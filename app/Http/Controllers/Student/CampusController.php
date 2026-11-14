<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CampusAttendanceService;
use App\Services\QrCodeService;
use App\Services\SrsWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    public function __construct(
        private SrsWorkflowService $srs,
        private CampusAttendanceService $attendance
    ) {
    }

    public function invoices(Request $request)
    {
        $invoices = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title,allow_online_f2f_payment', 'branch:id,name', 'paymentSchedules', 'payments' => fn ($q) => $q->latest()])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('student.campus.invoices', compact('invoices'));
    }

    public function uploadReceipt(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, (float) $invoice->balance)],
            'receipt_number' => ['required', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('receipt')->store('campus/receipts', 'public');

        $this->srs->applyPaymentToInvoice($invoice, [
            'amount' => $data['amount'],
            'payment_method' => 'bank_transfer',
            'receipt_number' => $data['receipt_number'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'receipt_file' => $path,
            'verify_immediately' => false,
            'notes' => 'Uploaded via Student Portal',
        ]);

        ToastMagic::success('Payment receipt submitted for verification.');

        return back();
    }

    public function attendance(Request $request)
    {
        $user = $request->user();
        $enrollments = Enrollment::with(['course:id,title,attendance_threshold,delivery_mode', 'branch:id,name', 'venue:id,name', 'invoice:id,enrollment_id,balance,payment_status'])
            ->where('user_id', $user->id)
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->latest()
            ->get();

        $records = AttendanceRecord::withoutGlobalScope('branch')
            ->with(['course:id,title', 'branch:id,name'])
            ->where('user_id', $user->id)
            ->latest('attendance_date')
            ->paginate(20);

        return view('student.campus.attendance', compact('enrollments', 'records'));
    }

    public function checkIn(Request $request, string $token)
    {
        $result = $this->attendance->checkIn($token, $request->user());
        if ($result['ok']) {
            ToastMagic::success($result['message']);
        } else {
            ToastMagic::error($result['message']);
        }

        return redirect()->route('student.campus.attendance');
    }

    public function generateControlNumber(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->user_id === (int) $request->user()->id, 403);
        $updated = $this->srs->ensureControlNumber($invoice);
        ToastMagic::success('Control number ready: ' . $updated->control_number);

        return back();
    }

    public function unenroll(Request $request, Enrollment $enrollment)
    {
        abort_unless((int) $enrollment->user_id === (int) $request->user()->id, 403);
        $invoice = $enrollment->invoice;

        if (($invoice && (float) $invoice->paid_amount > 0) || $enrollment->status === Enrollment::STATUS_COMPLETED) {
            ToastMagic::error('Paid or completed enrollments cannot be cancelled from the student portal.');
            return back();
        }

        if ($invoice) {
            $invoice->paymentSchedules()->delete();
            $invoice->delete();
        }
        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);
        ToastMagic::success('Unpaid enrollment cancelled.');

        return back();
    }

    public function downloadCertificatePdf(Request $request, Enrollment $enrollment)
    {
        abort_unless((int) $enrollment->user_id === (int) $request->user()->id, 403);
        abort_unless(
            $enrollment->certificate_serial && in_array($enrollment->certificate_status, ['ready', 'issued'], true),
            404
        );

        $enrollment->load(['user', 'applicant', 'course', 'branch']);
        $verifyUrl = route('verify-certificate', ['code' => $enrollment->certificate_serial]);
        $qr = QrCodeService::dataUri($verifyUrl, 120);

        return Pdf::loadView('campus.certificate-pdf', compact('enrollment', 'qr', 'verifyUrl'))
            ->download(($enrollment->certificate_serial ?: 'certificate') . '.pdf');
    }

    public function receipt(Request $request, Payment $payment)
    {
        $payment->load(['invoice.branch', 'invoice.course', 'invoice.user', 'invoice.applicant', 'user', 'applicant']);
        $ownerId = (int) ($payment->user_id ?? $payment->invoice?->user_id ?? 0);
        abort_unless($ownerId === (int) $request->user()->id, 403);

        return view('campus.payment-receipt', compact('payment'));
    }

    public function timetable(Request $request)
    {
        $user = $request->user();
        $enrollments = Enrollment::with(['course:id,title,delivery_mode', 'courseAssignment.instructor:id,name', 'courseAssignment.venue:id,name', 'courseAssignment.branch:id,name', 'branch:id,name'])
            ->where('user_id', $user->id)
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->latest()
            ->get();

        $branchTimetable = CourseAssignment::withoutGlobalScope('branch')
            ->with(['course:id,title', 'instructor:id,name', 'branch:id,name', 'venue:id,name'])
            ->where('status', 'active')
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('start_date')
            ->limit(20)
            ->get();

        return view('student.campus.timetable', compact('enrollments', 'branchTimetable'));
    }
}
