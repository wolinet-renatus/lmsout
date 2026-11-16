<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment schedule Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'enrollment_id',
        'installment_number',
        'installment_label',
        'amount_due',
        'amount_paid',
        'due_date',
        'status',
        'paid_at',
        'reminder_sent_at',
        'reminder_count',
        'legacy_scms_id',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for Invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Check condition: Is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Accessor for computed Remaining amount attribute.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, (float) $this->amount_due - (float) $this->amount_paid);
    }
}
