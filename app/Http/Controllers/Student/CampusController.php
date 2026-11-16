<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\CampusAttendanceService;
use App\Services\QrCodeService;
use App\Services\SrsWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Campus controller — handles student campus portal workflows and learning progress.
 *
 * @author Renatus Bernardo
 */
class CampusController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        private SrsWorkflowService $srs,
        private CampusAttendanceService $attendance
    ) {
    }

    /**
     * Build the base query for all DB invoices belonging to a student.
     */
    private function studentInvoicesQuery(User $user): Builder
    {
        return Invoice::withoutGlobalScope('branch')
            ->where(function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('enrollment', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('user_id', $user->id))
                    ->orWhereHas('applicant', function (Builder $aq) use ($user): void {
                        $aq->withoutGlobalScope('branch')->where('user_id', $user->id);
                        if (! empty($user->email)) {
                            $aq->orWhere('email', $user->email);
                        }
                    });
            });
    }

    /**
     * Verify whether the authenticated student owns the given invoice.
     */
    private function studentOwnsInvoice(User $user, Invoice $invoice): bool
    {
        if ((int) $invoice->user_id === (int) $user->id) {
            return true;
        }

        $invoice->loadMissing(['enrollment:id,user_id', 'applicant:id,user_id,email']);

        if ((int) ($invoice->enrollment?->user_id ?? 0) === (int) $user->id) {
            return true;
        }

        if ((int) ($invoice->applicant?->user_id ?? 0) === (int) $user->id) {
            return true;
        }

        return $invoice->applicant
            && ! empty($user->email)
            && strcasecmp((string) $invoice->applicant->email, (string) $user->email) === 0;
    }

    /**
     * Resolve dynamic invoice payload & KPI summary from the database.
     *
     * @return array{invoices: Collection, summary: array<string, mixed>, statusFilter: string, searchTerm: string}
     */
    private function buildInvoicesPayload(Request $request): array
    {
        $user = $request->user();
        $this->srs->syncMissingInvoicesForStudent($user);

        $statusFilter = trim((string) $request->input('status', 'all'));
        $searchTerm = trim((string) $request->input('search', ''));

        $allInvoices = $this->studentInvoicesQuery($user)
            ->with([
                'course:id,title,allow_online_f2f_payment,delivery_mode',
                'branch:id,name',
                'enrollment:id,user_id,venue_id,course_assignment_id,status',
                'enrollment.venue:id,name',
                'enrollment.courseAssignment:id,venue_id,session,start_date,end_date',
                'enrollment.courseAssignment.venue:id,name',
                'paymentSchedules',
                'payments' => fn ($q) => $q->withoutGlobalScope('branch')->latest(),
            ])
            ->latest()
            ->get();

        $pendingVerifications = $allInvoices->sum(fn (Invoice $inv) => $inv->payments->where('verification_status', 'pending')->count());

        $summary = [
            'total_count' => $allInvoices->count(),
            'paid_count' => $allInvoices->where('payment_status', 'paid')->count(),
            'open_count' => $allInvoices->where('balance', '>', 0)->count(),
            'total_billed' => (float) $allInvoices->sum('net_amount'),
            'total_paid' => (float) $allInvoices->sum('paid_amount'),
            'outstanding_balance' => (float) $allInvoices->sum('balance'),
            'pending_verifications' => (int) $pendingVerifications,
        ];

        $filtered = $allInvoices->filter(function (Invoice $inv) use ($statusFilter, $searchTerm): bool {
            if ($statusFilter !== '' && $statusFilter !== 'all') {
                if ($statusFilter === 'overdue') {
                    $hasOverdue = $inv->balance > 0 && $inv->paymentSchedules->contains(fn ($s) => $s->status === 'overdue');
                    if (! $hasOverdue) {
                        return false;
                    }
                } elseif (strtolower((string) $inv->payment_status) !== strtolower($statusFilter)) {
                    return false;
                }
            }

            if ($searchTerm !== '') {
                $needle = mb_strtolower($searchTerm);
                $haystacks = [
                    (string) $inv->invoice_number,
                    (string) ($inv->control_number ?? ''),
                    (string) ($inv->course?->title ?? ''),
                    (string) ($inv->branch?->name ?? ''),
                ];
                $matched = false;
                foreach ($haystacks as $value) {
                    if ($value !== '' && str_contains(mb_strtolower($value), $needle)) {
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    return false;
                }
            }

            return true;
        })->values();

        return [
            'invoices' => $filtered,
            'summary' => $summary,
            'statusFilter' => $statusFilter ?: 'all',
            'searchTerm' => $searchTerm,
        ];
    }

    /**
     * Invoices.
     */
    public function invoices(Request $request)
    {
        return view('student.campus.invoices', $this->buildInvoicesPayload($request));
    }

    /**
     * Real-time JSON snapshot for student campus invoices and receipt verifications.
     */
    public function liveInvoices(Request $request): JsonResponse
    {
        $payload = $this->buildInvoicesPayload($request);
        $summary = $payload['summary'];

        $items = $payload['invoices']->map(function (Invoice $inv): array {
            $net = (float) $inv->net_amount;
            $paid = (float) $inv->paid_amount;
            $balance = (float) $inv->balance;
            $progress = $net > 0 ? (int) min(100, round(($paid / $net) * 100)) : ($inv->payment_status === 'paid' ? 100 : 0);
            $status = strtolower((string) ($inv->payment_status ?: ($balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'))));

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'control_number' => $inv->control_number,
                'payment_status' => $status,
                'payment_status_label' => ucfirst($status),
                'net_amount' => $net,
                'paid_amount' => $paid,
                'balance' => $balance,
                'net_formatted' => currency_format($net),
                'paid_formatted' => currency_format($paid),
                'balance_formatted' => currency_format($balance),
                'progress_percent' => $progress,
                'schedules' => $inv->paymentSchedules->map(fn ($s) => [
                    'id' => $s->id,
                    'status' => strtolower((string) $s->status),
                    'status_label' => ucfirst((string) $s->status),
                    'amount_due_formatted' => currency_format((float) $s->amount_due),
                    'amount_paid_formatted' => currency_format((float) $s->amount_paid),
                ])->values(),
                'payments' => $inv->payments->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'receipt_number' => $p->receipt_number ?: '-',
                    'amount_formatted' => currency_format((float) $p->amount),
                    'status' => strtolower((string) $p->status),
                    'verification_status' => strtolower((string) ($p->verification_status ?: 'verified')),
                    'verification_label' => ucfirst((string) ($p->verification_status ?: 'verified')),
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'synced_at' => now()->format('H:i:s'),
            'summary' => [
                'total_count' => $summary['total_count'],
                'paid_count' => $summary['paid_count'],
                'open_count' => $summary['open_count'],
                'total_billed_formatted' => currency_format($summary['total_billed']),
                'total_paid_formatted' => currency_format($summary['total_paid']),
                'outstanding_balance_formatted' => currency_format($summary['outstanding_balance']),
                'pending_verifications' => $summary['pending_verifications'],
            ],
            'invoices' => $items,
        ]);
    }

    /**
     * Upload receipt.
     */
    public function uploadReceipt(Request $request, Invoice $invoice)
    {
        abort_unless($this->studentOwnsInvoice($request->user(), $invoice), 403);

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

    /**
     * Attendance.
     */
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

    /**
     * Check in.
     */
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

    /**
     * Generate control number.
     */
    public function generateControlNumber(Request $request, Invoice $invoice)
    {
        abort_unless($this->studentOwnsInvoice($request->user(), $invoice), 403);
        $updated = $this->srs->ensureControlNumber($invoice);
        ToastMagic::success('Control number ready: ' . $updated->control_number);

        return back();
    }

    /**
     * Unenroll.
     */
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

    /**
     * Download certificate pdf.
     */
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

    /**
     * Receipt.
     */
    public function receipt(Request $request, Payment $payment)
    {
        $payment->load([
            'invoice.branch',
            'invoice.course',
            'invoice.user',
            'invoice.applicant',
            'enrollment.course',
            'enrollment.branch',
            'enrollment.user',
            'bundle',
            'user',
            'applicant',
        ]);
        $ownerIds = array_filter([
            (int) ($payment->user_id ?? 0),
            (int) ($payment->invoice?->user_id ?? 0),
            (int) ($payment->enrollment?->user_id ?? 0),
        ]);
        abort_unless(in_array((int) $request->user()->id, $ownerIds, true), 403);

        return view('campus.payment-receipt', compact('payment'));
    }

    /**
     * Compute real-time cohort / session live status from dates and status column.
     *
     * @return array{key: string, label: string, badge: string}
     */
    private function resolveScheduleLiveStatus(?string $dbStatus, ?Carbon $startDate, ?Carbon $endDate, ?string $startTime = null, ?string $endTime = null): array
    {
        $status = strtolower((string) ($dbStatus ?: 'active'));

        if ($status === 'cancelled') {
            return ['key' => 'cancelled', 'label' => 'Cancelled', 'badge' => 'danger'];
        }

        if ($status === 'completed') {
            return ['key' => 'completed', 'label' => 'Completed', 'badge' => 'secondary'];
        }

        $today = now()->startOfDay();
        if ($endDate && $endDate->copy()->endOfDay()->lt($today)) {
            return ['key' => 'completed', 'label' => 'Completed', 'badge' => 'secondary'];
        }

        if ($startDate && $startDate->copy()->startOfDay()->gt($today)) {
            return ['key' => 'upcoming', 'label' => 'Upcoming', 'badge' => 'info'];
        }

        if ($startTime && $endTime) {
            $nowTime = now()->format('H:i:s');
            if ($nowTime >= $startTime && $nowTime <= $endTime) {
                return ['key' => 'in_session', 'label' => 'In Session Now', 'badge' => 'success'];
            }
        }

        if (in_array($status, ['active', 'ongoing', 'approved'], true)) {
            return ['key' => 'active', 'label' => 'Ongoing Cohort', 'badge' => 'primary'];
        }

        return ['key' => $status, 'label' => ucfirst($status), 'badge' => 'warning text-dark'];
    }

    /**
     * Build timetable view data and real-time status snapshot.
     *
     * @return array<string, mixed>
     */
    private function buildTimetablePayload(Request $request): array
    {
        $user = $request->user();
        $statusFilter = trim((string) $request->input('status', 'all'));
        $searchTerm = trim((string) $request->input('search', ''));

        $rawEnrollments = Enrollment::withoutGlobalScope('branch')
            ->with([
                'course:id,title,delivery_mode,instructor_id',
                'course.instructor:id,name',
                'courseAssignment.instructor:id,name',
                'courseAssignment.venue:id,name',
                'courseAssignment.branch:id,name',
                'venue:id,name',
                'branch:id,name',
                'invoice:id,enrollment_id,invoice_number,payment_status,balance',
            ])
            ->where('user_id', $user->id)
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->latest()
            ->get();

        $enrollments = $rawEnrollments->map(function (Enrollment $e) {
            $assignment = $e->courseAssignment;
            $startDate = $assignment?->start_date ?? $e->start_date;
            $endDate = $assignment?->end_date ?? $e->end_date;

            $enrollmentStatus = strtolower((string) ($e->status ?: 'pending'));
            if ($enrollmentStatus === Enrollment::STATUS_CANCELLED) {
                $liveStatus = ['key' => 'cancelled', 'label' => 'Cancelled', 'badge' => 'danger'];
            } elseif ($enrollmentStatus === Enrollment::STATUS_COMPLETED) {
                $liveStatus = ['key' => 'completed', 'label' => 'Completed', 'badge' => 'success'];
            } elseif ($enrollmentStatus === Enrollment::STATUS_PENDING) {
                $hasBalance = $e->invoice && (float) $e->invoice->balance > 0;
                $liveStatus = [
                    'key' => 'pending',
                    'label' => $hasBalance ? 'Awaiting Payment' : 'Pending Approval',
                    'badge' => 'warning text-dark',
                ];
            } else {
                $liveStatus = $this->resolveScheduleLiveStatus(
                    $assignment?->status ?? 'active',
                    $startDate,
                    $endDate,
                    $assignment?->start_time,
                    $assignment?->end_time
                );
            }

            $timeWindow = ($assignment?->start_time && $assignment?->end_time)
                ? substr((string) $assignment->start_time, 0, 5) . ' - ' . substr((string) $assignment->end_time, 0, 5)
                : null;

            $daysLabel = is_array($assignment?->schedule_days) && ! empty($assignment->schedule_days)
                ? implode(', ', array_map('ucfirst', $assignment->schedule_days))
                : null;

            return (object) [
                'id' => $e->id,
                'course_title' => $e->course?->title ?? '-',
                'delivery_mode' => ucfirst(str_replace('_', ' ', (string) ($e->delivery_mode ?? 'face_to_face'))),
                'branch_name' => $e->branch?->name ?? $assignment?->branch?->name ?? 'Main Campus',
                'venue_name' => $e->venue?->name ?? $assignment?->venue?->name ?? 'TBA',
                'tutor_name' => $assignment?->instructor?->name ?? $e->course?->instructor?->name ?? 'Assigned Tutor',
                'session_name' => ucfirst((string) ($assignment?->session ?? $e->session ?? 'Scheduled')),
                'time_window' => $timeWindow,
                'days_label' => $daysLabel,
                'start_date_label' => $startDate ? $startDate->format('d M Y') : 'TBA',
                'end_date_label' => $endDate ? $endDate->format('d M Y') : ($e->duration_weeks ? $e->duration_weeks . ' weeks' : 'Ongoing'),
                'attendance_percentage' => number_format((float) ($e->attendance_percentage ?? 0), 1) . '%',
                'enrollment_status' => ucfirst($enrollmentStatus),
                'payment_status' => $e->invoice ? ucfirst((string) $e->invoice->payment_status) : 'N/A',
                'live_status_key' => $liveStatus['key'],
                'live_status_label' => $liveStatus['label'],
                'live_status_badge' => $liveStatus['badge'],
            ];
        });

        $rawRegional = CourseAssignment::withoutGlobalScope('branch')
            ->with(['course:id,title', 'instructor:id,name', 'branch:id,name', 'venue:id,name'])
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderByRaw("CASE WHEN status IN ('active','ongoing') THEN 0 WHEN status = 'scheduled' THEN 1 WHEN status = 'completed' THEN 2 ELSE 3 END")
            ->orderByDesc('start_date')
            ->limit(30)
            ->get();

        $branchTimetable = $rawRegional->map(function (CourseAssignment $row) {
            $liveStatus = $this->resolveScheduleLiveStatus(
                $row->status,
                $row->start_date,
                $row->end_date,
                $row->start_time,
                $row->end_time
            );

            $timeWindow = ($row->start_time && $row->end_time)
                ? substr((string) $row->start_time, 0, 5) . ' - ' . substr((string) $row->end_time, 0, 5)
                : 'Flexible';

            $daysLabel = is_array($row->schedule_days) && ! empty($row->schedule_days)
                ? implode(', ', array_map('ucfirst', $row->schedule_days))
                : 'Daily Cohort';

            return (object) [
                'id' => $row->id,
                'course_title' => $row->course?->title ?? '-',
                'branch_name' => $row->branch?->name ?? '-',
                'venue_name' => $row->venue?->name ?? 'TBA',
                'tutor_name' => $row->instructor?->name ?? 'TBA',
                'session_name' => ucfirst((string) ($row->session ?? 'Scheduled')),
                'time_window' => $timeWindow,
                'days_label' => $daysLabel,
                'start_date_label' => optional($row->start_date)->format('d M Y') ?? 'TBA',
                'end_date_label' => optional($row->end_date)->format('d M Y') ?? 'Ongoing',
                'live_status_key' => $liveStatus['key'],
                'live_status_label' => $liveStatus['label'],
                'live_status_badge' => $liveStatus['badge'],
            ];
        });

        $applyFilters = function (Collection $items) use ($statusFilter, $searchTerm): Collection {
            return $items->filter(function ($item) use ($statusFilter, $searchTerm): bool {
                if ($statusFilter !== '' && $statusFilter !== 'all' && $item->live_status_key !== $statusFilter) {
                    return false;
                }
                if ($searchTerm !== '') {
                    $needle = mb_strtolower($searchTerm);
                    $haystack = mb_strtolower($item->course_title . ' ' . $item->branch_name . ' ' . $item->venue_name . ' ' . $item->tutor_name);
                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }
                return true;
            })->values();
        };

        $filteredEnrollments = $applyFilters($enrollments);
        $filteredRegional = $applyFilters($branchTimetable);

        $summary = [
            'enrolled_total' => $enrollments->count(),
            'active_or_ongoing' => $enrollments->whereIn('live_status_key', ['active', 'in_session', 'upcoming'])->count(),
            'completed_count' => $enrollments->where('live_status_key', 'completed')->count(),
            'regional_count' => $branchTimetable->count(),
        ];

        return [
            'enrollments' => $filteredEnrollments,
            'branchTimetable' => $filteredRegional,
            'summary' => $summary,
            'statusFilter' => $statusFilter ?: 'all',
            'searchTerm' => $searchTerm,
        ];
    }

    /**
     * Timetable.
     */
    public function timetable(Request $request)
    {
        return view('student.campus.timetable', $this->buildTimetablePayload($request));
    }

    /**
     * Real-time JSON snapshot for student campus timetable.
     */
    public function liveTimetable(Request $request): JsonResponse
    {
        $payload = $this->buildTimetablePayload($request);

        return response()->json([
            'success' => true,
            'synced_at' => now()->format('H:i:s'),
            'summary' => $payload['summary'],
            'enrollments' => $payload['enrollments'],
            'branchTimetable' => $payload['branchTimetable'],
        ]);
    }
}
