<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

