<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\CourseAssignment;
use App\Services\CampusAttendanceService;
use App\Services\QrCodeService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance controller — manages campus & short-course operations within the admin portal.
 *
 * @author Renatus Bernardo
 */
class AttendanceController extends Controller
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
    public function index(Request $request)
    {
        $assignments = CourseAssignment::with(['course:id,title', 'instructor:id,name', 'branch:id,name', 'venue:id,name'])
            ->where('status', 'active')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->orderBy('start_date')
            ->get();

        $todayCount = AttendanceRecord::whereDate('attendance_date', now())->count();
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.attendance.index', compact('assignments', 'todayCount', 'branches'));
    }

    /**
     * Display the specified resource details.
     */
    public function show(Request $request, CourseAssignment $assignment)
    {
        $date = $request->input('date', now()->toDateString());
        $assignment->load(['course', 'instructor', 'branch', 'venue']);
        $roster = $this->attendance->roster($assignment);
        $existing = AttendanceRecord::withoutGlobalScope('branch')
            ->whereIn('enrollment_id', $roster->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');

        $qrToken = session('attendance_qr_' . $assignment->id);
        $qr = $qrToken ? QrCodeService::dataUri(route('student.campus.checkin', $qrToken), 260) : null;

        return view('admin.campus.attendance.show', compact('assignment', 'roster', 'existing', 'date', 'qr', 'qrToken'));
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request, CourseAssignment $assignment)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'marks' => ['required', 'array'],
        ]);

        $saved = $this->attendance->saveMarks($assignment, $data['date'], $data['marks'], $request->user());
        ToastMagic::success("Attendance saved for {$saved} student(s).");

        return redirect()->route('admin.campus.attendance.show', [$assignment, 'date' => $data['date']]);
    }

    /**
     * Open qr.
     */
    public function openQr(CourseAssignment $assignment)
    {
        $token = $this->attendance->openQrSession($assignment, 15);
        session()->put('attendance_qr_' . $assignment->id, $token);
        ToastMagic::success('QR check-in opened for 15 minutes.');

        return redirect()->route('admin.campus.attendance.show', $assignment);
    }

    /**
     * Export.
     */
    public function export(CourseAssignment $assignment): StreamedResponse
    {
        $assignment->load('course');
        $records = AttendanceRecord::withoutGlobalScope('branch')
            ->with('user:id,name,email')
            ->where('course_assignment_id', $assignment->id)
            ->orderBy('attendance_date')
            ->get();

        $filename = 'attendance-' . str($assignment->course?->title ?? 'class')->slug() . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Student', 'Email', 'Status', 'Method', 'Checked In At']);
            foreach ($records as $r) {
                fputcsv($out, [
                    optional($r->attendance_date)->toDateString(),
                    $r->user?->name,
                    $r->user?->email,
                    $r->status,
                    $r->check_in_method,
                    optional($r->checked_in_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
