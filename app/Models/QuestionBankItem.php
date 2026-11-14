<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}

