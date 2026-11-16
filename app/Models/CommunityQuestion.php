<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Community question Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CommunityQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'tags',
        'views',
    ];

    protected $casts = [
        'tags' => 'json',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent hasMany relationship for Answers.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(CommunityAnswer::class, 'question_id');
    }

    /**
     * Eloquent morphMany relationship for Votes.
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }
}
