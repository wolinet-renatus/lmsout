<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce SRS Security Middleware
 *
 * Implements institutional session and password hygiene controls:
 * 1. Immediate termination for suspended (`is_blocked`) accounts.
 * 2. Optional single-active-session enforcement across devices.
 * 3. Configurable inactivity/idle timeout logout.
 * 4. Forced first-login password reset and periodic password expiry checks.
 *
 * @package App\Http\Middleware
 * @author Renatus Bernardo
 */
class EnforceSrsSecurity
{
    /**
     * Validate active session, account status, idle timeout, and password compliance.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $session = $request->session();

        // 1. Blocked Account Enforcement
        if ($user->is_blocked) {
            $this->logSecurityEvent($user, 'auth_blocked_session', 'Blocked user session terminated', $request);
            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been suspended. Please contact the administrator.',
            ]);
        }

        // Skip session/password interlocks while admin impersonates another user
        $isImpersonating = $session->has('impersonating_admin_id')
            || $session->has('impersonating_instructor_id')
            || $session->has('impersonating_student_id');

        // 2. Single Active Session Enforcement (configurable)
        if (! $isImpersonating && settings('authentication.security.srs_single_session_enabled', false)) {
            $currentSessionId = $session->getId();
            if ($user->active_session_id && $user->active_session_id !== $currentSessionId) {
                $this->logSecurityEvent($user, 'auth_concurrent_session_terminated', 'Concurrent session displaced by newer login', $request);
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your session was signed out because this account logged in from another device.',
                ]);
            }
        }

        // 3. Configurable Inactivity / Idle Timeout (SRS Section 5.1.4: default 5 minutes)
        $idleMinutes = (int) settings('authentication.security.srs_idle_timeout_minutes', 5);
        if (! $isImpersonating && $idleMinutes > 0) {
            $lastActivityTs = (int) $session->get('last_activity_ts', 0);
            $nowTs = now()->timestamp;

            if ($lastActivityTs > 0 && ($nowTs - $lastActivityTs) > ($idleMinutes * 60)) {
                if ($user->active_session_id === $session->getId()) {
                    $user->forceFill(['active_session_id' => null])->saveQuietly();
                }
                $this->logSecurityEvent($user, 'auth_session_idle_timeout', "Session expired after {$idleMinutes} minutes of inactivity", $request);
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => "Your session expired after {$idleMinutes} minutes of inactivity. Please sign in again.",
                ]);
            }

            $session->put('last_activity_ts', $nowTs);
        }

        // Update lightweight last_activity_at timestamp at most once per minute
        if (! $user->last_activity_at || $user->last_activity_at->lt(now()->subMinute())) {
            $user->forceFill(['last_activity_at' => now()])->saveQuietly();
        }

        // 4. Forced Password Reset or Expiry Enforcement (default 90 days when configured)
        if (! $isImpersonating && ! $request->routeIs('password.force_change', 'password.force_change.update', 'logout')) {
            $expiryDays = (int) settings('authentication.security.srs_password_expiry_days', 0);
            $expired = $expiryDays > 0
                && $user->password_changed_at
                && $user->password_changed_at->lt(now()->subDays($expiryDays));

            if ($user->force_password_reset || $expired) {
                return redirect()->route('password.force_change');
            }
        }

        return $next($request);
    }

    /**
     * Log security event.
     */
    private function logSecurityEvent($user, string $event, string $description, Request $request): void
    {
        try {
            if (function_exists('activity')) {
                activity('security')
                    ->causedBy($user)
                    ->event($event)
                    ->withProperties(['ip' => $request->ip(), 'event' => $event])
                    ->log($description);
            }
        } catch (\Throwable) {
            // Never break request flow if audit table is unavailable
        }
    }
}
