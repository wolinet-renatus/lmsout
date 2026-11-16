<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Habit log Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class HabitLog extends Model
{
    protected $fillable = [
        'habit_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
    ];

    /**
     * Eloquent belongsTo relationship for Habit.
     */
    public function habit()
    {
        return $this->belongsTo(Habit::class);
    }
}
