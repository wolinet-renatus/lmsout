<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Force password controller — handles authentication, verification, and password security flows.
 *
 * @author Renatus Bernardo
 */
class ForcePasswordController extends Controller
{
    /**
     * Display the specified resource details.
     */
    public function show()
    {
        return view('auth.force-password');
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
            'force_password_reset' => false,
            'password_changed_at' => now(),
        ]);

        try {
            activity('security')
                ->causedBy($user)
                ->event('auth_password_changed')
                ->withProperties(['ip' => $request->ip()])
                ->log('User completed mandatory password change');
        } catch (\Throwable) {
        }

        ToastMagic::success('Your password has been updated.');

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isInstructor()) {
            return redirect()->route('instructor.dashboard');
        }

        return redirect()->route('student.dashboard');
    }
}
