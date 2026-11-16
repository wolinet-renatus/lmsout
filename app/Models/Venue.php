<?php

namespace App\Models;

use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venue Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Venue extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'branch_id',
        'name',
        'capacity',
        'equipment',
        'notes',
        'is_active',
        'legacy_scms_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Eloquent hasMany relationship for Course assignments.
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * Eloquent hasMany relationship for Enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
