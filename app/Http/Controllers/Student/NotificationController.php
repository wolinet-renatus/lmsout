<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\HandlesPortalNotifications;
use App\Http\Controllers\Controller;

/**
 * Notification controller — handles student campus portal workflows and learning progress.
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

        return view('student.notifications.index', compact('notifications', 'user'));
    }

    /**
     * Portal role key used by notification routes.
     */
    protected function portalNotificationRole(): string
    {
        return 'student';
    }
}
