<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ApplicantReportExport;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reports Controller
 *
 * This controller handles the management of reports.
 *
 * @package App\Http\Controllers\Admin
 * @author Renatus Bernardo
 */
class ReportsController extends Controller
{
    /**
     * Display the reports index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfPreviousMonth = $startOfMonth->copy()->subMonth();
        $endOfPreviousMonth = $startOfPreviousMonth->copy()->endOfMonth();

        $completedPaymentsQuery = Payment::query()->where('status', Payment::STATUS_COMPLETED);

        $financials = [
            'total_revenue' => (clone $completedPaymentsQuery)->sum('amount'),
            'total_instructor' => (clone $completedPaymentsQuery)->sum('instructor_earning'),
            'total_platform' => (clone $completedPaymentsQuery)->sum('platform_commission'),
            'monthly_revenue' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('amount'),
            'monthly_instructor' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('instructor_earning'),
            'monthly_platform' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('platform_commission'),
            'previous_month_revenue' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfPreviousMonth, $endOfPreviousMonth])->sum('amount'),
        ];

        $financials['revenue_growth'] = $financials['previous_month_revenue'] > 0
            ? (($financials['monthly_revenue'] - $financials['previous_month_revenue']) / $financials['previous_month_revenue']) * 100
            : null;

        $enrollmentStats = [
            'total_enrollments' => Enrollment::count(),
            'completed_enrollments' => Enrollment::completed()->count(),
            'new_enrollments_month' => Enrollment::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
        ];

        $enrollmentStats['completion_rate'] = $enrollmentStats['total_enrollments'] > 0
            ? ($enrollmentStats['completed_enrollments'] / $enrollmentStats['total_enrollments']) * 100
            : 0;

        $studentStats = [
            'total_students' => User::where('role', 'student')->count(),
            'new_students_month' => User::where('role', 'student')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            'total_instructors' => User::where('role', 'instructor')->count(),
            'approved_instructors' => User::where('role', 'instructor')->where('is_approved', true)->count(),
        ];

        $topCourses = $this->topCoursesReport();
        $topInstructors = $this->topInstructorsReport();
        $monthlyTrend = $this->monthlyTrendReport(6);

        $monthlyTrendChart = [
            'labels' => $monthlyTrend->pluck('label')->values()->all(),
            'revenue' => $monthlyTrend->pluck('revenue')->values()->all(),
            'enrollments' => $monthlyTrend->pluck('enrollments')->values()->all(),
            'completions' => $monthlyTrend->pluck('completions')->values()->all(),
        ];

        $topCourseChart = [
            'labels' => $topCourses->pluck('course_title')->values()->all(),
            'revenue' => $topCourses->pluck('total_revenue')->values()->all(),
            'sales' => $topCourses->pluck('total_sales')->values()->all(),
        ];

        $topInstructorChart = [
            'labels' => $topInstructors->pluck('instructor_name')->values()->all(),
            'earnings' => $topInstructors->pluck('total_earning')->values()->all(),
        ];

        $campusStats = [
            'online_enrollments' => Enrollment::where(fn ($q) => $q->where('delivery_mode', 'online')->orWhereNull('delivery_mode'))->count(),
            'f2f_enrollments' => Enrollment::where('delivery_mode', 'face_to_face')->count(),
            'hybrid_enrollments' => Enrollment::where('delivery_mode', 'hybrid')->count(),
            'invoiced_total' => (float) \App\Models\Invoice::sum('net_amount'),
            'invoiced_paid' => (float) \App\Models\Invoice::sum('paid_amount'),
            'invoiced_balance' => (float) \App\Models\Invoice::sum('balance'),
            'discounts_granted' => (float) \App\Models\Invoice::sum('discount_amount'),
            'overdue_installments' => \App\Models\PaymentSchedule::where('status', '!=', 'paid')->whereDate('due_date', '<', now())->count(),
            'branches' => \App\Models\Branch::withCount('enrollments')
                ->withSum('invoices as collected_amount', 'paid_amount')
                ->withSum('invoices as balance_amount', 'balance')
                ->orderBy('name')->get(),
        ];

        return view('admin.reports.index', [
            'financials' => $financials,
            'enrollmentStats' => $enrollmentStats,
            'studentStats' => $studentStats,
            'topCourses' => $topCourses,
            'topInstructors' => $topInstructors,
            'monthlyTrend' => $monthlyTrend,
            'monthlyTrendChart' => $monthlyTrendChart,
            'topCourseChart' => $topCourseChart,
            'topInstructorChart' => $topInstructorChart,
            'campusStats' => $campusStats,
        ]);
    }

    /**
     * Get the top courses report
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function topCoursesReport(int $limit = 5): Collection
    {
        $rows = Payment::query()
            ->select([
                'courses.id as course_id',
                'courses.title as course_title',
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('COUNT(payments.id) as total_sales'),
                DB::raw('SUM(CASE WHEN enrollments.status = "' . Enrollment::STATUS_COMPLETED . '" THEN 1 ELSE 0 END) as total_completions'),
            ])
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->groupBy('courses.id', 'courses.title')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            return [
                'course_id' => (int) $row->course_id,
                'course_title' => $row->course_title,
                'total_revenue' => (float) $row->total_revenue,
                'total_sales' => (int) $row->total_sales,
                'total_completions' => (int) $row->total_completions,
            ];
        });
    }

    /**
     * Get the top instructors report
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function topInstructorsReport(int $limit = 5): Collection
    {
        $rows = Payment::query()
            ->select([
                'courses.instructor_id',
                DB::raw('SUM(payments.instructor_earning) as total_earning'),
                DB::raw('COUNT(payments.id) as total_sales'),
            ])
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->whereNotNull('courses.instructor_id')
            ->groupBy('courses.instructor_id')
            ->orderByDesc('total_earning')
            ->limit($limit)
            ->get();

        $instructors = User::whereIn('id', $rows->pluck('instructor_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($instructors) {
            $instructor = $instructors->get($row->instructor_id);

            return [
                'instructor_id' => (int) $row->instructor_id,
                'instructor_name' => $instructor?->name ?? 'Unknown Instructor',
                'total_earning' => (float) $row->total_earning,
                'total_sales' => (int) $row->total_sales,
            ];
        });
    }

    /**
     * Get the monthly trend report
     *
     * @param int $months
     * @return \Illuminate\Support\Collection
     */
    private function monthlyTrendReport(int $months = 6): Collection
    {
        $data = collect();
        $months = max($months, 1);

        for ($i = $months - 1; $i >= 0; $i--) {
            $period = Carbon::now()->subMonths($i);
            $start = $period->copy()->startOfMonth();
            $end = $period->copy()->endOfMonth();

            $data->push([
                'label' => $period->format('M Y'),
                'revenue' => (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount'),
                'enrollments' => Enrollment::whereBetween('created_at', [$start, $end])->count(),
                'completions' => Enrollment::completed()->whereBetween('updated_at', [$start, $end])->count(),
            ]);
        }

        return $data;
    }

    /**
     * Weekly / Monthly / Quarterly Ongoing Regional Center Course Report (migrated from SCMS, SRS R2).
     */
    public function centerCourseReport(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $startDate = match ($period) {
            'weekly' => now()->startOfWeek(),
            'quarterly' => now()->firstOfQuarter(),
            'all' => null,
            default => now()->startOfMonth(),
        };

        $enrollments = Enrollment::with(['course:id,title,delivery_mode', 'branch:id,name', 'venue.branch:id,name', 'invoice:id,enrollment_id,paid_amount,balance,payment_status'])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->when($effectiveBranch, fn ($q) => $q->where('branch_id', $effectiveBranch))
            ->when($startDate, fn ($q) => $q->whereDate('created_at', '>=', $startDate->toDateString()))
            ->get();

        $rows = $enrollments
            ->groupBy(fn ($e) => ($e->branch_id ?? $e->venue?->branch_id ?? 0) . '-' . ($e->course_id ?? 0))
            ->map(function ($group) {
                $first = $group->first();
                $fullCount = 0;
                $halfCount = 0;
                $unpaidCount = 0;
                $collected = 0.0;
                $outstanding = 0.0;

                foreach ($group as $enrollment) {
                    $invoice = $enrollment->invoice;
                    $status = $invoice?->payment_status ?? 'unpaid';
                    if ($status === 'paid') {
                        $fullCount++;
                    } elseif ($status === 'partial') {
                        $halfCount++;
                    } else {
                        $unpaidCount++;
                    }
                    if ($invoice) {
                        $collected += (float) $invoice->paid_amount;
                        $outstanding += (float) $invoice->balance;
                    }
                }

                $branchName = $first->branch?->name ?? $first->venue?->branch?->name ?? 'Main Centre';
                $modeLabel = match ($first->course?->delivery_mode) {
                    'hybrid' => 'Hybrid',
                    'online' => 'Online',
                    default => 'Face to Face',
                };

                return [
                    'branch_name'     => $branchName,
                    'course_title'    => $first->course?->title ?? 'Course',
                    'teaching_method' => $modeLabel,
                    'total_students'  => $group->count(),
                    'full_paid'       => $fullCount,
                    'half_paid'       => $halfCount,
                    'unpaid'          => $unpaidCount,
                    'collected'       => $collected,
                    'outstanding'     => $outstanding,
                ];
            })
            ->sortBy([['branch_name', 'asc'], ['course_title', 'asc']])
            ->values();

        if ($request->input('export') === 'csv') {
            abort_unless(auth()->user()?->hasAdminPermission('export-reports'), 403);
            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Centre Name', 'Course Name', 'Delivery Mode', 'Total Students', 'Full Paid', 'Partial Paid', 'Unpaid', 'Collected', 'Outstanding']);
                foreach ($rows as $row) {
                    fputcsv($handle, array_values($row));
                }
                fclose($handle);
            }, 'center_course_report_' . $period . '_' . date('Ymd_His') . '.csv');
        }

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->hasAdminPermission('export-reports'), 403);
            return Pdf::loadView('admin.reports.center-course-pdf', [
                'rows' => $rows,
                'period' => $period,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ])->setPaper('a4', 'landscape')->download('center_course_report_' . $period . '_' . date('Ymd_His') . '.pdf');
        }

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.center-course', compact('rows', 'branches', 'period', 'effectiveBranch'));
    }

    /**
     * Comprehensive Applicant Report with PDF / Excel / CSV exports (migrated from SCMS).
     */
    public function applicantReport(Request $request)
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);
        $courseId = $request->input('course_id');
        $countryId = $request->input('country_id');
        $gender = $request->input('gender');
        $paymentStatus = $request->input('payment_status');
        $discountFilter = $request->input('discount_filter');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = trim((string) $request->input('q', ''));

        $query = Applicant::with(['branch:id,name', 'country', 'enrollments.course:id,title', 'invoices:id,applicant_id,net_amount,paid_amount,balance,payment_status'])
            ->when($effectiveBranch, fn ($q) => $q->where('branch_id', $effectiveBranch))
            ->when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->when($gender, fn ($q) => $q->where('gender', $gender))
            ->when($courseId, fn ($q) => $q->whereHas('enrollments', fn ($e) => $e->where('course_id', $courseId)))
            ->when($paymentStatus, fn ($q) => $q->whereHas('invoices', fn ($i) => $i->where('payment_status', $paymentStatus)))
            ->when($discountFilter === 'discounted', fn ($q) => $q->where('has_discount', true))
            ->when($discountFilter === 'staff', fn ($q) => $q->where('is_staff_student', true))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")));

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf', 'excel'], true)) {
            abort_unless(auth()->user()?->hasAdminPermission('export-reports'), 403);
            $allApplicants = (clone $query)->latest()->get();

            if ($export === 'excel') {
                return Excel::download(new ApplicantReportExport($allApplicants), 'applicant_report_' . date('Ymd_His') . '.xlsx');
            }

            if ($export === 'csv') {
                return Excel::download(new ApplicantReportExport($allApplicants), 'applicant_report_' . date('Ymd_His') . '.csv', \Maatwebsite\Excel\Excel::CSV);
            }

            return Pdf::loadView('admin.reports.applicant-report-pdf', [
                'applicants' => $allApplicants,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ])->setPaper('a4', 'landscape')->download('applicant_report_' . date('Ymd_His') . '.pdf');
        }

        $statsQuery = clone $query;
        $totalApplicants = (clone $statsQuery)->count();
        $discountedCount = (clone $statsQuery)->where('has_discount', true)->count();

        $applicants = $query->latest()->paginate(25)->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $countries = Country::orderBy('name')->get(['id', 'name', 'nicename']);

        return view('admin.reports.applicant-report', compact(
            'applicants',
            'branches',
            'courses',
            'countries',
            'effectiveBranch',
            'totalApplicants',
            'discountedCount'
        ));
    }
}
