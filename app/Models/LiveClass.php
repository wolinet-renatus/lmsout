<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LiveClass Model
 *
 * Represents a scheduled live session linked to a course and optionally a lesson.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class LiveClass extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE      = 'live';
    public const STATUS_ENDED     = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'instructor_id',
        'title',
        'description',
        'provider',
        'meeting_id',
        'meeting_password',
        'join_url',
        'host_url',
        'scheduled_at',
        'duration_minutes',
        'status',
        'recording_url',
        'recording_video_path',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'duration_minutes' => 'integer',
        'is_live'          => 'boolean',
    ];

    protected $appends = [
        'minutes_until',
        'is_upcoming',
        'is_currently_live',
        'recording_video_url',
        'provider_label',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Lesson.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Eloquent belongsTo relationship for Instructor.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Minutes remaining until the live class starts (negative if already past).
     */
    public function getMinutesUntilAttribute(): int
    {
        return (int) now()->diffInMinutes($this->scheduled_at, false);
    }

    /**
     * True when the class is scheduled and starts within the next 24 hours.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at->isFuture();
    }

    /**
     * True when the session is currently marked live.
     */
    public function getIsCurrentlyLiveAttribute(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * Streaming URL for uploaded recording.
     */
    public function getRecordingVideoUrlAttribute(): ?string
    {
        if (!$this->recording_video_path) {
            return null;
        }

        $path = ltrim($this->recording_video_path, '/');
        return url('/api/video/stream?path=' . urlencode($path));
    }

    /**
     * Human-readable provider label.
     */
    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            'zoom'        => 'Zoom',
            'google_meet' => 'Google Meet',
            default       => 'Custom Link',
        };
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Query scope to filter records by Upcoming.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('scheduled_at', '>', now());
    }

    /**
     * Query scope to filter records by Live.
     */
    public function scopeLive($query)
    {
        return $query->where('status', self::STATUS_LIVE);
    }

    /**
     * Query scope to filter records by For course.
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
