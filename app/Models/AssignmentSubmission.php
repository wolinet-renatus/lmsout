<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assignment Submission Model
 *
 * This model represents a submission for an assignment.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class AssignmentSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'assignment_id',
        'enrollment_id',
        'submission_text',
        'submitted_at',
        'status',
        'grade',
        'instructor_notes',
        'similarity_score',
        'similarity_report',
        'external_similarity_provider',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'grade' => 'integer',
        'similarity_score' => 'decimal:2',
        'similarity_report' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_GRADED = 'graded';
    const STATUS_RETURNED = 'returned';

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Assignment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Eloquent hasMany relationship for Files.
     */
    public function files(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionFile::class);
    }

    /**
     * Check condition: Is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check condition: Is graded.
     */
    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    /**
     * Check condition: Is returned.
     */
    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }
}
