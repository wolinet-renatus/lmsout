<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BundleEnrollment Model
 *
 * Tracks user purchases of course bundles.
 * @author Renatus Bernardo
 */
class BundleEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'bundle_id',
        'payment_id',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Bundle.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Eloquent belongsTo relationship for Payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check condition: Is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
