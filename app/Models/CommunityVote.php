<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Community vote Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CommunityVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'votable_id',
        'votable_type',
        'vote',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent morphTo relationship for Votable.
     */
    public function votable(): MorphTo
    {
        return $this->morphTo();
    }
}
