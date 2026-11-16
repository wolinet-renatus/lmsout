<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * Ai chat message Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class AiChatMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'is_ai'];

    /**
     * Eloquent belongsTo relationship for User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
