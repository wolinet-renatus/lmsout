<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Country extends Model
{
    protected $fillable = [
        'code',
        'iso',
        'name',
        'nicename',
        'iso3',
        'phonecode',
    ];

    /**
     * Eloquent hasMany relationship for Get display name attribute.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nicename ?: $this->name;
    }

    /**
     * Eloquent hasMany relationship for Branches.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Eloquent hasMany relationship for Applicants.
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }
}
