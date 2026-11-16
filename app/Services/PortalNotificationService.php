<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Portal notification service — centralizes internal user notifications and login event alerts.
 *
 * @author Renatus Bernardo
 */
class PortalNotificationService
{
    /**
     * Dispatch a database notification to a user with optional deduplication window.
     */
    public function notifyUser(
        ?User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $dedupeTitle = null,
        int $dedupeMinutes = 0
    ): bool {
        if (! $user || ! $this->notificationsTableReady()) {
            return false;
        }

        try {
            if ($dedupeMinutes > 0) {
                $targetTitle = $dedupeTitle ?? $title;
                $alreadySent = $user->notifications()
                    ->where('created_at', '>=', now()->subMinutes($dedupeMinutes))
                    ->limit(25)
                    ->get()
                    ->contains(fn ($n) => ($n->data['title'] ?? '') === $targetTitle);

                if ($alreadySent) {
                    return false;
                }
            }

            $user->notify(new GeneralNotification($title, $message, $type, null, $url));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Handle post-login internal notifications (session security notice, fee balance, unread inbox).
     */
    public function handleLogin(User $user): void
    {
        if (! $this->notificationsTableReady()) {
            return;
        }

        $dashboardUrl = $this->dashboardRouteFor($user);

        $this->notifyUser(
            $user,
            'Signed In to OUT LMS',
            'Your session started at ' . now()->format('M d, Y · h:i A') . '. Review recent activity and learning updates.',
            'info',
            $dashboardUrl,
            'Signed In to OUT LMS',
            720
        );

        // Remind students of any outstanding invoice balances upon login
        if (! $user->isAdmin() && ! $user->isInstructor() && Schema::hasTable('invoices')) {
            try {
                $outstanding = (float) Invoice::withoutGlobalScope('branch')
                    ->ownedByUser($user)
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->sum('balance');

                if ($outstanding > 0 && Route::has('student.campus.invoices.index')) {
                    $formatted = function_exists('currency_format')
                        ? currency_format($outstanding)
                        : number_format($outstanding, 0);

                    $this->notifyUser(
                        $user,
                        'Outstanding Fee Balance',
                        "You have an unpaid invoice balance of {$formatted}. View invoices or submit a payment receipt.",
                        'warning',
                        route('student.campus.invoices.index'),
                        'Outstanding Fee Balance',
                        720
                    );
                }
            } catch (\Throwable) {
            }
        }

        // Remind student or instructor if unread messages are waiting in inbox
        $role = $user->isInstructor() ? 'instructor' : (! $user->isAdmin() ? 'student' : null);
        if ($role && Schema::hasTable('conversations') && Schema::hasTable('chat_messages')) {
            try {
                $column = $role === 'instructor' ? 'instructor_id' : 'student_id';
                $unreadMsgs = Conversation::where($column, $user->id)
                    ->where('is_closed', false)
                    ->whereHas('messages', fn ($q) => $q->where('sender_id', '!=', $user->id)->where('is_read', false))
                    ->count();

                $inboxRoute = $role === 'instructor' ? 'instructor.messages.index' : 'student.messages.index';

                if ($unreadMsgs > 0 && Route::has($inboxRoute)) {
                    $this->notifyUser(
                        $user,
                        'Unread Portal Messages',
                        "You have unread messages waiting across {$unreadMsgs} active conversation(s).",
                        'info',
                        route($inboxRoute),
                        'Unread Portal Messages',
                        360
                    );
                }
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Resolve landing dashboard URL based on user role.
     */
    private function dashboardRouteFor(User $user): ?string
    {
        if ($user->isAdmin() && Route::has('admin.dashboard')) {
            return route('admin.dashboard');
        }

        if ($user->isInstructor() && Route::has('instructor.dashboard')) {
            return route('instructor.dashboard');
        }

        return Route::has('student.dashboard') ? route('student.dashboard') : null;
    }

    /**
     * Check whether notifications storage table exists safely.
     */
    private function notificationsTableReady(): bool
    {
        try {
            return Schema::hasTable('notifications');
        } catch (\Throwable) {
            return false;
        }
    }
}
