<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\HabitLog;

/**
 * Habit Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class Habit extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'target_days_per_week',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eloquent hasMany relationship for Logs.
     */
    public function logs()
    {
        return $this->hasMany(HabitLog::class);
    }
}
