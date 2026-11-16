<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lesson Progress Model
 *
 * This model represents the progress of a lesson for a user.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class LessonProgress extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'enrollment_id',
        'is_completed',
        'completed_at',
        'progress_percentage',
        'last_accessed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Lesson.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Mark complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $percentage): void
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
            'last_accessed_at' => now(),
        ]);
    }
}
