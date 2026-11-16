<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Contracts\Auth\MustVerifyEmail;

/**
 * User Model
 *
 * This model represents a user.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            \Illuminate\Support\Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        \Illuminate\Support\Facades\Mail::to($this)->send(new \App\Mail\VerifyEmailMail($this, $verificationUrl));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'is_approved',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
        'force_password_reset',
        'password_changed_at',
        'failed_login_attempts',
        'lockout_count',
        'locked_until',
        'active_session_id',
        'last_activity_at',
        'bio',
        'phone',
        'address',
        'registration_number',
        'national_id',
        'specialization',
        'years_experience',
        'previous_experience',
        'highest_degree',
        'field_of_study',
        'certifications',
        'profile_photo',
        'resume',
        'linkedin',
        'twitter',
        'facebook',
        'youtube',
        'approval_reason',
        'rejection_reason',
        'balance',
        'stripe_connect_account_id',
        'stripe_connect_onboarded',
        'legacy_scms_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'force_password_reset' => 'boolean',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_activity_at' => 'datetime',
            'balance' => 'decimal:2',
            'stripe_connect_onboarded' => 'boolean',
        ];
    }

    protected $appends = [
        'courses_count',
        'average_rating',
    ];

    /**
     * Accessor for computed Courses count attribute.
     */
    public function getCoursesCountAttribute()
    {
        return $this->courses()->where('status', 'published')->count();
    }

    /**
     * Accessor for computed Average rating attribute.
     */
    public function getAverageRatingAttribute()
    {
        return round($this->courses()->withAvg('reviews', 'rating')->get()->avg('reviews_avg_rating') ?? 0.0, 1);
    }

    /**
     * Check if user is admin (includes super-admin and staff)
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is unrestricted Super Admin / System Administrator.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->role !== 'admin') {
            return false;
        }

        if ($this->hasRole(['super-admin', 'admin', 'system-administrator'])) {
            return true;
        }

        try {
            if ($this->hasPermissionTo('manage-all')) {
                return true;
            }
        } catch (\Throwable) {
            // Ignore missing permission cache in fresh installs
        }

        // Legacy initial admin rows with no Spatie role assigned yet remain unrestricted
        return $this->roles()->count() === 0;
    }

    /**
     * Check if user is a role/permission-restricted admin or campus staff account.
     */
    public function isStaff(): bool
    {
        return $this->role === 'admin' && ! $this->isSuperAdmin();
    }

    /**
     * Whether an admin/staff user can view all regional branches without restriction.
     */
    public function isMultiBranchAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasRole(['director', 'deputy-director', 'marketing-officer'])
            || $this->hasAdminPermission('manage-branches');
    }

    /**
     * Effective branch filter: branch-scoped staff are pinned to their own branch.
     */
    public function effectiveBranchId(?int $requestedBranchId = null): ?int
    {
        if ($this->isMultiBranchAdmin()) {
            return $requestedBranchId ?: null;
        }

        if ($this->branch_id) {
            return (int) $this->branch_id;
        }

        return $requestedBranchId ?: null;
    }

    /**
     * Human-friendly label for the user's active Spatie / portal role.
     */
    public function primaryRoleLabel(): string
    {
        $roleName = $this->roles()->value('name');
        $map = [
            'super-admin'          => 'Super Admin',
            'system-administrator' => 'System Administrator',
            'admin'                => 'Administrator',
            'director'             => 'Director',
            'deputy-director'      => 'Deputy Director',
            'branch-registrar'     => 'Branch Manager',
            'coordinator'          => 'Training Coordinator',
            'marketing-officer'    => 'Marketing Officer',
            'accountant'           => 'Accountant',
            'staff'                => 'Staff',
            'instructor'           => 'Tutor / Instructor',
            'student'              => 'Student',
        ];

        if ($roleName && isset($map[$roleName])) {
            return $map[$roleName];
        }

        if ($roleName) {
            return \Illuminate\Support\Str::title(str_replace('-', ' ', $roleName));
        }

        return match ($this->role) {
            'admin'      => 'Administrator',
            'instructor' => 'Tutor / Instructor',
            default      => 'Student',
        };
    }

    /**
     * Resolve the dedicated dashboard profile key for the authenticated user's primary role.
     */
    public function primaryDashboardProfile(): string
    {
        if ($this->role === 'instructor') {
            return 'instructor';
        }

        if ($this->role === 'student') {
            return 'student';
        }

        if ($this->isSuperAdmin()) {
            return 'admin';
        }

        $roleName = (string) ($this->roles()->value('name') ?? '');

        return match ($roleName) {
            'director', 'deputy-director' => 'director',
            'marketing-officer'           => 'marketing',
            'branch-registrar'            => 'branch_manager',
            'coordinator'                 => 'coordinator',
            'accountant'                  => 'accountant',
            'staff'                       => 'staff',
            default                       => 'custom',
        };
    }

    /**
     * Human-readable isolation scope summary for the user's current role & branch.
     */
    public function dashboardScopeDescription(): string
    {
        if ($this->role === 'instructor') {
            return 'Tutor Portal (/instructor/*)';
        }

        if ($this->role === 'student') {
            return 'Student Portal (/student/*)';
        }

        if ($this->isMultiBranchAdmin()) {
            return 'All Regional Centers';
        }

        return $this->branch?->name ?? 'Assigned Center';
    }

    /**
     * Check if an admin/staff user has a specific granular permission
     */
    public function hasAdminPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        try {
            return $this->hasPermissionTo($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if user is instructor
     */
    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if instructor is approved
     */
    public function isApprovedInstructor(): bool
    {
        return $this->isInstructor() && $this->is_approved;
    }

    /**
     * Get the courses for the instructor.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /**
     * Get the enrollments for the user.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the lesson progress records for the user.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get the quiz attempts for the user.
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the assignment submissions for the user.
     */
    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Get the reviews written by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Eloquent hasMany relationship for Account deletion requests.
     */
    public function accountDeletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    /**
     * Eloquent hasMany relationship for Notification settings.
     */
    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    /**
     * Eloquent hasMany relationship for Instructor withdrawals.
     */
    public function instructorWithdrawals(): HasMany
    {
        return $this->hasMany(InstructorWithdrawal::class, 'instructor_id');
    }

    /**
     * Eloquent hasMany relationship for Community questions.
     */
    public function communityQuestions(): HasMany
    {
        return $this->hasMany(CommunityQuestion::class);
    }

    /**
     * Eloquent hasMany relationship for Community answers.
     */
    public function communityAnswers(): HasMany
    {
        return $this->hasMany(CommunityAnswer::class);
    }

    /**
     * Get conversations where user is the student.
     */
    public function studentConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'student_id');
    }

    /**
     * Get conversations where user is the instructor.
     */
    public function instructorConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'instructor_id');
    }

    /**
     * Eloquent belongsTo relationship for Branch.
     */
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Eloquent hasMany relationship for Applicant.
     */
    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class);
    }

    /**
     * Eloquent hasMany relationship for Invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Eloquent hasMany relationship for Course assignments.
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'instructor_id');
    }

    /**
     * Eloquent hasMany relationship for Attendance records.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

}
