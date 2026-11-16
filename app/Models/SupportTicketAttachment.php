<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Support ticket attachment Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class SupportTicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'message_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    /**
     * Eloquent belongsTo relationship for Ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Eloquent belongsTo relationship for Message.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'message_id');
    }

    /**
     * Accessor for computed Url attribute.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . ltrim($this->file_path, '/'));
    }
}

