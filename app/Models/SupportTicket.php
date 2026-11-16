<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * SupportTicket Model (v3.0.0)
 *
 * Represents a support ticket between Student/Instructor and Admin.
 * @author Renatus Bernardo
 */
class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'user_id',
        'sender_role',
        'subject',
        'department',
        'priority',
        'status',
        'last_reply_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at'     => 'datetime',
    ];

    /**
     * Booted.
     */
    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
            if (empty($ticket->last_reply_at)) {
                $ticket->last_reply_at = now();
            }
        });
    }

    /**
     * Eloquent belongsTo relationship for Generate ticket number.
     */
    public static function generateTicketNumber(): string
    {
        return 'TKT-' . date('Y') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define custom validation error messages for the request.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Get any file attachments for the email message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_id');
    }

    /**
     * Accessor for computed Priority badge attribute.
     */
    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high'   => 'warning',
            'medium' => 'info',
            default  => 'secondary',
        };
    }

    /**
     * Accessor for computed Status badge attribute.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open'        => 'primary',
            'in_progress' => 'warning',
            'answered'    => 'success',
            'closed'      => 'secondary',
            default       => 'light',
        };
    }

    /**
     * Scope support tickets by the submitter's assigned center when isolated.
     */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        return $query->whereHas('user', fn (Builder $uq) => $uq->where('branch_id', $branchId));
    }
}

