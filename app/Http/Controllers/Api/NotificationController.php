<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Notification controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark as read.
     */
    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all read.
     */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy($id, Request $request)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }
}
