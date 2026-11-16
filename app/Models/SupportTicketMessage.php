<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Support ticket message Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class SupportTicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_admin_reply',
    ];

    protected $casts = [
        'is_admin_reply' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for Ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get any file attachments for the email message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'message_id');
    }
}

