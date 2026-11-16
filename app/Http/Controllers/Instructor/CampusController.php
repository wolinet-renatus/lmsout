<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Services\CampusAttendanceService;
use App\Services\QrCodeService;
use App\Services\SrsWorkflowService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Campus controller — handles instructor studio workflows and cohort delivery.
 *
 * @author Renatus Bernardo
 */
class CampusController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        private CampusAttendanceService $attendance,
        private SrsWorkflowService $srs
    ) {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $tutor = $request->user();
        $scope = $request->query('scope', 'mine');

        $assignments = CourseAssignment::withoutGlobalScope('branch')
            ->with(['course:id,title,delivery_mode,attendance_threshold', 'branch:id,name', 'venue:id,name', 'instructor:id,name'])
            ->withCount('enrollments')
            ->when($scope === 'branch' && $tutor->branch_id, fn ($q) => $q->where('branch_id', $tutor->branch_id))
            ->when($scope !== 'branch' || ! $tutor->branch_id, fn ($q) => $q->where('instructor_id', $tutor->id))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        $myCourseIds = CourseAssignment::withoutGlobalScope('branch')
            ->where('instructor_id', $tutor->id)
            ->pluck('course_id')
            ->filter()
            ->unique();

        $readyCertificatesCount = Enrollment::withoutGlobalScope('branch')
            ->whereIn('course_id', $myCourseIds)
            ->where('certificate_status', 'ready')
            ->count();

        return view('instructor.campus.index', compact('assignments', 'scope', 'readyCertificatesCount'));
    }

    /**
     * Per-center enrolled students roster with Paid vs Unpaid / Certificate Readiness filters (SRS R6).
     */
    public function roster(Request $request, CourseAssignment $assignment)
    {
        $user = $request->user();
        abort_unless(
            (int) $assignment->instructor_id === (int) $user->id
            || ($user->branch_id && (int) $assignment->branch_id === (int) $user->branch_id),
            403
        );

        $paymentFilter = $request->query('payment', 'all');
        $assignment->load(['course', 'branch', 'venue']);

        $roster = Enrollment::withoutGlobalScope('branch')
            ->with(['user:id,name,email,phone,registration_number', 'applicant:id,full_name,email,phone', 'invoice:id,enrollment_id,invoice_number,net_amount,paid_amount,balance,payment_status'])
            ->where(function ($q) use ($assignment) {
                $q->where('course_assignment_id', $assignment->id)
                    ->orWhere(fn ($w) => $w->where('course_id', $assignment->course_id)->where('branch_id', $assignment->branch_id));
            })
            ->when($paymentFilter === 'paid', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('payment_status', 'paid')))
            ->when($paymentFilter === 'partial', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('payment_status', 'partial')))
            ->when($paymentFilter === 'unpaid', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('payment_status', 'unpaid')))
            ->latest('id')
            ->get();

        $roster->each(function (Enrollment $e) {
            $e->setAttribute('is_cert_eligible', $this->srs->isCertificateEligible($e));
        });

        return view('instructor.campus.roster', compact('assignment', 'roster', 'paymentFilter'));
    }

    /**
     * Show attendance.
     */
    public function showAttendance(Request $request, CourseAssignment $assignment)
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);

        $date = $request->input('date', now()->toDateString());
        $assignment->load(['course', 'branch', 'venue']);
        $roster = $this->attendance->roster($assignment);
        $existing = AttendanceRecord::withoutGlobalScope('branch')
            ->whereIn('enrollment_id', $roster->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');

        $qrToken = session('attendance_qr_' . $assignment->id);
        $qr = $qrToken ? QrCodeService::dataUri(route('student.campus.checkin', $qrToken), 240) : null;

        return view('instructor.campus.attendance', compact('assignment', 'roster', 'existing', 'date', 'qr', 'qrToken'));
    }

    /**
     * Store attendance.
     */
    public function storeAttendance(Request $request, CourseAssignment $assignment)
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'marks' => ['required', 'array'],
        ]);

        $saved = $this->attendance->saveMarks($assignment, $data['date'], $data['marks'], $request->user());
        ToastMagic::success("Attendance saved for {$saved} student(s).");

        return redirect()->route('instructor.campus.attendance', [$assignment, 'date' => $data['date']]);
    }

    /**
     * Open qr.
     */
    public function openQr(Request $request, CourseAssignment $assignment)
    {
        abort_unless((int) $assignment->instructor_id === (int) $request->user()->id, 403);

        $token = $this->attendance->openQrSession($assignment, 15);
        session()->put('attendance_qr_' . $assignment->id, $token);
        ToastMagic::success('QR check-in opened for 15 minutes.');

        return redirect()->route('instructor.campus.attendance', $assignment);
    }
}
