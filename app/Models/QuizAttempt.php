<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz Attempt Model
 *
 * This model represents an attempt at a quiz.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'enrollment_id',
        'score',
        'passed',
        'time_taken',
        'started_at',
        'completed_at',
        'proctoring_snapshots',
        'proctoring_flags',
        'merit_rank',
        'paper_question_ids',
    ];

    protected $casts = [
        'score' => 'integer',
        'passed' => 'boolean',
        'time_taken' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'proctoring_snapshots' => 'array',
        'proctoring_flags' => 'array',
        'merit_rank' => 'integer',
        'paper_question_ids' => 'array',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Quiz.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Eloquent hasMany relationship for Answers.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * Calculate score.
     */
    public function calculateScore(): int
    {
        $totalQuestions = $this->quiz->questions()->count();
        if ($totalQuestions === 0) {
            return 0;
        }

        $correctAnswers = $this->answers()->where('is_correct', true)->count();
        return (int) round(($correctAnswers / $totalQuestions) * 100);
    }

    /**
     * Check condition: Is passed.
     */
    public function isPassed(): bool
    {
        return $this->score >= $this->quiz->passing_score;
    }
}
