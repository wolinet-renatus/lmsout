<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Course faq Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class CourseFaq extends Model
{
    protected $fillable = [
        'course_id',
        'question',
        'answer',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Eloquent belongsTo relationship for Course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}

