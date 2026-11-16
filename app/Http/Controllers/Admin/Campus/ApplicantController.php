<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Venue;
use App\Services\CampusApplicationService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Registrar desk for Face-to-Face / Hybrid intake using the SCMS Applicants Form as-is.
 * @author Renatus Bernardo
 */
class ApplicantController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private CampusApplicationService $applications)
    {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $applicants = Applicant::with(['branch:id,name', 'enrollments.course:id,title', 'invoices:id,applicant_id,invoice_number,control_number,payment_status,balance'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where(fn ($w) => $w->where('full_name', 'like', $s)->orWhere('email', 'like', $s)->orWhere('phone', 'like', $s));
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.applicants.index', compact('applicants', 'branches'));
    }

    /**
     * Show the form for creating a new resource record.
     */
    public function create()
    {
        return view('admin.campus.applicants.create', $this->formData());
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request)
    {
        $data = $request->validate(CampusApplicationService::rules(false));

        try {
            $result = $this->applications->submit(
                $data,
                $request->only(['parent_staff_id', 'discount_attachment', 'receipt']),
                'admin',
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['course_id' => $e->getMessage()]);
        }

        $msg = 'Applicant registered. Invoice ' . $result['invoice']->invoice_number . ' / Control No. ' . $result['invoice']->control_number . '.';
        if ($result['created_user']) {
            $msg .= ' Temporary password: ' . $result['plain_password'] . ' (change required at first login).';
        }
        ToastMagic::success($msg);

        return redirect()->route('admin.campus.invoices.show', $result['invoice']);
    }

    /**
     * Display the specified resource details.
     */
    public function show(Applicant $applicant)
    {
        $applicant->load(['user', 'branch', 'country', 'enrollments.course', 'enrollments.venue', 'invoices.paymentSchedules', 'payments']);

        return view('admin.campus.applicants.show', compact('applicant'));
    }

    /**
     * JSON fee/duration/discount lookup used by the form's JS (replaces SCMS route fees.get).
     */
    public function courseFee(Course $course)
    {
        $weeks = 0;
        if ($course->duration_label && preg_match('/(\d+)/', $course->duration_label, $m)) {
            $weeks = (int) $m[1];
        }

        return response()->json([
            'fee' => (float) ($course->regular_price ?: $course->sale_price ?: 0),
            'duration' => (string) $weeks,
            'has_discount' => (bool) $course->has_discount,
            'discount' => (float) $course->discount_amount,
            'delivery_mode' => $course->delivery_mode,
            'branch_id' => $course->default_branch_id,
            'venue_id' => $course->default_venue_id,
            'is_basic_computer' => str_contains(strtolower($course->title), 'basic computer'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'countries' => Country::orderByRaw("CASE WHEN iso = 'TZ' THEN 0 ELSE 1 END")->orderBy('name')->get(['id', 'name', 'nicename']),
            'courses' => Course::whereIn('delivery_mode', ['face_to_face', 'hybrid'])
                ->where('status', 'published')->orderBy('title')->get(['id', 'title', 'regular_price', 'sale_price', 'delivery_mode']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'venues' => Venue::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']),
            'cohorts' => CourseAssignment::where('status', 'active')->with('course:id,title')->get(['id', 'course_id', 'session', 'start_date', 'venue_id', 'branch_id']),
            'educationLevels' => Applicant::educationLevels(),
            'durations' => Applicant::durations(),
            'discountReasons' => Applicant::discountReasons(),
        ];
    }
}
