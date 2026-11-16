<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Model
 *
 * This model represents a course.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class Course extends Model
{
    protected $fillable = [
        'instructor_id',
        'assistant_instructor_id',
        'certificate_template_id',
        'title',
        'course_type',
        'delivery_mode',
        'default_branch_id',
        'default_venue_id',
        'duration_label',
        'description',
        'featured_image',
        'intro_video',
        'visibility',
        'public_course',
        'max_students',
        'difficulty',
        'pricing_model',
        'regular_price',
        'sale_price',
        'price_currency',
        'has_discount',
        'discount_amount',
        'schedule_enabled',
        'drip_enabled',
        'schedule_date',
        'course_category_id',
        'course_language_id',
        'category',
        'language',
        'tags',
        'seo_title',
        'seo_description',
        'seo_tags',
        'marketing_copy',
        'requirements',
        'objectives',
        'status',
        'deleted_date',
        'approval_reason',
        'rejection_reason',
        'is_live_course',
        'attendance_threshold',
        'allow_online_f2f_payment',
        'f2f_commission_enabled',
        'legacy_scms_id',
    ];

    protected $casts = [
        'public_course' => 'boolean',
        'schedule_enabled' => 'boolean',
        'drip_enabled'     => 'boolean',
        'schedule_date'    => 'datetime',
        'regular_price'    => 'decimal:2',
        'sale_price'       => 'decimal:2',
        'has_discount'     => 'boolean',
        'discount_amount'  => 'decimal:2',
        'max_students'     => 'integer',
        'deleted_date'     => 'datetime',
        'is_live_course'   => 'boolean',
        'attendance_threshold' => 'integer',
        'allow_online_f2f_payment' => 'boolean',
        'f2f_commission_enabled'   => 'boolean',
        'marketing_copy'   => 'array',
    ];


    protected $appends = [
        'thumbnail',
        'price',
        'discounted_price',
        'rating',
        'reviews_count',
        'lessons_count',
        'students_count',
        'intro_video_url',
    ];

    /**
     * Get the instructor that owns the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Eloquent belongsTo relationship for Assistant instructor.
     */
    public function assistantInstructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_instructor_id');
    }

    /**
     * Eloquent hasMany relationship for Co instructors.
     */
    public function coInstructors(): HasMany
    {
        return $this->hasMany(CourseCoInstructor::class)->with('user');
    }

    /**
     * Eloquent hasMany relationship for Faqs.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(CourseFaq::class)->orderBy('order');
    }

    /**
     * Primary instructor share percent.
     */
    public function primaryInstructorSharePercent(): float
    {
        $coShare = $this->relationLoaded('coInstructors')
            ? (float) $this->coInstructors->sum('revenue_share')
            : (float) $this->coInstructors()->sum('revenue_share');
        return max(0.0, round(100.0 - $coShare, 2));
    }

    /**
     * Check condition: Can user manage curriculum.
     */
    public function canUserManageCurriculum(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->role === 'admin' || (int) $this->instructor_id === (int) $user->id) {
            return true;
        }
        if ((int) $this->assistant_instructor_id === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('coInstructors')) {
            return $this->coInstructors->contains(fn ($co) => (int) $co->user_id === (int) $user->id && (bool) $co->can_edit);
        }

        return $this->coInstructors()
            ->where('user_id', $user->id)
            ->where('can_edit', true)
            ->exists();
    }

    /**
     * Eloquent belongsTo relationship for Can user manage pricing and team.
     */
    public function canUserManagePricingAndTeam(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $user->role === 'admin' || (int) $this->instructor_id === (int) $user->id;
    }

    /**
     * Eloquent belongsTo relationship for Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    /**
     * Eloquent belongsTo relationship for Category relation.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->category();
    }

    /**
     * Eloquent belongsTo relationship for Language.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(CourseLanguage::class, 'course_language_id');
    }

    /**
     * Get the topics for the course.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(CourseTopic::class)->orderBy('order');
    }

    /**
     * Get the enrollments for the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the reviews for the course.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->orderBy('created_at', 'desc');
    }

    /**
     * Get the lessons for the course directly 
     */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, CourseTopic::class);
    }

    /**
     * Get the bundles that include this course.
     */
    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_courses');
    }

    /**
     * Eloquent hasMany relationship for Coupons.
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get live classes for this course.
     */
    public function liveClasses(): HasMany
    {
        return $this->hasMany(LiveClass::class)->orderBy('scheduled_at');
    }

    /**
     * Get the next upcoming live class.
     */
    public function nextLiveClass()
    {
        return $this->liveClasses()->upcoming()->first();
    }

    /**
     * Get the certificate template associated with the course.
     */
    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }


    /**
     * Accessor for computed Thumbnail attribute.
     */
    public function getThumbnailAttribute()
    {
        if (!$this->featured_image) {
            return asset('assets/front/img/home-1/courses/courses-01.jpg');
        }
        if (str_starts_with($this->featured_image, 'http://') || str_starts_with($this->featured_image, 'https://')) {
            return $this->featured_image;
        }
        if (str_starts_with($this->featured_image, 'assets/')) {
            return asset($this->featured_image);
        }
        return asset('storage/' . ltrim($this->featured_image, '/'));
    }

    /**
     * Accessor for computed Price attribute.
     */
    public function getPriceAttribute()
    {
        return $this->regular_price;
    }

    /**
     * Accessor for computed Discounted price attribute.
     */
    public function getDiscountedPriceAttribute()
    {
        return $this->sale_price;
    }

    /**
     * Resolve the currency in which this course's regular_price and sale_price are stored.
     */
    public function getPriceCurrencyCode(): string
    {
        return normalize_currency_code($this->price_currency ?: base_currency_code(), base_currency_code());
    }

    /**
     * Convert a stored course price amount into the target (or active display) currency.
     */
    public function priceInCurrency($amount, ?string $targetCurrency = null): float
    {
        return convert_currency((float) ($amount ?? 0), $this->getPriceCurrencyCode(), $targetCurrency ?? currency_code());
    }

    /**
     * Format a stored course price amount in the target (or active display) currency.
     */
    public function formatPrice($amount, int $decimals = 2, string $style = 'code', ?string $targetCurrency = null): string
    {
        return currency_format((float) ($amount ?? 0), $decimals, $style, $this->getPriceCurrencyCode(), $targetCurrency);
    }

    /**
     * Accessor for computed Rating attribute.
     */
    public function getRatingAttribute()
    {
        // Calculate average rating from reviews if not cached
        return $this->reviews()->avg('rating') ?? 0.0;
    }

    /**
     * Accessor for computed Reviews count attribute.
     */
    public function getReviewsCountAttribute()
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return $this->attributes['reviews_count'];
        }
        return $this->reviews()->count();
    }

    /**
     * Accessor for computed Lessons count attribute.
     */
    public function getLessonsCountAttribute()
    {
        if (array_key_exists('lessons_count', $this->attributes)) {
            return $this->attributes['lessons_count'];
        }
        return $this->lessons()->count();
    }

    /**
     * Accessor for computed Students count attribute.
     */
    public function getStudentsCountAttribute()
    {
        // Total students enrolled (approved or completed)
        return $this->enrollments()->whereIn('status', ['approved', 'completed'])->count();
    }


    /**
     * Accessor for computed Intro video url attribute.
     */
    public function getIntroVideoUrlAttribute()
    {
        if (!$this->intro_video) {
            return null;
        }

        // Generate streaming URL with the video path
        return url('/api/video/stream?path=' . urlencode($this->intro_video));
    }

    /**
     * Eloquent belongsTo relationship for Default branch.
     */
    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    /**
     * Eloquent belongsTo relationship for Default venue.
     */
    public function defaultVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'default_venue_id');
    }

    /**
     * Eloquent hasMany relationship for Course assignments.
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * Eloquent hasMany relationship for Invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Eloquent hasMany relationship for Attendance records.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Check condition: Is online.
     */
    public function isOnline(): bool
    {
        return ($this->delivery_mode ?? 'online') === 'online';
    }

    /**
     * Check condition: Is face to face.
     */
    public function isFaceToFace(): bool
    {
        return $this->delivery_mode === 'face_to_face';
    }

    /**
     * Check condition: Is hybrid.
     */
    public function isHybrid(): bool
    {
        return $this->delivery_mode === 'hybrid';
    }

    /**
     * Requires applicant form.
     */
    public function requiresApplicantForm(): bool
    {
        return in_array($this->delivery_mode ?? 'online', ['face_to_face', 'hybrid'], true);
    }
}
