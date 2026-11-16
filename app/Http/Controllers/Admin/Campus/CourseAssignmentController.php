<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use App\Models\Venue;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Timetable: assigns tutor + venue + session + dates to a Face-to-Face / Hybrid course cohort.
 * @author Renatus Bernardo
 */
class CourseAssignmentController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $assignments = CourseAssignment::with(['course:id,title,delivery_mode', 'instructor:id,name', 'branch:id,name', 'venue:id,name'])
            ->withCount('enrollments')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('instructor_id'), fn ($q) => $q->where('instructor_id', $request->instructor_id))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $instructors = User::where('role', 'instructor')->orderBy('name')->get(['id', 'name']);

        return view('admin.campus.timetable.index', compact('assignments', 'branches', 'instructors'));
    }

    /**
     * Show the form for creating a new resource record.
     */
    public function create()
    {
        return view('admin.campus.timetable.form', $this->formData(new CourseAssignment(['status' => 'active', 'session' => 'morning'])));
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($conflict = $this->venueConflict($data)) {
            return back()->withInput()->withErrors(['venue_id' => $conflict]);
        }

        CourseAssignment::create($data);
        ToastMagic::success('Timetable entry created.');

        return redirect()->route('admin.campus.timetable.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseAssignment $timetable)
    {
        return view('admin.campus.timetable.form', $this->formData($timetable));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, CourseAssignment $timetable)
    {
        $data = $this->validated($request);
        if ($conflict = $this->venueConflict($data, $timetable->id)) {
            return back()->withInput()->withErrors(['venue_id' => $conflict]);
        }

        $timetable->update($data);
        ToastMagic::success('Timetable entry updated.');

        return redirect()->route('admin.campus.timetable.index');
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(CourseAssignment $timetable)
    {
        if ($timetable->enrollments()->exists()) {
            ToastMagic::error('Students are linked to this cohort. Mark it completed or cancelled instead.');
            return back();
        }

        $timetable->delete();
        ToastMagic::success('Timetable entry deleted.');

        return back();
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(CourseAssignment $timetable)
    {
        $next = $timetable->status === 'active' ? 'completed' : 'active';
        $timetable->update(['status' => $next]);

        ToastMagic::success($next === 'completed'
            ? 'Class cohort marked as completed.'
            : 'Class cohort reactivated.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(CourseAssignment $assignment): array
    {
        return [
            'assignment' => $assignment,
            'courses' => Course::whereIn('delivery_mode', ['face_to_face', 'hybrid'])->orderBy('title')->get(['id', 'title', 'delivery_mode']),
            'instructors' => User::where('role', 'instructor')->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'venues' => Venue::where('is_active', true)->with('branch:id,name')->orderBy('name')->get(['id', 'name', 'branch_id', 'capacity']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'session' => ['required', 'in:morning,afternoon,evening,weekend'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,completed,cancelled'],
        ]);
        $data['schedule_days'] = $data['schedule_days'] ?? [];

        return $data;
    }

    /**
     * Prevent double-booking a venue for the same session over overlapping dates.
     */
    private function venueConflict(array $data, ?int $ignoreId = null): ?string
    {
        if (empty($data['venue_id']) || ($data['status'] ?? 'active') !== 'active') {
            return null;
        }

        $end = $data['end_date'] ?? $data['start_date'];
        $conflict = CourseAssignment::withoutGlobalScope('branch')
            ->where('venue_id', $data['venue_id'])
            ->where('session', $data['session'])
            ->where('status', 'active')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $data['start_date']))
            ->with('course:id,title')
            ->first();

        return $conflict
            ? 'Venue already booked for the ' . ucfirst($data['session']) . ' session by "' . ($conflict->course?->title ?? 'another course') . '" in these dates.'
            : null;
    }
}
