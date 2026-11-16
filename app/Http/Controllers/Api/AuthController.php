<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use App\Services\OtpService;
use App\Mail\WelcomeMail;
use App\Mail\AdminNewRegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Auth controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class AuthController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        private OtpService $otpService,
        private NotificationPreferenceService $notificationPreferenceService
    ) {
    }

    /**
     * Register bindings and service container definitions.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'terms' => 'accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'is_approved' => true,
            ]);

            // Initialize notification settings
            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            // Send emails (Welcome & Admin Notification)
            $emailPreferences = settings('email.preferences', []);

            try {
                if (!empty($emailPreferences['send_welcome_email'])) {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                }

                if (!empty($emailPreferences['notify_admin_new_registration'])) {
                    $adminEmail = settings('contact.details.support_email', config('mail.from.address'));
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new AdminNewRegistrationMail($user));
                    }
                }
            } catch (\Exception $e) {
                // Log email errors but don't fail registration
                \Log::error('Registration email failed: ' . $e->getMessage());
            }

            $token = null;
            $message = 'Registration successful';

            if (settings('authentication.security.email_verification_required', false)) {
                $user->sendEmailVerificationNotification();
                $message = 'Registration successful. Please verify your email address.';
                // Return no token so user must login after verifying
            } else {
                $user->markEmailAsVerified(); // Ensure verified if setting is off, or let it handle naturally
                $token = $user->createToken('auth_token')->plainTextToken;
            }

            return response()->json([
                'message' => $message,
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->is_blocked) {
            return response()->json([
                'message' => 'Your account has been blocked. ' . ($user->blocked_reason ?: 'Please contact an administrator.'),
                'is_blocked' => true,
            ], 403);
        }

        if ($user && settings('authentication.security.srs_lockout_enabled', true) && $user->locked_until && $user->locked_until->isFuture()) {
            $minutes = max(1, (int) now()->diffInMinutes($user->locked_until, false) + 1);
            return response()->json([
                'message' => "Account temporarily locked due to failed attempts. Try again in {$minutes} minute(s).",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 423);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user && settings('authentication.security.srs_lockout_enabled', true)) {
                $maxAttempts = max(2, (int) settings('authentication.security.srs_max_attempts', 3));
                $attempts = (int) $user->failed_login_attempts + 1;
                $updates = ['failed_login_attempts' => $attempts];

                if ($attempts >= $maxAttempts) {
                    $lockCount = (int) $user->lockout_count + 1;
                    $updates['failed_login_attempts'] = 0;
                    $updates['lockout_count'] = $lockCount;
                    $updates['locked_until'] = now()->addMinutes(5 * $lockCount);
                    if ($lockCount >= 3) {
                        $updates['is_blocked'] = true;
                        $updates['blocked_at'] = now();
                        $updates['blocked_reason'] = 'Auto-blocked after repeated login lockouts.';
                    }
                }
                $user->forceFill($updates)->saveQuietly();
            }

            return response()->json([
                'message' => 'Invalid login credentials',
            ], 401);
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_activity_at' => now(),
        ])->saveQuietly();

        // Check for Email Verification
        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Your email is not verified. A new verification link has been sent to your email.',
                'email_unverified' => true
            ], 403);
        }

        if ($this->otpService->enabled()) {
            $this->otpService->send($user);

            return response()->json([
                'message' => 'OTP sent to your email',
                'otp_required' => true,
                'email' => $user->email
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'otp_required' => false,
            'force_password_reset' => $user->needsPasswordReset(),
        ]);
    }

    /**
     * Verify otp.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$this->otpService->verify($user, $request->code)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $this->otpService->clear($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Resend otp.
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $this->otpService->send($user);

        return response()->json(['message' => 'OTP resent successfully']);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * User.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
