<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance record Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class AttendanceRecord extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'enrollment_id',
        'course_id',
        'course_assignment_id',
        'user_id',
        'branch_id',
        'attendance_date',
        'session_token',
        'status',
        'check_in_method',
        'checked_in_at',
        'marked_by',
        'notes',
        'legacy_scms_id',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'checked_in_at' => 'datetime',
    ];

    /**
     * Eloquent belongsTo relationship for Enrollment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Course assignment.
     */
    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Eloquent belongsTo relationship for Marker.
     */
    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
