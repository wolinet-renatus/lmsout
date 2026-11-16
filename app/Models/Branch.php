<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_code',
        'email',
        'phone',
        'address',
        'country_id',
        'is_active',
        'legacy_scms_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for Country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Eloquent hasMany relationship for Venues.
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * Eloquent hasMany relationship for Users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Eloquent hasMany relationship for Applicants.
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    /**
     * Eloquent hasMany relationship for Enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
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
        return $this->hasMany(CourseAssignment::class);
    }
}
