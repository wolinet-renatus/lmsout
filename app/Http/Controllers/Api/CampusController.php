<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Venue;
use App\Services\CampusApplicationService;
use App\Services\CampusAttendanceService;
use App\Services\SrsWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campus controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class CampusController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        private CampusApplicationService $applications,
        private SrsWorkflowService $srs,
        private CampusAttendanceService $attendance
    ) {
    }

    /**
     * Meta.
     */
    public function meta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'address', 'phone']),
                'venues' => Venue::withoutGlobalScope('branch')->where('is_active', true)->orderBy('name')->get(['id', 'branch_id', 'name', 'capacity']),
                'countries' => Country::orderByRaw("CASE WHEN iso = 'TZ' THEN 0 ELSE 1 END")->orderBy('name')->get(['id', 'name', 'nicename', 'iso', 'iso3']),
                'education_levels' => Applicant::educationLevels(),
                'durations' => Applicant::durations(),
                'discount_reasons' => Applicant::discountReasons(),
                'installment_plans' => ['quarter' => 'Quarter (4 x 25%)', 'half' => 'Half (2 x 50%)', 'full' => 'Full (100%)'],
                'sessions' => ['morning', 'afternoon', 'evening', 'weekend'],
            ],
        ]);
    }

    /**
     * Branches.
     */
    public function branches(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'address', 'phone']),
        ]);
    }

    /**
     * Venues by branch.
     */
    public function venuesByBranch(Branch $branch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Venue::withoutGlobalScope('branch')
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name', 'capacity']),
        ]);
    }

    /**
     * Course fee.
     */
    public function courseFee(Course $course): JsonResponse
    {
        $weeks = 0;
        if ($course->duration_label && preg_match('/(\d+)/', $course->duration_label, $m)) {
            $weeks = (int) $m[1];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'fee' => (float) ($course->regular_price ?: $course->sale_price ?: 0),
                'duration' => (string) $weeks,
                'has_discount' => (bool) $course->has_discount,
                'discount' => (float) $course->discount_amount,
                'delivery_mode' => $course->delivery_mode ?? 'online',
                'requires_applicant_form' => $course->requiresApplicantForm(),
                'allow_online_f2f_payment' => (bool) ($course->allow_online_f2f_payment ?? true),
                'branch_id' => $course->default_branch_id,
                'venue_id' => $course->default_venue_id,
                'is_basic_computer' => str_contains(strtolower($course->title), 'basic computer'),
            ],
        ]);
    }

    /**
     * Apply.
     */
    public function apply(Request $request, Course $course): JsonResponse
    {
        if ($course->isOnline()) {
            return response()->json([
                'success' => false,
                'message' => 'Online courses must use direct online checkout (/api/courses/' . $course->id . '/enroll).',
            ], 422);
        }

        $request->merge(['course_id' => $course->id]);
        $data = $request->validate(CampusApplicationService::rules(true));

        try {
            $result = $this->applications->submit(
                $data,
                $request->only(['parent_staff_id', 'discount_attachment', 'receipt']),
                'api',
                $request->user('sanctum')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $invoice = $result['invoice']->load(['paymentSchedules', 'branch:id,name', 'course:id,title,allow_online_f2f_payment']);
        $token = $result['created_user'] ? $result['user']->createToken('auth_token')->plainTextToken : null;

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data' => [
                'applicant' => $result['applicant'],
                'enrollment' => $result['enrollment'],
                'invoice' => $invoice,
                'payment' => $result['payment'],
                'token' => $token,
                'token_type' => $token ? 'Bearer' : null,
            ],
        ], 201);
    }

    /**
     * Verify invoice.
     */
    public function verifyInvoice(string $number): JsonResponse
    {
        $invoice = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title', 'branch:id,name', 'user:id,name', 'paymentSchedules'])
            ->where(function ($q) use ($number) {
                $q->where('invoice_number', $number)
                    ->orWhere('control_number', $number);
            })
            ->first();

        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title,allow_online_f2f_payment,delivery_mode', 'branch:id,name', 'paymentSchedules', 'payments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Generate control number.
     */
    public function generateControlNumber(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless((int) $invoice->user_id === (int) $request->user()->id, 403);
        $updated = $this->srs->ensureControlNumber($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Control number ready.',
            'data' => $updated,
        ]);
    }

    /**
     * Upload receipt.
     */
    public function uploadReceipt(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless((int) $invoice->user_id === (int) $request->user()->id, 403);

        if ((float) $invoice->balance <= 0) {
            return response()->json(['success' => false, 'message' => 'Invoice is already fully paid.'], 422);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, (float) $invoice->balance)],
            'receipt_number' => ['required', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('receipt')->store('campus/receipts', 'public');
        $payment = $this->srs->applyPaymentToInvoice($invoice, [
            'amount' => $data['amount'],
            'payment_method' => 'bank_transfer',
            'receipt_number' => $data['receipt_number'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'receipt_file' => $path,
            'verify_immediately' => false,
            'notes' => 'Uploaded via Mobile/API',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment receipt submitted for verification.',
            'data' => $payment,
        ], 201);
    }

    /**
     * Unenroll.
     */
    public function unenroll(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_unless((int) $enrollment->user_id === (int) $request->user()->id, 403);
        $invoice = $enrollment->invoice;

        if (($invoice && (float) $invoice->paid_amount > 0) || $enrollment->status === Enrollment::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Paid or completed enrollments cannot be cancelled.',
            ], 422);
        }

        if ($invoice) {
            $invoice->paymentSchedules()->delete();
            $invoice->delete();
        }
        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'Unpaid enrollment cancelled.',
        ]);
    }

    /**
     * Attendance.
     */
    public function attendance(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => [
                'enrollments' => $enrollments,
                'records' => $records,
            ],
        ]);
    }

    /**
     * Check in.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:120']]);
        $result = $this->attendance->checkIn($data['token'], $request->user());

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }
}
