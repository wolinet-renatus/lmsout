<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Concerns\HandlesPortalNotifications;
use App\Http\Controllers\Controller;

/**
 * Notification controller — handles instructor studio workflows and cohort delivery.
 *
 * @author Renatus Bernardo
 */
class NotificationController extends Controller
{
    use HandlesPortalNotifications;

    /**
     * Display a listing of the resource records.
     */
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(15);

        return view('instructor.notifications.index', compact('notifications', 'user'));
    }

    /**
     * Portal role key used by notification routes.
     */
    protected function portalNotificationRole(): string
    {
        return 'instructor';
    }
}
