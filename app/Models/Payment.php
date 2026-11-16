<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Payment Model
 *
 * This model represents a payment for a course.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class Payment extends Model
{
    use BranchScoped;

    protected $fillable = [
        'user_id',
        'bundle_id',
        'enrollment_id',
        'invoice_id',
        'applicant_id',
        'payment_method',
        'installment_type',
        'amount',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'platform_commission',
        'instructor_earning',
        'commission_processed',
        'status',
        'verification_status',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'flutterwave_transaction_id',
        'flutterwave_tx_ref',
        'paypal_order_id',
        'paypal_payer_id',
        'paypal_payment_id',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_confirmation_code',
        'pesapal_payment_account',
        'receipt_file',
        'receipt_number',
        'payment_date',
        'transaction_id',
        'notes',
        'coupon_id',
        'discount_amount',
        'legacy_scms_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'instructor_earning' => 'decimal:2',
        'commission_processed' => 'boolean',
        'discount_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Booted.
     */
    protected static function booted()
    {
        static::creating(function (Payment $payment) {
            $attributes = $payment->getAttributes();
            $payment->status = $payment->status ?: self::STATUS_PENDING;

            if (! array_key_exists('verification_status', $attributes)) {
                $payment->verification_status = $payment->status === self::STATUS_COMPLETED
                    ? 'verified'
                    : 'pending';
            } elseif ($payment->verification_status === 'verified' && $payment->status === self::STATUS_PENDING) {
                $payment->status = self::STATUS_COMPLETED;
            }

            if ($payment->status === self::STATUS_COMPLETED) {
                $payment->verification_status = 'verified';
                $payment->payment_date = $payment->payment_date ?? now()->toDateString();
            }
        });

        static::updating(function (Payment $payment) {
            if ($payment->verification_status === 'verified' && $payment->status === self::STATUS_PENDING) {
                $payment->status = self::STATUS_COMPLETED;
            } elseif ($payment->isDirty('verification_status') && $payment->verification_status === 'rejected' && $payment->status === self::STATUS_PENDING) {
                $payment->status = self::STATUS_FAILED;
            }

            if ($payment->status === self::STATUS_COMPLETED) {
                if ($payment->verification_status !== 'verified') {
                    $payment->verification_status = 'verified';
                }
                if (! $payment->payment_date) {
                    $payment->payment_date = now()->toDateString();
                }
            }
        });

        static::saved(function (Payment $payment) {
            if (($payment->isDirty('status') || $payment->wasChanged('status')) && $payment->status === self::STATUS_COMPLETED) {
                if ($payment->coupon_id) {
                    \App\Models\CouponUsage::firstOrCreate([
                        'coupon_id' => $payment->coupon_id,
                        'user_id' => $payment->user_id ?? ($payment->enrollment?->user_id),
                        'enrollment_id' => $payment->enrollment_id,
                    ], [
                        'discount_amount' => $payment->discount_amount ?? 0.00,
                    ]);

                    $coupon = \App\Models\Coupon::find($payment->coupon_id);
                    if ($coupon) {
                        $coupon->increment('used_count');
                    }
                }

                app(\App\Services\SrsWorkflowService::class)->finalizeCompletedPayment($payment);
            }
        });
    }

    const PAYMENT_METHOD_OFFLINE = 'offline';
    const PAYMENT_METHOD_STRIPE = 'stripe';
    const PAYMENT_METHOD_FLUTTERWAVE = 'flutterwave';
    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_PESAPAL = 'pesapal';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Eloquent belongsTo relationship for Coupon.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Eloquent belongsTo relationship for Bundle.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for computed Receipt url attribute.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_file) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($this->receipt_file)) {
            return null;
        }

        return $disk->url($this->receipt_file);
    }

    /**
     * Check condition: Is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check condition: Is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check condition: Is offline.
     */
    public function isOffline(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_OFFLINE;
    }

    /**
     * Check condition: Is stripe.
     */
    public function isStripe(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_STRIPE;
    }

    /**
     * Check condition: Is flutterwave.
     */
    public function isFlutterwave(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_FLUTTERWAVE;
    }

    /**
     * Check condition: Is paypal.
     */
    public function isPaypal(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAL;
    }

    /**
     * Check condition: Is Pesapal.
     */
    public function isPesapal(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PESAPAL;
    }

    /**
     * Eloquent belongsTo relationship for Invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Eloquent belongsTo relationship for Applicant.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Restrict branch-isolated staff to payments tied to their branch's invoices or applicants.
     */
    public function applyBranchScope(Builder $builder, int $branchId): void
    {
        $builder->where(function (Builder $q) use ($branchId): void {
            $q->whereHas('invoice', fn (Builder $iq) => $iq->withoutGlobalScope('branch')->where('branch_id', $branchId))
                ->orWhereHas('applicant', fn (Builder $aq) => $aq->withoutGlobalScope('branch')->where('branch_id', $branchId));
        });
    }

    /**
     * Explicitly scope payments to a branch via invoice/applicant relations.
     */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        $query->where(function (Builder $q) use ($branchId): void {
            $q->whereHas('invoice', fn (Builder $iq) => $iq->withoutGlobalScope('branch')->where('branch_id', $branchId))
                ->orWhereHas('applicant', fn (Builder $aq) => $aq->withoutGlobalScope('branch')->where('branch_id', $branchId));
        });

        return $query;
    }
}
