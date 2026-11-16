<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Course co instructor Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CourseCoInstructor extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'revenue_share',
        'can_edit',
        'role',
    ];

    protected $casts = [
        'revenue_share' => 'decimal:2',
        'can_edit' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

