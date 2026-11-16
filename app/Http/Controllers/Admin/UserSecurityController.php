<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * SRS account controls merged into existing user management (block/unblock, unlock, force reset).
 * @author Renatus Bernardo
 */
class UserSecurityController extends Controller
{
    /**
     * Block.
     */
    public function block(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            ToastMagic::error('You cannot block your own account.');
            return back();
        }

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $user->forceFill([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $data['reason'] ?? 'Blocked by administrator',
            'active_session_id' => null,
        ])->save();

        ToastMagic::success($user->name . ' has been blocked.');

        return back();
    }

    /**
     * Unblock.
     */
    public function unblock(User $user)
    {
        $user->forceFill([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
            'failed_login_attempts' => 0,
            'lockout_count' => 0,
            'locked_until' => null,
        ])->save();

        ToastMagic::success($user->name . ' has been unblocked.');

        return back();
    }

    /**
     * Force reset.
     */
    public function forceReset(User $user)
    {
        $user->forceFill(['force_password_reset' => true])->save();
        ToastMagic::success($user->name . ' must change password at next login.');

        return back();
    }
}
