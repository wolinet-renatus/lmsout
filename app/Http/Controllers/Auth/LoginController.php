<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RecaptchaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login Controller
 *
 * This controller handles the login functionality.
 *
 * @package App\Http\Controllers\Auth
 * @author Renatus Bernardo
 */
class LoginController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private RecaptchaService $recaptcha, private OtpService $otpService)
    {
    }

    /**
     * Display the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     * 
     */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->recaptcha->enabled() && !$this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip(), 'login')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Recaptcha verification failed. Please try again.',
            ])->withInput();
        }

        /** @var User|null $existingUser */
        $existingUser = User::where('email', $credentials['email'])->first();

        if ($existingUser) {
            if ($existingUser->is_blocked) {
                return back()->withErrors([
                    'email' => 'Your account has been suspended. Please contact an administrator to unblock it.',
                ])->onlyInput('email');
            }

            if (settings('authentication.security.srs_lockout_enabled', true) && $existingUser->locked_until && $existingUser->locked_until->isFuture()) {
                $minutesLeft = max(1, (int) now()->diffInMinutes($existingUser->locked_until, false));
                return back()->withErrors([
                    'email' => "Account temporarily locked due to repeated failed attempts. Try again in {$minutesLeft} minute(s).",
                ])->onlyInput('email');
            }
        }

        if (!Auth::validate($credentials)) {
            if ($existingUser && settings('authentication.security.srs_lockout_enabled', true)) {
                $maxAttempts = max(2, (int) settings('authentication.security.srs_max_attempts', 3));
                $attempts = (int) $existingUser->failed_login_attempts + 1;
                $updates = ['failed_login_attempts' => $attempts];

                if ($attempts >= $maxAttempts) {
                    $lockCount = (int) $existingUser->lockout_count + 1;
                    $updates['failed_login_attempts'] = 0;
                    $updates['lockout_count'] = $lockCount;
                    $updates['locked_until'] = now()->addMinutes(5 * $lockCount);

                    if ($lockCount >= 3) {
                        $updates['is_blocked'] = true;
                        $updates['blocked_at'] = now();
                        $updates['blocked_reason'] = 'Auto-blocked after repeated login lockouts.';
                    }
                }

                $existingUser->forceFill($updates)->saveQuietly();

                try {
                    activity('security')
                        ->causedBy($existingUser)
                        ->event(! empty($updates['is_blocked']) ? 'auth_account_blocked' : (! empty($updates['locked_until']) ? 'auth_account_locked' : 'auth_failed_login'))
                        ->withProperties(['ip' => $request->ip(), 'email' => $credentials['email']])
                        ->log(! empty($updates['is_blocked']) ? 'Account auto-blocked after repeated lockouts' : (! empty($updates['locked_until']) ? 'Account temporarily locked after failed login attempts' : 'Failed login attempt'));
                } catch (\Throwable) {
                }
            }

            ToastMagic::warning('Invalid credentials. Please try again.');

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        /** @var User|null $user */
        $user = $existingUser ?? User::where('email', $credentials['email'])->first();

        if (!$user) {
            ToastMagic::error('Authentication failed. Please try again.');

            return back()->withErrors([
                'email' => 'Authentication failed.',
            ])->onlyInput('email');
        }

        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ToastMagic::error('Your email is not verified. A new verification link has been sent to your email.');
            return back()->withErrors([
                'email' => 'Your email is not verified. A new verification link has been sent to your email.',
            ])->onlyInput('email');
        }

        if ($this->otpService->enabled()) {
            $this->otpService->send($user);

            $request->session()->put('otp_login_user_id', $user->id);
            $request->session()->put('otp_login_remember', $request->filled('remember'));

            ToastMagic::info('Enter the verification code we sent to your email.');

            return redirect()->route('login.otp.show');
        }

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->forget('impersonating_admin_id');
            $request->session()->forget('impersonating_instructor_id');
            $request->session()->forget('impersonating_student_id');

            $request->session()->regenerate();


            $user = Auth::user();

            if (!$user) {
                ToastMagic::error('Authentication failed. Please try again.');
                return back()->withErrors(['email' => 'Authentication failed.'])->onlyInput('email');
            }

            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'active_session_id' => $request->session()->getId(),
                'last_activity_at' => now(),
            ])->saveQuietly();
            $request->session()->put('last_activity_ts', now()->timestamp);

            try {
                activity('security')
                    ->causedBy($user)
                    ->event('auth_login')
                    ->withProperties(['ip' => $request->ip(), 'role' => $user->primaryRoleLabel()])
                    ->log('User logged in successfully');
            } catch (\Throwable) {
            }

            if ($user->force_password_reset) {
                ToastMagic::info('Please update your default password before continuing.');
                return redirect()->route('password.force_change');
            }

            ToastMagic::success('Welcome back! ' . $user->name);

            /** @var User $user */

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isInstructor()) {
                return redirect()->route('instructor.dashboard');
            } else {
                return redirect()->route('student.dashboard');
            }
        }

        ToastMagic::warning('Invalid credentials. Please try again.');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->forceFill(['active_session_id' => null])->saveQuietly();
        }

        $request->session()->forget('impersonating_admin_id');
        $request->session()->forget('impersonating_instructor_id');
        $request->session()->forget('impersonating_student_id');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        ToastMagic::info('You have been logged out.');

        return redirect()->route('home');
    }
}

