<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 * Conversation Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Conversation extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_id',
        'is_accepted',
        'is_closed',
        'last_message_at',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_closed' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    // ── Relationships ──

    /**
     * Eloquent belongsTo relationship for Student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Eloquent belongsTo relationship for Instructor.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Define custom validation error messages for the request.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Eloquent hasOne relationship for Latest message.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    // ── Scopes ──

    /**
     * Query scope to filter records by Accepted.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('is_accepted', true);
    }

    /**
     * Query scope to filter records by Requests.
     */
    public function scopeRequests(Builder $query): Builder
    {
        return $query->where('is_accepted', false)->where('is_closed', false);
    }

    /**
     * Query scope to filter records by For user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('student_id', $userId)
              ->orWhere('instructor_id', $userId);
        });
    }

    /**
     * Query scope to filter records by For student.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Query scope to filter records by For instructor.
     */
    public function scopeForInstructor(Builder $query, int $instructorId): Builder
    {
        return $query->where('instructor_id', $instructorId);
    }

    // ── Helpers ──

    /**
     * Count unread messages for a given user in this conversation.
     */
    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get the other participant (from the perspective of $userId).
     */
    public function otherParticipant(int $userId): ?User
    {
        return $this->student_id === $userId
            ? $this->instructor
            : $this->student;
    }
}
