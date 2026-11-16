<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Invoice Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Invoice extends Model
{
    use HasFactory, BranchScoped, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'control_number',
        'control_number_generated_at',
        'applicant_id',
        'user_id',
        'enrollment_id',
        'course_id',
        'branch_id',
        'total_amount',
        'discount_amount',
        'net_amount',
        'paid_amount',
        'balance',
        'payment_status',
        'installment_plan',
        'due_date',
        'issued_at',
        'paid_at',
        'notes',
        'created_by',
        'legacy_scms_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'control_number_generated_at' => 'datetime',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'invoice_number',
                'control_number',
                'net_amount',
                'paid_amount',
                'balance',
                'payment_status',
                'installment_plan',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Eloquent belongsTo relationship for Applicant.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Eloquent belongsTo relationship for Creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Eloquent hasMany relationship for Payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Eloquent hasMany relationship for Payment schedules.
     */
    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class)->orderBy('installment_number');
    }

    /**
     * Scope invoices belonging to a given user across direct, enrollment, and applicant ownership.
     */
    public function scopeOwnedByUser($query, User $user)
    {
        return $query->where(function ($q) use ($user): void {
            $q->where('user_id', $user->id)
                ->orWhereHas('enrollment', fn ($eq) => $eq->where('user_id', $user->id))
                ->orWhereHas('applicant', function ($aq) use ($user): void {
                    $aq->where('user_id', $user->id);
                    if (! empty($user->email)) {
                        $aq->orWhere('email', $user->email);
                    }
                });
        });
    }
}
