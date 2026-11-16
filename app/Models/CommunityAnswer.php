<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Community answer Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CommunityAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'content',
        'is_accepted',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(CommunityQuestion::class, 'question_id');
    }

    /**
     * Eloquent morphMany relationship for Votes.
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }
}
