<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course assignment Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CourseAssignment extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'course_id',
        'instructor_id',
        'branch_id',
        'venue_id',
        'session',
        'start_date',
        'end_date',
        'schedule_days',
        'start_time',
        'end_time',
        'max_capacity',
        'status',
        'legacy_scms_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'schedule_days' => 'array',
        'max_capacity' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Instructor.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Eloquent belongsTo relationship for Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Eloquent belongsTo relationship for Venue.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Eloquent hasMany relationship for Enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Eloquent hasMany relationship for Attendance records.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
