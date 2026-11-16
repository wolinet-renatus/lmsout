<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Bundle;
use App\Models\BundleEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * SRS Workflow Service
 *
 * Orchestrates GePG-style control number generation, SCMS fee/discount calculations,
 * installment schedule allocation, invoice-to-payment reconciliation, attendance
 * percentage recalculation, and QR/serial certificate issuance.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class SrsWorkflowService
{
    /**
     * Inject RevenueShareService to settle platform/instructor splits on verified payments.
     */
    public function __construct(private RevenueShareService $revenueShare)
    {
    }

    /**
     * Generate a unique OUT/GePG-style 12-digit control number starting with 99104.
     */
    public function generateControlNumber(): string
    {
        do {
            $candidate = '99104' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Invoice::withoutGlobalScope('branch')->where('control_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Ensure the given invoice has an assigned control number.
     */
    public function ensureControlNumber(Invoice $invoice): Invoice
    {
        if (! empty($invoice->control_number)) {
            return $invoice;
        }

        $invoice->update([
            'control_number' => $this->generateControlNumber(),
            'control_number_generated_at' => now(),
        ]);

        $fresh = $invoice->fresh();
        $recipient = $fresh->user ?? $fresh->enrollment?->user;
        if ($recipient && Route::has('student.campus.invoices.index')) {
            app(PortalNotificationService::class)->notifyUser(
                $recipient,
                'Control Number Issued',
                "Control number {$fresh->control_number} is now ready for invoice {$fresh->invoice_number}.",
                'info',
                route('student.campus.invoices.index')
            );
        }

        return $fresh;
    }

    /**
     * Compute fee breakdown for Face-to-Face & Hybrid applications following SCMS rules.
     *
     * @return array{total_amount: float, discount_amount: float, net_amount: float, discount_percentage: float}
     */
    public function calculateFees(
        Course $course,
        bool $isStaffStudent = false,
        bool $hasDiscount = false,
        float $discountPercentage = 0.0
    ): array {
        $baseFee = (float) ($course->regular_price ?? 0);
        if ($baseFee <= 0 && (float) ($course->sale_price ?? 0) > 0) {
            $baseFee = (float) $course->sale_price;
        }

        $discountAmount = 0.0;
        $effectivePct = 0.0;

        // Rule from SCMS Form: Staff Student on Basic Computer Application receives 50% waiver
        $isBasicComputer = Str::contains(Str::lower($course->title), 'basic computer');
        if ($isStaffStudent && $isBasicComputer) {
            $effectivePct = 50.0;
            $discountAmount = round($baseFee * 0.50, 2);
        } elseif ($hasDiscount && $discountPercentage > 0) {
            $effectivePct = min(100.0, max(0.0, $discountPercentage));
            $discountAmount = round($baseFee * ($effectivePct / 100.0), 2);
        } elseif ($course->has_discount && (float) $course->discount_amount > 0) {
            $discountAmount = min($baseFee, round((float) $course->discount_amount, 2));
            $effectivePct = $baseFee > 0 ? round(($discountAmount / $baseFee) * 100.0, 2) : 0.0;
        } elseif ((float) ($course->sale_price ?? 0) > 0 && (float) $course->sale_price < $baseFee) {
            $discountAmount = round($baseFee - (float) $course->sale_price, 2);
            $effectivePct = $baseFee > 0 ? round(($discountAmount / $baseFee) * 100.0, 2) : 0.0;
        }

        $netAmount = max(0.0, round($baseFee - $discountAmount, 2));

        return [
            'total_amount' => round($baseFee, 2),
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'discount_percentage' => $effectivePct,
        ];
    }

    /**
     * Create payment schedule installments (quarter = 4x25%, half = 2x50%, full = 1x100%).
     */
    public function buildPaymentSchedules(Invoice $invoice, ?Enrollment $enrollment = null, string $plan = 'full'): void
    {
        if ($invoice->paymentSchedules()->exists()) {
            return;
        }

        $net = (float) $invoice->net_amount;
        if ($net <= 0) {
            return;
        }

        $startDate = $enrollment?->start_date ? $enrollment->start_date->copy() : now();
        $rows = [];

        if ($plan === 'quarter') {
            $chunk = round($net / 4, 2);
            $running = 0.0;
            for ($i = 1; $i <= 4; $i++) {
                $amt = ($i === 4) ? round($net - $running, 2) : $chunk;
                $running += $amt;
                $rows[] = [
                    'installment_number' => $i,
                    'installment_label' => "Quarter {$i} (25%)",
                    'amount_due' => $amt,
                    'due_date' => $startDate->copy()->addDays(($i - 1) * 7)->toDateString(),
                ];
            }
        } elseif ($plan === 'half') {
            $first = round($net / 2, 2);
            $second = round($net - $first, 2);
            $rows[] = [
                'installment_number' => 1,
                'installment_label' => 'First Half (50%)',
                'amount_due' => $first,
                'due_date' => $startDate->toDateString(),
            ];
            $rows[] = [
                'installment_number' => 2,
                'installment_label' => 'Second Half (50%)',
                'amount_due' => $second,
                'due_date' => $startDate->copy()->addDays(14)->toDateString(),
            ];
        } else {
            $rows[] = [
                'installment_number' => 1,
                'installment_label' => 'Full Payment (100%)',
                'amount_due' => $net,
                'due_date' => ($invoice->due_date ?? $startDate->copy()->addDays(7))->toDateString(),
            ];
        }

        foreach ($rows as $row) {
            PaymentSchedule::create([
                'invoice_id' => $invoice->id,
                'enrollment_id' => $enrollment?->id ?? $invoice->enrollment_id,
                'installment_number' => $row['installment_number'],
                'installment_label' => $row['installment_label'],
                'amount_due' => $row['amount_due'],
                'amount_paid' => 0,
                'due_date' => $row['due_date'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Record a payment against an Invoice, sync Installment Schedules, Enrollment status, and RevenueShareService.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyPaymentToInvoice(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data): Payment {
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $verifyNow = (bool) ($data['verify_immediately'] ?? true);
            $verificationStatus = $data['verification_status'] ?? ($verifyNow ? 'verified' : 'pending');
            $paymentStatus = $verificationStatus === 'verified' ? Payment::STATUS_COMPLETED : Payment::STATUS_PENDING;

            $payment = Payment::create([
                'user_id' => $data['user_id'] ?? $invoice->user_id,
                'enrollment_id' => $data['enrollment_id'] ?? $invoice->enrollment_id,
                'invoice_id' => $invoice->id,
                'applicant_id' => $data['applicant_id'] ?? $invoice->applicant_id,
                'payment_method' => $data['payment_method'] ?? 'control_number',
                'installment_type' => $data['installment_type'] ?? $invoice->installment_plan ?? 'full',
                'amount' => $amount,
                'subtotal' => $amount,
                'tax_amount' => 0,
                'tax_rate' => 0,
                'status' => $paymentStatus,
                'verification_status' => $verificationStatus,
                'receipt_file' => $data['receipt_file'] ?? null,
                'receipt_number' => $data['receipt_number'] ?? ('RCPT-' . strtoupper(Str::random(8))),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'transaction_id' => $data['transaction_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($verificationStatus === 'verified' && $amount > 0) {
                $this->settleVerifiedAmountOnInvoice($invoice, $payment);
            } elseif ($amount > 0) {
                $recipient = User::find($payment->user_id) ?? $invoice->user ?? $invoice->enrollment?->user;
                if ($recipient && Route::has('student.payments')) {
                    app(PortalNotificationService::class)->notifyUser(
                        $recipient,
                        'Payment Receipt Submitted',
                        "Payment submission for invoice {$invoice->invoice_number} is awaiting finance verification.",
                        'info',
                        route('student.payments')
                    );
                }
            }

            return $payment->fresh();
        });
    }

    /**
     * Verify a pending offline/receipt payment and settle invoice, enrollments, and earnings.
     */
    public function verifyPendingPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $fresh = Payment::withoutGlobalScope('branch')->lockForUpdate()->findOrFail($payment->id);

            $fresh->update([
                'verification_status' => 'verified',
                'status' => Payment::STATUS_COMPLETED,
                'payment_date' => $fresh->payment_date ?? now()->toDateString(),
            ]);

            $this->finalizeCompletedPayment($fresh->fresh());

            return $fresh->fresh();
        });
    }

    /**
     * Generate a unique institutional invoice number using the configured prefix.
     */
    public function nextInvoiceNumber(): string
    {
        $prefix = function_exists('generateInvoicePrefix') ? generateInvoicePrefix() : 'INV-';
        do {
            $candidate = $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Invoice::withoutGlobalScope('branch')->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Ensure any completed or enrollment-linked payment has a real DB Invoice + GePG control number.
     */
    public function ensureInvoiceForPayment(Payment $payment, bool $syncCompleted = true): ?Invoice
    {
        if ($payment->invoice_id) {
            $existing = Invoice::withoutGlobalScope('branch')->find($payment->invoice_id);
            if ($existing && empty($existing->control_number) && $existing->payment_status !== 'paid') {
                $existing->forceFill([
                    'control_number' => $this->generateControlNumber(),
                    'control_number_generated_at' => now(),
                ])->saveQuietly();
            }

            if ($existing && $syncCompleted && $payment->status === Payment::STATUS_COMPLETED) {
                $this->syncInvoiceFromCompletedPayment($payment);
            }

            return $existing?->fresh();
        }

        if (! $payment->user_id) {
            return null;
        }

        // Skip abandoned orphan pending rows with neither an enrollment nor a bundle
        if ($payment->status !== Payment::STATUS_COMPLETED && ! $payment->enrollment_id && ! $payment->bundle_id) {
            return null;
        }

        $enrollment = $payment->enrollment_id
            ? Enrollment::withoutGlobalScope('branch')->with('course')->find($payment->enrollment_id)
            : null;

        $courseId = $enrollment?->course_id;
        if (! $courseId && $payment->bundle_id) {
            $bundle = Bundle::with('courses:id,branch_id')->find($payment->bundle_id);
            $firstBundleCourse = $bundle?->courses->first();
            $courseId = $firstBundleCourse?->id;
        }

        $branchId = $enrollment?->branch_id
            ?? $enrollment?->course?->branch_id
            ?? User::find($payment->user_id)?->branch_id;

        $invoice = DB::transaction(function () use ($payment, $enrollment, $courseId, $branchId): Invoice {
            $candidate = null;

            if ($enrollment) {
                $candidate = Invoice::withoutGlobalScope('branch')
                    ->where('user_id', $payment->user_id)
                    ->where('enrollment_id', $enrollment->id)
                    ->where('payment_status', '!=', 'cancelled')
                    ->orderByDesc('id')
                    ->first();

                if ($candidate && $candidate->payment_status === 'paid' && $payment->status === Payment::STATUS_COMPLETED) {
                    $hasOtherCompleted = Payment::withoutGlobalScope('branch')
                        ->where('invoice_id', $candidate->id)
                        ->where('status', Payment::STATUS_COMPLETED)
                        ->where('id', '!=', $payment->id)
                        ->exists();

                    if ($hasOtherCompleted) {
                        $candidate = null;
                    }
                }
            }

            if (! $candidate && $courseId) {
                $candidate = Invoice::withoutGlobalScope('branch')
                    ->where('user_id', $payment->user_id)
                    ->where('course_id', $courseId)
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->orderByDesc('id')
                    ->first();
            }

            if ($candidate) {
                $updates = [];
                if (empty($candidate->control_number)) {
                    $updates['control_number'] = $this->generateControlNumber();
                    $updates['control_number_generated_at'] = now();
                }
                if (! $candidate->enrollment_id && $enrollment) {
                    $updates['enrollment_id'] = $enrollment->id;
                }
                if (! $candidate->course_id && $courseId) {
                    $updates['course_id'] = $courseId;
                }
                if (! $candidate->branch_id && $branchId) {
                    $updates['branch_id'] = $branchId;
                }
                if (! empty($updates)) {
                    $candidate->forceFill($updates)->saveQuietly();
                }

                $invoice = $candidate;
            } else {
                $netAmount = max(0.0, round((float) $payment->amount, 2));
                $discountAmount = max(0.0, round((float) ($payment->discount_amount ?? 0), 2));
                $totalAmount = round($netAmount + $discountAmount, 2);
                $issuedAt = $payment->created_at ?? now();

                $invoice = Invoice::create([
                    'invoice_number' => $this->nextInvoiceNumber(),
                    'control_number' => $this->generateControlNumber(),
                    'control_number_generated_at' => now(),
                    'user_id' => $payment->user_id,
                    'enrollment_id' => $enrollment?->id,
                    'course_id' => $courseId,
                    'branch_id' => $branchId,
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                    'paid_amount' => 0,
                    'balance' => $netAmount,
                    'payment_status' => $netAmount <= 0 ? 'paid' : 'unpaid',
                    'installment_plan' => 'full',
                    'due_date' => $issuedAt->copy()->addDays(7)->toDateString(),
                    'issued_at' => $issuedAt,
                    'notes' => $payment->bundle_id
                        ? 'Auto-generated from bundle checkout'
                        : 'Auto-generated from course checkout',
                    'created_by' => $payment->user_id,
                ]);

                $this->buildPaymentSchedules($invoice, $enrollment, 'full');
            }

            $payment->forceFill(['invoice_id' => $invoice->id])->saveQuietly();

            return $invoice;
        });

        if ($syncCompleted && $payment->status === Payment::STATUS_COMPLETED) {
            $this->syncInvoiceFromCompletedPayment($payment->fresh() ?? $payment);
        }

        return $invoice->fresh();
    }

    /**
     * Self-heal missing invoice links and unsettled completed invoices for a given student.
     */
    public function syncMissingInvoicesForStudent(User $user): void
    {
        $unlinkedPayments = Payment::withoutGlobalScope('branch')
            ->where('user_id', $user->id)
            ->whereNull('invoice_id')
            ->where(function ($query): void {
                $query->where('status', Payment::STATUS_COMPLETED)
                    ->orWhereNotNull('enrollment_id')
                    ->orWhereNotNull('bundle_id');
            })
            ->orderBy('id')
            ->get();

        foreach ($unlinkedPayments as $payment) {
            $this->ensureInvoiceForPayment($payment);
        }

        // Reconcile any completed payment whose linked invoice still reports zero paid_amount.
        $unreconciledPayments = Payment::withoutGlobalScope('branch')
            ->where('user_id', $user->id)
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereNotNull('invoice_id')
            ->whereHas('invoice', fn ($q) => $q->withoutGlobalScope('branch')->where('paid_amount', '<=', 0)->where('net_amount', '>', 0))
            ->get();

        foreach ($unreconciledPayments as $payment) {
            $this->syncInvoiceFromCompletedPayment($payment);
        }
    }

    /**
     * Finalize any completed payment idempotently across invoices, enrollments, bundles,
     * revenue share, and student bell notifications.
     */
    public function finalizeCompletedPayment(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_COMPLETED) {
            return;
        }

        if ($payment->verification_status !== 'verified' || ! $payment->payment_date) {
            $payment->forceFill([
                'verification_status' => 'verified',
                'payment_date' => $payment->payment_date ?? now()->toDateString(),
            ])->saveQuietly();
        }

        if (! $payment->invoice_id) {
            $this->ensureInvoiceForPayment($payment, false);
            $payment = $payment->fresh() ?? $payment;
        }

        if ($payment->invoice_id) {
            $this->syncInvoiceFromCompletedPayment($payment);
        }

        $enrollmentActivated = false;
        if ($payment->enrollment_id) {
            $enrollment = Enrollment::withoutGlobalScope('branch')->with('course', 'user')->find($payment->enrollment_id);
            if ($enrollment && $enrollment->status === Enrollment::STATUS_PENDING) {
                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'payment_id' => $enrollment->payment_id ?? $payment->id,
                    'enrolled_at' => $enrollment->enrolled_at ?? now(),
                ]);
                $enrollmentActivated = true;
            }

            if ($payment->bundle_id && $enrollment) {
                $bundle = Bundle::with('courses')->find($payment->bundle_id);
                if ($bundle) {
                    foreach ($bundle->courses as $bundleCourseItem) {
                        if ((int) $bundleCourseItem->id === (int) $enrollment->course_id) {
                            continue;
                        }

                        Enrollment::updateOrCreate(
                            ['user_id' => $enrollment->user_id, 'course_id' => $bundleCourseItem->id],
                            [
                                'status' => Enrollment::STATUS_APPROVED,
                                'enrolled_at' => now(),
                            ]
                        );
                    }

                    BundleEnrollment::updateOrCreate(
                        ['user_id' => $enrollment->user_id, 'bundle_id' => $bundle->id],
                        [
                            'status' => 'approved',
                            'enrolled_at' => now(),
                        ]
                    );
                }
            }

            if ($enrollmentActivated && $enrollment && ! $payment->invoice_id && $enrollment->user) {
                $courseTitle = $enrollment->course?->title ?? 'Course';
                $accessUrl = Route::has('student.courses.access') && $enrollment->course_id
                    ? route('student.courses.access', $enrollment->course_id)
                    : (Route::has('student.my-courses') ? route('student.my-courses') : null);

                app(PortalNotificationService::class)->notifyUser(
                    $enrollment->user,
                    'Payment Confirmed',
                    "Your payment for {$courseTitle} is completed and enrollment is active.",
                    'success',
                    $accessUrl
                );
            }
        }

        $freshPayment = $payment->fresh() ?? $payment;
        if (! $freshPayment->commission_processed) {
            $this->revenueShare->process($freshPayment);
        }
    }

    /**
     * Reconcile an invoice after an online gateway payment is completed.
     */
    public function syncInvoiceFromCompletedPayment(Payment $payment): void
    {
        if (! $payment->invoice_id) {
            return;
        }

        // Bypass branch scope so gateway webhooks/callbacks can reconcile cross-branch invoices.
        $invoice = Invoice::withoutGlobalScope('branch')->find($payment->invoice_id);
        if (! $invoice) {
            return;
        }

        if ($payment->verification_status !== 'verified') {
            $payment->forceFill(['verification_status' => 'verified', 'payment_date' => $payment->payment_date ?? now()->toDateString()])->saveQuietly();
        }

        $this->settleVerifiedAmountOnInvoice($invoice, $payment);
    }

    /**
     * Sum verified payments, update invoice balance/status, allocate installments,
     * approve pending enrollments, and process revenue share once per payment.
     */
    protected function settleVerifiedAmountOnInvoice(Invoice $invoice, Payment $payment): void
    {
        $verifiedTotal = (float) Payment::withoutGlobalScope('branch')
            ->where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $net = (float) $invoice->net_amount;
        $paidAmount = min($net, round($verifiedTotal, 2));
        $balance = max(0.0, round($net - $paidAmount, 2));
        $invStatus = $balance <= 0.01 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

        $invoice->update([
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'payment_status' => $invStatus,
            'paid_at' => $invStatus === 'paid' ? ($invoice->paid_at ?? now()) : null,
        ]);

        // Allocate paid total greedily across schedules in chronological order
        $remainingToAllocate = $paidAmount;
        foreach ($invoice->paymentSchedules()->orderBy('installment_number')->get() as $schedule) {
            $due = (float) $schedule->amount_due;
            $allocated = min($due, max(0.0, $remainingToAllocate));
            $remainingToAllocate = max(0.0, $remainingToAllocate - $allocated);

            $schedStatus = $allocated >= ($due - 0.01)
                ? 'paid'
                : ($allocated > 0 ? 'partial' : ($schedule->due_date && $schedule->due_date->isPast() ? 'overdue' : 'pending'));

            $schedule->update([
                'amount_paid' => round($allocated, 2),
                'status' => $schedStatus,
                'paid_at' => $schedStatus === 'paid' ? ($schedule->paid_at ?? now()) : null,
            ]);
        }

        // Approve enrollment once any verified payment covers first installment or balance
        $enrollment = $invoice->enrollment;
        $enrollmentWasApproved = false;
        if ($enrollment && $enrollment->status === Enrollment::STATUS_PENDING && $paidAmount > 0) {
            $enrollment->update([
                'status' => Enrollment::STATUS_APPROVED,
                'payment_id' => $payment->id,
                'enrolled_at' => $enrollment->enrolled_at ?? now(),
            ]);
            $enrollmentWasApproved = true;
        }

        if (! $payment->commission_processed) {
            $this->revenueShare->process($payment);
        }

        $recipient = $invoice->user ?? $enrollment?->user ?? ($payment->user_id ? User::find($payment->user_id) : null);
        if ($recipient && ($invoice->wasChanged(['paid_amount', 'payment_status']) || $enrollmentWasApproved)) {
            $notifier = app(PortalNotificationService::class);
            $invUrl = Route::has('student.campus.invoices.index') ? route('student.campus.invoices.index') : null;
            $statusLabel = $invStatus === 'paid' ? 'fully settled' : 'partially paid';

            $notifier->notifyUser(
                $recipient,
                'Payment Confirmed',
                "Your payment for invoice {$invoice->invoice_number} has been verified ({$statusLabel}).",
                'success',
                $invUrl
            );

            if ($enrollmentWasApproved && $enrollment) {
                $courseTitle = $enrollment->course?->title ?? 'Campus Course';
                $courseUrl = Route::has('student.campus.timetable') ? route('student.campus.timetable') : null;
                $notifier->notifyUser(
                    $recipient,
                    'Enrollment Approved',
                    "Your enrollment for {$courseTitle} is now active. Check your campus timetable and course materials.",
                    'success',
                    $courseUrl
                );
            }
        }
    }

    /**
     * Recompute stored attendance_percentage on an enrollment.
     */
    public function recalculateAttendancePercentage(Enrollment $enrollment): float
    {
        $total = AttendanceRecord::withoutGlobalScope('branch')
            ->where('enrollment_id', $enrollment->id)
            ->count();

        if ($total === 0) {
            $enrollment->update(['attendance_percentage' => 0]);
            return 0.0;
        }

        $attended = AttendanceRecord::withoutGlobalScope('branch')
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $pct = round(($attended / $total) * 100, 2);
        $enrollment->update(['attendance_percentage' => $pct]);

        return $pct;
    }

    /**
     * Check whether a F2F/Hybrid enrollment meets attendance + tuition clearance for certificate.
     */
    public function isCertificateEligible(Enrollment $enrollment): bool
    {
        $enrollment->loadMissing('course', 'invoice');

        if (! $enrollment->isFaceToFaceOrHybrid()) {
            return $enrollment->status === Enrollment::STATUS_COMPLETED;
        }

        $threshold = (int) ($enrollment->course?->attendance_threshold ?? 75);
        $attendanceOk = (float) $enrollment->attendance_percentage >= $threshold;

        $invoice = $enrollment->invoice;
        $tuitionOk = $invoice ? ((float) $invoice->balance <= 0.01) : true;

        return $attendanceOk && $tuitionOk;
    }

    /**
     * Return all F2F/Hybrid enrollments eligible for certificate marking/issuance.
     */
    public function getEligibleEnrollments(?int $branchId = null): Collection
    {
        $query = Enrollment::with(['user', 'course', 'branch', 'invoice'])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->filter(fn (Enrollment $enrollment) => $this->isCertificateEligible($enrollment))->values();
    }

    /**
     * Mark or issue an OUT LMS campus certificate with unique serial number.
     */
    public function ensureCertificate(Enrollment $enrollment, ?int $issuedBy = null, string $targetStatus = 'issued'): Enrollment
    {
        $serial = $enrollment->certificate_serial;
        if (! $serial) {
            $serial = sprintf('OUT-CERT-%s-%06d', date('Y'), $enrollment->id);
        }

        $updates = [
            'certificate_serial' => $serial,
            'certificate_status' => $targetStatus,
            'certificate_ready_at' => $enrollment->certificate_ready_at ?? now(),
        ];

        if ($targetStatus === 'issued') {
            $updates['certificate_issued_at'] = now();
            $updates['certificate_issued_by'] = $issuedBy;
            if ($enrollment->status !== Enrollment::STATUS_COMPLETED) {
                $updates['status'] = Enrollment::STATUS_COMPLETED;
            }
        }

        $enrollment->update($updates);

        $fresh = $enrollment->fresh();
        if ($targetStatus === 'issued' && $fresh?->user && Route::has('student.certificates')) {
            $courseTitle = $fresh->course?->title ?? 'Course';
            app(PortalNotificationService::class)->notifyUser(
                $fresh->user,
                'Certificate Issued',
                "Your verified OUT certificate ({$serial}) for {$courseTitle} is ready to download.",
                'success',
                route('student.certificates')
            );
        }

        return $fresh;
    }
}
