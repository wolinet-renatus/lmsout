<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CourseAssignment;
use App\Services\CampusAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campus controller API Controller — handles instructor mobile/client REST endpoints.
 *
 * @author Renatus Bernardo
 */
class CampusController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private CampusAttendanceService $attendance)
    {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = CourseAssignment::withoutGlobalScope('branch')
            ->with(['course:id,title,delivery_mode,attendance_threshold', 'branch:id,name', 'venue:id,name'])
            ->withCount('enrollments')
            ->where('instructor_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Roster.
     */
    public function roster(Request $request, CourseAssignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);

        $date = $request->input('date', now()->toDateString());
        $assignment->load(['course:id,title,delivery_mode,attendance_threshold', 'branch:id,name', 'venue:id,name']);
        $roster = $this->attendance->roster($assignment);
        $existing = AttendanceRecord::withoutGlobalScope('branch')
            ->whereIn('enrollment_id', $roster->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
                'date' => $date,
                'roster' => $roster,
                'existing_marks' => $existing,
            ],
        ]);
    }

    /**
     * Store attendance.
     */
    public function storeAttendance(Request $request, CourseAssignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'marks' => ['required', 'array'],
        ]);

        $saved = $this->attendance->saveMarks($assignment, $data['date'], $data['marks'], $request->user());

        return response()->json([
            'success' => true,
            'message' => "Attendance saved for {$saved} student(s).",
            'saved_count' => $saved,
        ]);
    }

    /**
     * Open qr.
     */
    public function openQr(Request $request, CourseAssignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);
        $token = $this->attendance->openQrSession($assignment, 15);

        return response()->json([
            'success' => true,
            'message' => 'QR check-in opened for 15 minutes.',
            'data' => [
                'token' => $token,
                'checkin_url' => route('student.campus.checkin', $token),
                'expires_in_minutes' => 15,
            ],
        ]);
    }
}
