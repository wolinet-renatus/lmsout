<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\Venue;
use App\Services\CampusApplicationService;
use App\Services\QrCodeService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Course apply controller — handles public storefront and learner intake workflows.
 *
 * @author Renatus Bernardo
 */
class CourseApplyController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private CampusApplicationService $applications)
    {
    }

    /**
     * Display the specified resource details.
     */
    public function show(Course $course)
    {
        if ($course->status !== 'published' || $course->visibility !== 'public') {
            ToastMagic::error('This course is not available for applications.');
            return redirect()->route('courses');
        }

        if ($course->isOnline()) {
            return redirect()->route('courses.checkout', $course->id);
        }

        $user = Auth::user();
        $prefill = $user ? [
            'full_name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->phone,
        ] : [];

        return view('courses.apply', [
            'selectedCourse' => $course,
            'prefill' => $prefill,
            'countries' => Country::orderByRaw("CASE WHEN iso = 'TZ' THEN 0 ELSE 1 END")->orderBy('name')->get(['id', 'name', 'nicename']),
            'courses' => Course::whereIn('delivery_mode', ['face_to_face', 'hybrid'])
                ->where('status', 'published')->where('visibility', 'public')->orderBy('title')->get(['id', 'title']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'venues' => Venue::withoutGlobalScope('branch')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']),
            'educationLevels' => Applicant::educationLevels(),
            'durations' => Applicant::durations(),
            'discountReasons' => Applicant::discountReasons(),
        ]);
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request, Course $course)
    {
        if ($course->isOnline()) {
            return redirect()->route('courses.checkout', $course->id);
        }

        $data = $request->validate(CampusApplicationService::rules(true));

        try {
            $result = $this->applications->submit(
                $data,
                $request->only(['parent_staff_id', 'discount_attachment', 'receipt']),
                'public',
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['mobile' => $e->getMessage()]);
        }

        if ($result['created_user'] && ! Auth::check()) {
            Auth::login($result['user'], true);
            $request->session()->put('generated_campus_password', $result['plain_password']);
        }

        $invoice = $result['invoice'];
        $request->session()->put('last_campus_invoice_id', $invoice->id);

        $enrolledCourse = $invoice->course ?? $course;

        if (($data['payment_option'] ?? 'pay_later') === 'online' && $enrolledCourse->allow_online_f2f_payment && $invoice->balance > 0) {
            ToastMagic::success('Application recorded! Proceed to pay your first installment online.');
            return redirect()->route('courses.checkout', ['id' => $enrolledCourse->id, 'invoice_id' => $invoice->id]);
        }

        ToastMagic::success('Application submitted! Invoice ' . $invoice->invoice_number . ' and Control Number ' . $invoice->control_number . ' are ready.');

        return redirect()->route('courses.apply.confirmation', $invoice->id);
    }

    /**
     * Confirmation.
     */
    public function confirmation(Request $request, Invoice $invoice)
    {
        $authorized = ($request->session()->get('last_campus_invoice_id') == $invoice->id)
            || (Auth::check() && (int) Auth::id() === (int) $invoice->user_id);

        if (! $authorized) {
            abort(403);
        }

        $invoice->load(['course', 'branch', 'enrollment.venue', 'paymentSchedules', 'payments']);
        $qr = QrCodeService::dataUri(route('campus.invoice.verify', ['number' => $invoice->invoice_number]), 140);

        return view('courses.apply-confirmation', compact('invoice', 'qr'));
    }

    public function print(Request $request, Invoice $invoice)
    {
        $authorized = ($request->session()->get('last_campus_invoice_id') == $invoice->id)
            || (Auth::check() && ((int) Auth::id() === (int) $invoice->user_id || Auth::user()->isAdmin()));

        if (! $authorized) {
            abort(403);
        }

        $invoice->load(['user', 'applicant', 'course', 'branch', 'enrollment.venue', 'paymentSchedules', 'payments']);
        $qr = QrCodeService::dataUri(route('campus.invoice.verify', ['number' => $invoice->invoice_number]), 140);

        return view('campus.invoice-print', compact('invoice', 'qr'));
    }

    /**
     * Verify invoice.
     */
    public function verifyInvoice(string $number)
    {
        $invoice = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title', 'branch:id,name', 'user:id,name'])
            ->where(function ($q) use ($number) {
                $q->where('invoice_number', $number)
                    ->orWhere('control_number', $number);
            })
            ->firstOrFail();

        return view('courses.verify-invoice', compact('invoice'));
    }

    /**
     * Course fee.
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
}
