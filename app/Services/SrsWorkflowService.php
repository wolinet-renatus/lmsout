<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SrsWorkflowService
{
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

        return $invoice->fresh();
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
            }

            return $payment->fresh();
        });
    }

    /**
     * Verify a pending offline/receipt payment and settle invoice balances & schedules.
     */
    public function verifyPendingPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            if ($payment->verification_status === 'verified' && $payment->status === Payment::STATUS_COMPLETED) {
                return $payment;
            }

            $payment->update([
                'verification_status' => 'verified',
                'status' => Payment::STATUS_COMPLETED,
            ]);

            if ($payment->invoice) {
                $this->settleVerifiedAmountOnInvoice($payment->invoice, $payment);
            }

            return $payment->fresh();
        });
    }

    public function syncInvoiceFromCompletedPayment(Payment $payment): void
    {
        if (! $payment->invoice_id) {
            return;
        }

        $invoice = Invoice::withoutGlobalScope('branch')->find($payment->invoice_id);
        if (! $invoice) {
            return;
        }

        if ($payment->verification_status !== 'verified') {
            $payment->forceFill(['verification_status' => 'verified', 'payment_date' => $payment->payment_date ?? now()->toDateString()])->saveQuietly();
        }

        $this->settleVerifiedAmountOnInvoice($invoice, $payment);
    }

    protected function settleVerifiedAmountOnInvoice(Invoice $invoice, Payment $payment): void
    {
        $verifiedTotal = (float) Payment::where('invoice_id', $invoice->id)
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
        if ($enrollment && $enrollment->status === Enrollment::STATUS_PENDING && $paidAmount > 0) {
            $enrollment->update([
                'status' => Enrollment::STATUS_APPROVED,
                'payment_id' => $payment->id,
                'enrolled_at' => $enrollment->enrolled_at ?? now(),
            ]);
        }

        if (! $payment->commission_processed) {
            $this->revenueShare->process($payment);
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

        return $enrollment->fresh();
    }
}
