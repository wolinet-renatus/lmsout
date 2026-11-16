<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Language Model
 *
 * This model represents a language for courses.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class CourseLanguage extends Model
{
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Eloquent hasMany relationship for Courses.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'course_language_id');
    }
}
