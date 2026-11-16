<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz Model
 *
 * This model represents a quiz for a course.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class Quiz extends Model
{
    protected $fillable = [
        'course_topic_id',
        'question_bank_id',
        'random_questions_count',
        'title',
        'subject',
        'description',
        'passing_score',
        'time_limit',
        'is_live_exam',
        'result_publish_at',
        'proctoring_enabled',
        'order',
    ];

    protected $casts = [
        'question_bank_id' => 'integer',
        'random_questions_count' => 'integer',
        'passing_score' => 'integer',
        'time_limit' => 'integer',
        'is_live_exam' => 'boolean',
        'result_publish_at' => 'datetime',
        'proctoring_enabled' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for Question bank.
     */
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    /**
     * Get the topic that owns the quiz.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(CourseTopic::class, 'course_topic_id');
    }

    /**
     * Get the questions for the quiz.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the attempts for the quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
