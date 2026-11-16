<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Bundle course Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class BundleCourse extends Pivot
{
    protected $table = 'bundle_courses';

    protected $fillable = [
        'bundle_id',
        'course_id',
    ];
}
