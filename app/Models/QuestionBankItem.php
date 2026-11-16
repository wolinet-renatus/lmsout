<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Question bank item Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class QuestionBankItem extends Model
{
    protected $fillable = [
        'question_bank_id',
        'subject',
        'difficulty',
        'question',
        'type',
        'options',
        'correct_answer',
        'explanation',
        'marks',
    ];

    protected $casts = [
        'options' => 'array',
        'marks' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for Bank.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}

