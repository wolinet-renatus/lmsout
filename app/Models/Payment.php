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
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'paystack_reference',
        'paystack_access_code',
        'flutterwave_transaction_id',
        'flutterwave_tx_ref',
        'paypal_order_id',
        'paypal_payer_id',
        'paypal_payment_id',
        'sslcommerz_tran_id',
        'sslcommerz_session_key',
        'sslcommerz_val_id',
        'mollie_payment_id',
        'bkash_payment_id',
        'bkash_trx_id',
        'xpay_order_id',
        'xpay_transaction_id',
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

    protected static function booted()
    {
        static::saved(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === self::STATUS_COMPLETED) {
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

                if ($payment->invoice_id) {
                    app(\App\Services\SrsWorkflowService::class)->syncInvoiceFromCompletedPayment($payment);
                }
            }
        });
    }

    const PAYMENT_METHOD_RAZORPAY = 'razorpay';
    const PAYMENT_METHOD_OFFLINE = 'offline';
    const PAYMENT_METHOD_STRIPE = 'stripe';
    const PAYMENT_METHOD_PAYSTACK = 'paystack';
    const PAYMENT_METHOD_FLUTTERWAVE = 'flutterwave';
    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_SSLCOMMERZ = 'sslcommerz';
    const PAYMENT_METHOD_MOLLIE = 'mollie';
    const PAYMENT_METHOD_BKASH = 'bkash';
    const PAYMENT_METHOD_XPAY = 'xpay';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRazorpay(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_RAZORPAY;
    }

    public function isOffline(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_OFFLINE;
    }

    public function isStripe(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_STRIPE;
    }

    public function isPaystack(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYSTACK;
    }

    public function isFlutterwave(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_FLUTTERWAVE;
    }

    public function isPaypal(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAL;
    }

    public function isSslcommerz(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_SSLCOMMERZ;
    }

    public function isMollie(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_MOLLIE;
    }

    public function isBkash(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BKASH;
    }

    public function isXpay(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_XPAY;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

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
