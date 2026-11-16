<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment Model
 *
 * This model represents an enrollment for a course.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class Enrollment extends Model
{
    use BranchScoped;

    protected $fillable = [
        'user_id',
        'course_id',
        'applicant_id',
        'branch_id',
        'venue_id',
        'course_assignment_id',
        'payment_id',
        'status',
        'delivery_mode',
        'session',
        'start_date',
        'end_date',
        'duration_weeks',
        'agreed_fee',
        'attendance_percentage',
        'certificate_status',
        'certificate_serial',
        'certificate_ready_at',
        'certificate_issued_at',
        'certificate_issued_by',
        'enrolled_at',
        'legacy_scms_id',
    ];

    /**
     * Booted.
     */
    protected static function booted()
    {
        static::updated(function ($enrollment) {
            if ($enrollment->wasChanged('status') && $enrollment->status === self::STATUS_APPROVED) {
                // Pre-load payment to ensure we have bundle_id
                $enrollment->loadMissing('payment');

                if ($enrollment->payment && $enrollment->payment->bundle_id) {
                    $bundleId = $enrollment->payment->bundle_id;
                    $userId = $enrollment->user_id;
                    $paymentId = $enrollment->payment_id;

                    // Fetch all course IDs in this bundle
                    $courseIds = \Illuminate\Support\Facades\DB::table('bundle_courses')
                        ->where('bundle_id', $bundleId)
                        ->pluck('course_id');

                    // Wrap in withoutEvents to prevent infinite recursion
                    static::withoutEvents(function () use ($userId, $courseIds, $paymentId, $enrollment, $bundleId) {
                        // Activate ALL pending enrollments for this user and bundle
                        // Crucially, we also link them to the same payment_id
                        self::where('user_id', $userId)
                            ->whereIn('course_id', $courseIds)
                            ->where('status', self::STATUS_PENDING)
                            ->where('id', '!=', $enrollment->id)
                            ->update([
                                'status' => self::STATUS_APPROVED,
                                'enrolled_at' => now(),
                                'payment_id' => $paymentId,
                            ]);

                        // Complete the BundleEnrollment record if it exists
                        \App\Models\BundleEnrollment::updateOrCreate(
                            ['user_id' => $userId, 'bundle_id' => $bundleId],
                            [
                                'status' => 'approved',
                                'enrolled_at' => now(),
                                'payment_id' => $paymentId,
                            ]
                        );
                    });
                }
            }
        });
    }

    protected $casts = [
        'enrolled_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'agreed_fee' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'certificate_ready_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
    ];

    /**
     * Query scope to filter records by Completed.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Accessor for computed Certificate number attribute.
     */
    public function getCertificateNumberAttribute(): ?string
    {
        if (! $this->id || ! $this->user_id || ! $this->course_id) {
            return null;
        }

        return sprintf(
            'CERT-%06d-%s',
            $this->id,
            strtoupper(substr(md5($this->user_id . $this->course_id), 0, 6))
        );
    }

    /**
     * Accessor for computed Completion date attribute.
     */
    public function getCompletionDateAttribute(): ?string
    {
        return $this->updated_at?->format('M d, Y');
    }

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for Payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check condition: Is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Eloquent hasMany relationship for Is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Eloquent hasMany relationship for Is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Eloquent hasMany relationship for Lesson progress.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Eloquent hasMany relationship for Quiz attempts.
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Eloquent belongsTo relationship for Assignment submissions.
     */
    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Eloquent belongsTo relationship for Applicant.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
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
     * Eloquent belongsTo relationship for Course assignment.
     */
    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    /**
     * Eloquent hasMany relationship for Invoice.
     */
    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Eloquent belongsTo relationship for Attendance records.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Eloquent belongsTo relationship for Certificate issuer.
     */
    public function certificateIssuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'certificate_issued_by');
    }

    /**
     * Check condition: Is face to face or hybrid.
     */
    public function isFaceToFaceOrHybrid(): bool
    {
        return in_array($this->delivery_mode ?? $this->course?->delivery_mode ?? 'online', ['face_to_face', 'hybrid'], true);
    }

    /**
     * Restrict branch-isolated staff to enrollments belonging to their center.
     */
    public function applyBranchScope(Builder $builder, int $branchId): void
    {
        $builder->where($this->getTable() . '.branch_id', $branchId);
    }

    /**
     * Explicitly scope enrollments to a regional branch when provided.
     */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where($this->getTable() . '.branch_id', $branchId);
    }
}
