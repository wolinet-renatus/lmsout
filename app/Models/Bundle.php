<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bundle Model
 *
 * This model represents a course bundle, sold together.
 * @author Renatus Bernardo
 */
class Bundle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'title',
        'description',
        'featured_image',
        'price',
        'status',          // 'active', 'inactive'
        'approval_status', // 'pending', 'approved', 'rejected'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'thumbnail',
        'image',
    ];

    /**
     * Get the vendor (admin or instructor) that created the bundle.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the courses that are part of this bundle.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'bundle_courses');
    }

    /**
     * Get the enrollments for this bundle.
     */
    public function bundleEnrollments(): HasMany
    {
        return $this->hasMany(BundleEnrollment::class);
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
     * Accessor for computed Image attribute.
     */
    public function getImageAttribute()
    {
        return $this->thumbnail;
    }

}
