<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Question bank Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class QuestionBank extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'subject',
        'difficulty',
        'description',
    ];

    /**
     * Eloquent belongsTo relationship for Creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Eloquent hasMany relationship for Items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuestionBankItem::class);
    }

    /**
     * Eloquent hasMany relationship for Quizzes.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}

