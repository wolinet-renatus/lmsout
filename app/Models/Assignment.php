<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assignment Model
 *
 * This model represents an assignment.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class Assignment extends Model
{
    protected $fillable = [
        'course_topic_id',
        'title',
        'description',
        'instructions',
        'accepted_submission_types',
        'max_file_size_mb',
        'rubric',
        'originality_check_enabled',
        'order',
    ];

    protected $appends = [
        'files_count',
    ];

    protected $casts = [
        'accepted_submission_types' => 'array',
        'max_file_size_mb' => 'integer',
        'originality_check_enabled' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the files count for the assignment.
     */
    public function getFilesCountAttribute()
    {
        return $this->files()->count();
    }

    /**
     * Get the topic that owns the assignment.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(CourseTopic::class, 'course_topic_id');
    }

    /**
     * Get the files for the assignment.
     */
    public function files(): HasMany
    {
        return $this->hasMany(AssignmentFile::class);
    }

    /**
     * Get the submissions for the assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
