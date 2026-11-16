<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin notification history Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class AdminNotificationHistory extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'target_audience',
        'image',
        'url',
        'recipient_count',
        'sent_by',
    ];

    /**
     * Eloquent belongsTo relationship for Sender.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
