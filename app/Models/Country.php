<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getDisplayNameAttribute(): string
    {
        return $this->nicename ?: $this->name;
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }
}
