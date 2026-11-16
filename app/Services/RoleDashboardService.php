<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Bundle;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

/**
 * Builds role-specific, permission-checked, filterable, and paginated dashboard payloads
 * using joined database queries across LMS and SCMS tables.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class RoleDashboardService
{
    /**
     * Assemble the complete KPI, chart, filter, and paginated widget payload for a user.
     */
    public function buildForUser(User $user, array $rawFilters = []): array
    {
        $profile = $user->primaryDashboardProfile();
        $isMultiBranch = $user->isMultiBranchAdmin();
        $filters = $this->normalizeFilters($rawFilters);
        $branchId = $user->effectiveBranchId($filters['branch_id']);
        $filters['branch_id'] = $branchId;

        $scope = [
            'profile' => $profile,
            'role_label' => $user->primaryRoleLabel(),
            'branch_id' => $branchId,
            'branch_name' => $branchId
                ? (Branch::whereKey($branchId)->value('name') ?? 'Assigned Center')
                : ($isMultiBranch ? 'All 17 Regional Centers' : 'Assigned Center'),
            'scope_label' => $branchId && $isMultiBranch
                ? 'Filtered: ' . (Branch::whereKey($branchId)->value('name') ?? 'Selected Center')
                : $user->dashboardScopeDescription(),
            'is_multi_branch' => $isMultiBranch,
            'branches' => $isMultiBranch
                ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_code'])
                : collect(),
            'can_view_campus_metrics' => $user->hasAdminPermission('view-analytics') || $user->hasAdminPermission('view-reports'),
            'campus' => $this->campusStrip($user, $branchId),
        ];

        $data = match ($profile) {
            'admin'          => $this->buildAdminData($user, $branchId, $filters),
            'director'       => $this->buildDirectorData($branchId, $filters),
            'marketing'      => $this->buildMarketingData($branchId, $filters),
            'branch_manager' => $this->buildBranchManagerData($branchId, $filters),
            'coordinator'    => $this->buildCoordinatorData($branchId, $filters),
            'accountant'     => $this->buildAccountantData($branchId, $filters),
            'staff'          => $this->buildStaffData($branchId, $filters),
            default          => $this->buildCustomData($user, $branchId, $filters),
        };

        $data['profile'] = $profile;
        $data['scope'] = $scope;
        $data['filters'] = $filters;
        $data['status_options'] = $this->statusOptionsForProfile($profile);

        return $data;
    }

    /**
     * Normalize incoming dashboard GET filter parameters.
     */
    private function normalizeFilters(array $raw): array
    {
        $dateRange = strtolower((string) ($raw['date_range'] ?? 'all'));
        if (! in_array($dateRange, ['all', 'today', '7d', '30d', 'month'], true)) {
            $dateRange = 'all';
        }

        $perPage = (int) ($raw['per_page'] ?? 5);
        if (! in_array($perPage, [5, 10, 15, 25], true)) {
            $perPage = 5;
        }

        return [
            'branch_id' => ! empty($raw['branch_id']) ? (int) $raw['branch_id'] : null,
            'search' => trim((string) ($raw['search'] ?? '')),
            'date_range' => $dateRange,
            'status' => strtolower(trim((string) ($raw['status'] ?? 'all'))) ?: 'all',
            'per_page' => $perPage,
        ];
    }

    /**
     * Resolve [start, end] Carbon bounds for a filter window, or null when unrestricted.
     */
    private function resolveDateWindow(string $dateRange): ?array
    {
        return match ($dateRange) {
            'today' => [Carbon::today()->startOfDay(), Carbon::now()->endOfDay()],
            '7d'    => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()],
            '30d'   => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            default => null,
        };
    }

    /**
     * Apply date filter.
     */
    private function applyDateFilter(Builder $query, string $column, ?array $window): Builder
    {
        return $window ? $query->whereBetween($column, $window) : $query;
    }

    /**
     * Status options for profile.
     */
    private function statusOptionsForProfile(string $profile): array
    {
        return match ($profile) {
            'admin' => [
                'all' => 'All Statuses',
                'completed' => 'Completed Payments',
                'pending' => 'Pending Payments',
                'published' => 'Published Courses',
            ],
            'director' => [
                'all' => 'All Statuses',
                'active' => 'Active Classes',
                'ready' => 'Certs Ready',
                'issued' => 'Certs Issued',
            ],
            'marketing' => [
                'all' => 'All Modes',
                'face_to_face' => 'Face to Face',
                'hybrid' => 'Hybrid',
                'online' => 'Online',
            ],
            'branch_manager' => [
                'all' => 'All Statuses',
                'paid' => 'Paid Invoices',
                'partial' => 'Partial Invoices',
                'unpaid' => 'Unpaid Invoices',
                'active' => 'Active Classes',
            ],
            'coordinator' => [
                'all' => 'All Statuses',
                'active' => 'Active Classes',
                'ready' => 'Certs Ready',
                'pending' => 'Certs Pending',
                'present' => 'Attendance: Present',
                'late' => 'Attendance: Late',
                'absent' => 'Attendance: Absent',
            ],
            'accountant' => [
                'all' => 'All Statuses',
                'paid' => 'Paid Invoices',
                'partial' => 'Partial Invoices',
                'unpaid' => 'Unpaid Invoices',
                'pending' => 'Pending Slip',
            ],
            'staff' => [
                'all' => 'All Statuses',
                'open' => 'Open Tickets',
                'in_progress' => 'In Progress Tickets',
                'active' => 'Active Classes',
            ],
            default => ['all' => 'All Statuses'],
        };
    }

    /**
     * Paginate collection.
     */
    private function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage($pageName);
        $slice = $items->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        ))->withQueryString();
    }

    /**
     * Compact campus KPIs for the permission-aware header strip.
     */
    private function campusStrip(User $user, ?int $branchId): array
    {
        return [
            'role_label'            => $user->primaryRoleLabel(),
            'branch_name'           => $branchId ? (Branch::whereKey($branchId)->value('name') ?? 'Assigned Center') : 'All Regional Branches',
            'active_classes'        => CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'active')->count(),
            'ready_certificates'    => Enrollment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereIn('delivery_mode', ['face_to_face', 'hybrid'])->where('certificate_status', 'ready')->count(),
            'f2f_applicants'        => Applicant::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'pending_verifications' => Payment::query()->withoutGlobalScope('branch')->forBranch($branchId)->whereNotNull('invoice_id')->where('verification_status', 'pending')->count(),
            'overdue_installments'  => PaymentSchedule::query()
                ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->withoutGlobalScope('branch')->where('branch_id', $branchId)))
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->whereDate('due_date', '<', now())
                ->count(),
            'invoiced_balance'      => (float) Invoice::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('balance'),
        ];
    }

    /**
     * Super Admin / Admin / System Administrator: full platform + campus intelligence.
     */
    private function buildAdminData(User $user, ?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $paymentsBase = Payment::withoutGlobalScope('branch')->forBranch($branchId);
        $totalRevenue = (float) (clone $paymentsBase)->where('status', Payment::STATUS_COMPLETED)->sum('amount');
        $lastMonthRevenue = (float) (clone $paymentsBase)
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');
        $revenueChangePct = $lastMonthRevenue > 0
            ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        $studentsQuery = User::where('role', 'student')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $totalStudents = (clone $studentsQuery)->count();
        $newStudentsThisMonth = (clone $studentsQuery)->where('created_at', '>=', $thisMonthStart)->count();
        $lastMonthNewStudents = (clone $studentsQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $studentsChangePct = $lastMonthNewStudents > 0
            ? round((($newStudentsThisMonth - $lastMonthNewStudents) / $lastMonthNewStudents) * 100, 1)
            : null;

        $enrollmentsBase = Enrollment::withoutGlobalScope('branch')->forBranch($branchId);
        $totalEnrollments = (clone $enrollmentsBase)->count();
        $thisMonthEnrollments = (clone $enrollmentsBase)->where('created_at', '>=', $thisMonthStart)->count();
        $averageRating = Review::where('is_approved', true)->avg('rating') ?: 0;
        $totalApprovedReviews = Review::where('is_approved', true)->count();
        $pendingOfflinePayments = (clone $paymentsBase)
            ->where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'published')->count();
        $completionStats = (clone $enrollmentsBase)->select([
            DB::raw("SUM(CASE WHEN status = '" . Enrollment::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed"),
            DB::raw('COUNT(*) as total'),
        ])->first();
        $completionRate = ($completionStats && $completionStats->total > 0)
            ? round(($completionStats->completed / $completionStats->total) * 100, 1)
            : 0;

        $topCourses = Course::query()
            ->select('courses.id', 'courses.title', 'courses.featured_image', 'courses.status')
            ->selectSub(
                DB::table('payments')
                    ->join('enrollments', 'enrollments.id', '=', 'payments.enrollment_id')
                    ->whereColumn('enrollments.course_id', 'courses.id')
                    ->where('payments.status', Payment::STATUS_COMPLETED)
                    ->when($branchId, fn ($q) => $q->where('enrollments.branch_id', $branchId))
                    ->when($dateWindow, fn ($q) => $q->whereBetween('payments.created_at', $dateWindow))
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)'),
                'total_revenue'
            )
            ->withCount([
                'enrollments' => function ($q) use ($branchId, $dateWindow): void {
                    $q->withoutGlobalScope('branch')
                        ->when($branchId, fn ($eq) => $eq->where('branch_id', $branchId))
                        ->when($dateWindow, fn ($eq) => $eq->whereBetween('created_at', $dateWindow));
                },
                'enrollments as completed_enrollments_count' => function ($q) use ($branchId, $dateWindow): void {
                    $q->withoutGlobalScope('branch')
                        ->where('status', Enrollment::STATUS_COMPLETED)
                        ->when($branchId, fn ($eq) => $eq->where('branch_id', $branchId))
                        ->when($dateWindow, fn ($eq) => $eq->whereBetween('created_at', $dateWindow));
                },
                'reviews',
            ])
            ->withAvg('reviews', 'rating')
            ->when($search !== '', fn ($q) => $q->where('courses.title', 'like', "%{$search}%"))
            ->when(in_array($status, ['published', 'draft'], true), fn ($q) => $q->where('courses.status', $status))
            ->when($branchId, fn ($q) => $q->whereHas('enrollments', fn ($eq) => $eq->withoutGlobalScope('branch')->where('branch_id', $branchId)))
            ->orderByDesc('total_revenue')
            ->orderByDesc('enrollments_count')
            ->paginate($perPage, ['*'], 'courses_page')
            ->withQueryString()
            ->through(function (Course $course): array {
                $courseCompletionRate = $course->enrollments_count > 0
                    ? round(($course->completed_enrollments_count / $course->enrollments_count) * 100, 1)
                    : 0;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'enrollments' => $course->enrollments_count,
                    'revenue' => (float) ($course->total_revenue ?? 0),
                    'rating' => round($course->reviews_avg_rating ?? 0, 1),
                    'completion_rate' => $courseCompletionRate,
                ];
            });

        $activeEngaged = (clone $enrollmentsBase)->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])->distinct('user_id')->count('user_id');
        $completingEngaged = (clone $enrollmentsBase)->where('status', Enrollment::STATUS_COMPLETED)->distinct('user_id')->count('user_id');
        $atRiskEngaged = (clone $enrollmentsBase)->where('status', Enrollment::STATUS_PENDING)->distinct('user_id')->count('user_id');

        $recentEnrollments = $this->applyDateFilter(
            (clone $enrollmentsBase)->with(['user:id,name', 'course:id,title']),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()->limit(15)->get()->toBase()->map(fn ($e) => [
                'type' => 'enrollment',
                'icon' => 'fa-user-plus',
                'message' => ($e->user->name ?? 'Student') . ' enrolled in "' . ($e->course->title ?? 'Course') . '"',
                'time' => $e->created_at->diffForHumans(),
                'timestamp' => $e->created_at,
            ]);

        $recentReviews = $this->applyDateFilter(
            Review::with(['user:id,name', 'course:id,title'])->where('is_approved', true),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()->limit(10)->get()->toBase()->map(fn ($r) => [
                'type' => 'review',
                'icon' => 'fa-star',
                'message' => ($r->user->name ?? 'Student') . ' rated "' . ($r->course->title ?? 'Course') . '" ' . round($r->rating, 1) . ' stars',
                'time' => $r->created_at->diffForHumans(),
                'timestamp' => $r->created_at,
            ]);

        $recentActivity = $this->paginateCollection(
            $recentEnrollments->merge($recentReviews)->sortByDesc('timestamp')->values(),
            $perPage,
            'activity_page'
        );

        $sales = $this->applyDateFilter(
            (clone $paymentsBase)->with(['enrollment.user:id,name', 'enrollment.course:id,title', 'user:id,name']),
            'created_at',
            $dateWindow
        )
            ->when(in_array($status, [Payment::STATUS_COMPLETED, Payment::STATUS_PENDING, Payment::STATUS_FAILED], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('enrollment.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('enrollment.course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'tx_page')
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => 'sale-' . $p->id,
                'type' => 'sale',
                'description' => $p->enrollment?->course?->title ?? 'Course Sale',
                'student' => $p->enrollment?->user?->name ?? $p->user?->name ?? 'User',
                'amount' => (float) $p->amount,
                'status' => $p->status,
                'date' => $p->created_at->format('M d, Y'),
                'raw_date' => $p->created_at,
            ]);

        return [
            'overview' => [
                'total_revenue' => $totalRevenue,
                'revenue_change_pct' => $revenueChangePct,
                'total_students' => $totalStudents,
                'new_students_this_month' => $newStudentsThisMonth,
                'students_change_pct' => $studentsChangePct,
                'total_enrollments' => $totalEnrollments,
                'this_month_enrollments' => $thisMonthEnrollments,
                'average_rating' => $averageRating,
                'total_reviews' => $totalApprovedReviews,
                'completion_rate' => $completionRate,
                'active_courses' => $activeCourses,
                'total_courses' => $totalCourses,
                'refund_rate' => 0,
                'pending_offline_payments' => $pendingOfflinePayments,
            ],
            'charts' => [
                'thirty_days' => $this->getRevenueEnrollmentChart('thirty_days', $branchId),
                'this_year' => $this->getRevenueEnrollmentChart('this_year', $branchId),
                'last_12_months' => $this->getRevenueEnrollmentChart('last_12_months', $branchId),
            ],
            'top_courses' => $topCourses,
            'student_engagement' => [
                'active_students' => $activeEngaged,
                'active_percentage' => $totalStudents > 0 ? round(($activeEngaged / $totalStudents) * 100, 1) : 0,
                'completing_students' => $completingEngaged,
                'completing_percentage' => $totalStudents > 0 ? round(($completingEngaged / $totalStudents) * 100, 1) : 0,
                'at_risk_students' => $atRiskEngaged,
                'at_risk_percentage' => $totalStudents > 0 ? round(($atRiskEngaged / $totalStudents) * 100, 1) : 0,
            ],
            'recent_activity' => $recentActivity,
            'recent_transactions' => $sales,
            'campus' => $this->campusStrip($user, $branchId),
            'security_logs' => $this->recentSecurityLogs(5),
        ];
    }

    /**
     * Director / Deputy Director: academic & branch oversight with filterable paginated tables.
     */
    private function buildDirectorData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $activeClassesQuery = CourseAssignment::query()->withoutGlobalScope('branch')
            ->with(['course:id,title', 'branch:id,name', 'venue:id,name', 'instructor:id,name'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($dateWindow, fn ($q) => $q->whereBetween('created_at', $dateWindow))
            ->when($status === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($status !== 'active', fn ($q) => $q->where('status', 'active'))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('venue', fn ($v) => $v->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('start_date');

        $readyCertificates = Enrollment::withoutGlobalScope('branch')
            ->with(['user:id,name', 'applicant:id,full_name', 'course:id,title', 'branch:id,name'])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($dateWindow, fn ($q) => $q->whereBetween('created_at', $dateWindow))
            ->when(in_array($status, ['ready', 'issued'], true), fn ($q) => $q->where('certificate_status', $status), fn ($q) => $q->whereIn('certificate_status', ['ready', 'issued']))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            }))
            ->latest('certificate_ready_at')
            ->paginate($perPage, ['*'], 'certs_page')
            ->withQueryString();

        $branchPerformance = DB::table('branches as b')
            ->leftJoin('applicants as a', 'a.branch_id', '=', 'b.id')
            ->leftJoin('invoices as i', 'i.branch_id', '=', 'b.id')
            ->when($branchId, fn ($q) => $q->where('b.id', $branchId))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('b.name', 'like', "%{$search}%")
                    ->orWhere('b.branch_code', 'like', "%{$search}%");
            }))
            ->select(
                'b.id',
                'b.name',
                'b.branch_code',
                DB::raw('COUNT(DISTINCT a.id) as applicants_count'),
                DB::raw('COUNT(DISTINCT i.id) as invoices_count'),
                DB::raw('COALESCE(SUM(i.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(i.balance), 0) as total_balance')
            )
            ->groupBy('b.id', 'b.name', 'b.branch_code')
            ->orderByDesc('applicants_count')
            ->orderByDesc('total_paid')
            ->paginate($perPage, ['*'], 'branches_page')
            ->withQueryString();

        $pendingInstructors = User::where('role', 'instructor')
            ->where('is_approved', false)
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage, ['id', 'name', 'email', 'specialization', 'created_at'], 'instructors_page')
            ->withQueryString();

        return [
            'kpis' => [
                'branches_count' => Branch::where('is_active', true)->when($branchId, fn ($q) => $q->where('id', $branchId))->count(),
                'active_classes_count' => CourseAssignment::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'active')->count(),
                'ongoing_f2f_count' => Enrollment::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereIn('delivery_mode', ['face_to_face', 'hybrid'])->where('status', Enrollment::STATUS_APPROVED)->count(),
                'ready_certs_count' => Enrollment::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereIn('delivery_mode', ['face_to_face', 'hybrid'])->where('certificate_status', 'ready')->count(),
                'issued_certs_count' => Enrollment::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereIn('delivery_mode', ['face_to_face', 'hybrid'])->where('certificate_status', 'issued')->count(),
                'pending_instructors_count' => User::where('role', 'instructor')->where('is_approved', false)->count(),
                'total_invoiced_paid' => (float) Invoice::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('paid_amount'),
                'total_invoiced_balance' => (float) Invoice::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('balance'),
            ],
            'active_classes' => $activeClassesQuery->paginate($perPage, ['*'], 'classes_page')->withQueryString(),
            'ready_certificates' => $readyCertificates,
            'branch_performance' => $branchPerformance,
            'pending_instructors' => $pendingInstructors,
            'security_logs' => $this->recentSecurityLogs(5),
        ];
    }

    /**
     * Marketing Officer: growth, intake funnel, courses, bundles & coupon campaigns.
     */
    private function buildMarketingData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $applicantsBase = Applicant::withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $totalApplicants = (clone $applicantsBase)->count();
        $convertedApplicants = DB::table('applicants as a')
            ->join('enrollments as e', 'e.applicant_id', '=', 'a.id')
            ->when($branchId, fn ($q) => $q->where('a.branch_id', $branchId))
            ->where('e.status', Enrollment::STATUS_APPROVED)
            ->distinct('a.id')
            ->count('a.id');

        $conversionRate = $totalApplicants > 0 ? round(($convertedApplicants / $totalApplicants) * 100, 1) : 0;

        $courseDemand = DB::table('courses as c')
            ->leftJoin('enrollments as e', function ($join) use ($branchId, $dateWindow): void {
                $join->on('e.course_id', '=', 'c.id');
                if ($branchId) {
                    $join->where('e.branch_id', '=', $branchId);
                }
                if ($dateWindow) {
                    $join->whereBetween('e.created_at', $dateWindow);
                }
            })
            ->where('c.status', 'published')
            ->when(in_array($status, ['face_to_face', 'hybrid', 'online'], true), fn ($q) => $q->where('c.delivery_mode', $status))
            ->when($search !== '', fn ($q) => $q->where('c.title', 'like', "%{$search}%"))
            ->select(
                'c.id',
                'c.title',
                'c.delivery_mode',
                DB::raw('COUNT(e.id) as enrollments_count'),
                DB::raw("SUM(CASE WHEN e.delivery_mode IN ('face_to_face', 'hybrid') THEN 1 ELSE 0 END) as f2f_count"),
                DB::raw("SUM(CASE WHEN e.delivery_mode = 'online' THEN 1 ELSE 0 END) as online_count")
            )
            ->groupBy('c.id', 'c.title', 'c.delivery_mode')
            ->orderByDesc('enrollments_count')
            ->paginate($perPage, ['*'], 'course_demand_page')
            ->withQueryString();

        $branchDemand = DB::table('branches as b')
            ->leftJoin('applicants as a', function ($join) use ($dateWindow): void {
                $join->on('a.branch_id', '=', 'b.id');
                if ($dateWindow) {
                    $join->whereBetween('a.created_at', $dateWindow);
                }
            })
            ->when($branchId, fn ($q) => $q->where('b.id', $branchId))
            ->when($search !== '', fn ($q) => $q->where('b.name', 'like', "%{$search}%"))
            ->select('b.id', 'b.name', DB::raw('COUNT(a.id) as applicants_count'))
            ->groupBy('b.id', 'b.name')
            ->orderByDesc('applicants_count')
            ->paginate($perPage, ['*'], 'branch_demand_page')
            ->withQueryString();

        $recentLeads = $this->applyDateFilter(
            (clone $applicantsBase)->with(['branch:id,name', 'enrollments.course:id,title']),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('enrollments.course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'leads_page')
            ->withQueryString();

        $activeCoupons = Coupon::where('status', 'active')
            ->orderByDesc('used_count')
            ->limit(6)
            ->get(['id', 'code', 'type', 'value', 'used_count', 'usage_limit', 'valid_to']);

        return [
            'kpis' => [
                'total_applicants' => $totalApplicants,
                'converted_applicants' => $convertedApplicants,
                'conversion_rate' => $conversionRate,
                'this_month_applicants' => (clone $applicantsBase)->where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                'published_courses' => Course::where('status', 'published')->count(),
                'active_bundles' => Schema::hasTable('bundles') ? Bundle::where('status', 'active')->count() : 0,
                'active_coupons' => Coupon::where('status', 'active')->count(),
                'coupon_redemptions' => (int) Coupon::sum('used_count'),
                'discount_waivers' => (clone $applicantsBase)->where('has_discount', true)->count(),
            ],
            'intake_chart' => $this->getApplicantEnrollmentChart(12, $branchId),
            'course_demand' => $courseDemand,
            'branch_demand' => $branchDemand,
            'recent_leads' => $recentLeads,
            'active_coupons' => $activeCoupons,
        ];
    }

    /**
     * Branch Manager / Registrar: 100% branch-isolated with filterable paginated cards.
     */
    private function buildBranchManagerData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $applicantsQuery = Applicant::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $invoicesQuery = Invoice::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $assignmentsQuery = CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $venues = Venue::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount(['courseAssignments as active_classes_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'venues_page')
            ->withQueryString();

        $recentInvoices = $this->applyDateFilter(
            (clone $invoicesQuery)->with(['applicant:id,full_name', 'course:id,title']),
            'created_at',
            $dateWindow
        )
            ->when(in_array($status, ['paid', 'partial', 'unpaid'], true), fn ($q) => $q->where('payment_status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('control_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'invoices_page')
            ->withQueryString();

        $activeClasses = (clone $assignmentsQuery)
            ->with(['course:id,title', 'venue:id,name', 'instructor:id,name'])
            ->withCount('enrollments')
            ->where('status', 'active')
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('venue', fn ($v) => $v->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('start_date')
            ->paginate($perPage, ['*'], 'classes_page')
            ->withQueryString();

        $recentApplicants = $this->applyDateFilter(
            (clone $applicantsQuery)->with('enrollments.course:id,title'),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('enrollments.course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'applicants_page')
            ->withQueryString();

        return [
            'kpis' => [
                'applicants_count' => (clone $applicantsQuery)->count(),
                'enrollments_count' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->count(),
                'collected_amount' => (float) (clone $invoicesQuery)->sum('paid_amount'),
                'outstanding_balance' => (float) (clone $invoicesQuery)->sum('balance'),
                'active_classes' => (clone $assignmentsQuery)->where('status', 'active')->count(),
                'venues_count' => Venue::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
                'ready_certificates' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('certificate_status', 'ready')->count(),
                'overdue_installments' => PaymentSchedule::query()
                    ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->withoutGlobalScope('branch')->where('branch_id', $branchId)))
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<', now())
                    ->count(),
            ],
            'chart' => $this->getRevenueEnrollmentChart('last_12_months', $branchId),
            'venues' => $venues,
            'active_classes' => $activeClasses,
            'recent_invoices' => $recentInvoices,
            'recent_applicants' => $recentApplicants,
        ];
    }

    /**
     * Training Coordinator: 100% branch-isolated academic & logistics operations.
     */
    private function buildCoordinatorData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $activeClasses = CourseAssignment::query()
            ->withoutGlobalScope('branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['course:id,title,attendance_threshold', 'venue:id,name,capacity', 'instructor:id,name'])
            ->withCount('enrollments')
            ->where('status', 'active')
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('venue', fn ($v) => $v->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('start_date')
            ->paginate($perPage, ['*'], 'classes_page')
            ->withQueryString();

        $venues = Venue::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount(['courseAssignments as active_classes_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'venues_page')
            ->withQueryString();

        $attendanceStats = AttendanceRecord::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as attended")
            ->first();
        $attendanceRate = ($attendanceStats && $attendanceStats->total > 0)
            ? round(($attendanceStats->attended / $attendanceStats->total) * 100, 1)
            : 0;

        $readyClearances = Enrollment::query()
            ->withoutGlobalScope('branch')
            ->forBranch($branchId)
            ->with(['user:id,name', 'applicant:id,full_name', 'course:id,title,attendance_threshold'])
            ->whereIn('delivery_mode', ['face_to_face', 'hybrid'])
            ->when(in_array($status, ['pending', 'ready'], true), fn ($q) => $q->where('certificate_status', $status), fn ($q) => $q->whereIn('certificate_status', ['pending', 'ready']))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'clearances_page')
            ->withQueryString();

        $recentAttendance = $this->applyDateFilter(
            AttendanceRecord::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->with(['user:id,name', 'course:id,title']),
            'attendance_date',
            $dateWindow
        )
            ->when(in_array($status, ['present', 'late', 'absent'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest('attendance_date')
            ->paginate($perPage, ['*'], 'attendance_page')
            ->withQueryString();

        return [
            'kpis' => [
                'active_classes' => CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'active')->count(),
                'completed_classes' => CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'completed')->count(),
                'venues_count' => Venue::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
                'branch_enrollments' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->count(),
                'attendance_rate' => $attendanceRate,
                'attendance_records_count' => (int) ($attendanceStats->total ?? 0),
                'ready_certificates' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('certificate_status', 'ready')->count(),
                'issued_certificates' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('certificate_status', 'issued')->count(),
            ],
            'active_classes' => $activeClasses,
            'venues' => $venues,
            'ready_clearances' => $readyClearances,
            'recent_attendance' => $recentAttendance,
        ];
    }

    /**
     * Accountant: 100% branch-isolated GePG invoices, collections, overdue schedules & verifications.
     */
    private function buildAccountantData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $invoicesQuery = Invoice::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $overdueBase = PaymentSchedule::query()
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->withoutGlobalScope('branch')->where('branch_id', $branchId)))
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', '<', now());

        $overdueSummary = (clone $overdueBase)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0) as due_balance')
            ->first();

        $overdueSchedules = (clone $overdueBase)
            ->with(['invoice.applicant:id,full_name,phone', 'invoice.course:id,title'])
            ->when($search !== '', fn ($q) => $q->whereHas('invoice', function ($iq) use ($search): void {
                $iq->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"));
            }))
            ->orderBy('due_date')
            ->paginate($perPage, ['*'], 'overdue_page')
            ->withQueryString();

        $pendingVerificationsBase = Payment::query()
            ->withoutGlobalScope('branch')
            ->forBranch($branchId)
            ->where('verification_status', 'pending');

        $pendingVerificationsCount = (clone $pendingVerificationsBase)->count();

        $pendingVerifications = $this->applyDateFilter(
            (clone $pendingVerificationsBase)->with(['invoice:id,invoice_number,control_number,branch_id', 'applicant:id,full_name', 'user:id,name']),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'verifications_page')
            ->withQueryString();

        $recentInvoices = $this->applyDateFilter(
            (clone $invoicesQuery)->with(['applicant:id,full_name', 'course:id,title']),
            'created_at',
            $dateWindow
        )
            ->when(in_array($status, ['paid', 'partial', 'unpaid'], true), fn ($q) => $q->where('payment_status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('control_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'invoices_page')
            ->withQueryString();

        return [
            'kpis' => [
                'total_invoiced' => (float) (clone $invoicesQuery)->sum('net_amount'),
                'collected_amount' => (float) (clone $invoicesQuery)->sum('paid_amount'),
                'outstanding_balance' => (float) (clone $invoicesQuery)->sum('balance'),
                'discount_waivers' => (float) (clone $invoicesQuery)->sum('discount_amount'),
                'paid_invoices_count' => (clone $invoicesQuery)->where('payment_status', 'paid')->count(),
                'partial_invoices_count' => (clone $invoicesQuery)->where('payment_status', 'partial')->count(),
                'unpaid_invoices_count' => (clone $invoicesQuery)->where('payment_status', 'unpaid')->count(),
                'overdue_count' => (int) ($overdueSummary->cnt ?? 0),
                'overdue_amount' => (float) ($overdueSummary->due_balance ?? 0),
                'pending_verifications_count' => $pendingVerificationsCount,
            ],
            'collection_chart' => $this->getRevenueEnrollmentChart('last_12_months', $branchId),
            'overdue_schedules' => $overdueSchedules,
            'pending_verifications' => $pendingVerifications,
            'recent_invoices' => $recentInvoices,
        ];
    }

    /**
     * Center Staff: 100% branch-isolated front-desk intake, tickets, attendance & clearances.
     */
    private function buildStaffData(?int $branchId, array $filters): array
    {
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];
        $dateWindow = $this->resolveDateWindow($filters['date_range']);

        $applicantsQuery = Applicant::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $openTickets = Schema::hasTable('support_tickets')
            ? $this->applyDateFilter(
                SupportTicket::query()->forBranch($branchId)->with('user:id,name,email'),
                'created_at',
                $dateWindow
            )
                ->when(in_array($status, ['open', 'in_progress'], true), fn ($q) => $q->where('status', $status), fn ($q) => $q->whereIn('status', ['open', 'in_progress']))
                ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                    $sub->where('subject', 'like', "%{$search}%")
                        ->orWhere('ticket_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                }))
                ->latest()
                ->paginate($perPage, ['*'], 'tickets_page')
                ->withQueryString()
            : $this->paginateCollection(collect(), $perPage, 'tickets_page');

        $upcomingClasses = CourseAssignment::query()
            ->withoutGlobalScope('branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['course:id,title', 'venue:id,name', 'instructor:id,name'])
            ->where('status', 'active')
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('venue', fn ($v) => $v->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('start_date')
            ->paginate($perPage, ['*'], 'classes_page')
            ->withQueryString();

        $recentApplicants = $this->applyDateFilter(
            (clone $applicantsQuery)->with('enrollments.course:id,title'),
            'created_at',
            $dateWindow
        )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('enrollments.course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'applicants_page')
            ->withQueryString();

        return [
            'kpis' => [
                'applicants_count' => (clone $applicantsQuery)->count(),
                'this_month_applicants' => (clone $applicantsQuery)->where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                'open_tickets_count' => Schema::hasTable('support_tickets') ? SupportTicket::query()->forBranch($branchId)->whereIn('status', ['open', 'in_progress'])->count() : 0,
                'active_classes' => CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'active')->count(),
                'today_attendance' => AttendanceRecord::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereDate('attendance_date', Carbon::today())->count(),
                'ready_certificates' => Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('certificate_status', 'ready')->count(),
            ],
            'recent_applicants' => $recentApplicants,
            'open_tickets' => $openTickets,
            'upcoming_classes' => $upcomingClasses,
        ];
    }

    /**
     * Custom staff role fallback: pure permission-composed dashboard (zero leakage).
     */
    private function buildCustomData(User $user, ?int $branchId, array $filters): array
    {
        return [
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'applicants_count' => $user->hasAdminPermission('manage-applicants')
                ? Applicant::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()
                : null,
            'active_classes_count' => $user->hasAdminPermission('manage-timetables')
                ? CourseAssignment::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', 'active')->count()
                : null,
            'invoices_balance' => $user->hasAdminPermission('manage-invoices')
                ? (float) Invoice::query()->withoutGlobalScope('branch')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('balance')
                : null,
            'ready_certificates_count' => $user->hasAdminPermission('manage-certificates')
                ? Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('certificate_status', 'ready')->count()
                : null,
            'open_tickets_count' => ($user->hasAdminPermission('manage-tickets') && Schema::hasTable('support_tickets'))
                ? SupportTicket::query()->forBranch($branchId)->whereIn('status', ['open', 'in_progress'])->count()
                : null,
        ];
    }

    /**
     * Get revenue enrollment chart.
     */
    private function getRevenueEnrollmentChart(string $period, ?int $branchId = null): array
    {
        $labels = [];
        $revenue = [];
        $enrollments = [];

        if ($period === 'thirty_days') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->format('d M');
                $revenue[] = (float) Payment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('status', Payment::STATUS_COMPLETED)->whereDate('created_at', $date)->sum('amount');
                $enrollments[] = Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->whereDate('created_at', $date)->count();
            }
        } elseif ($period === 'this_year') {
            $startOfYear = Carbon::now()->startOfYear();
            $currentMonth = Carbon::now()->month;
            for ($i = 1; $i <= $currentMonth; $i++) {
                $date = $startOfYear->copy()->month($i);
                $labels[] = $date->format('M');
                $revenue[] = (float) Payment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('status', Payment::STATUS_COMPLETED)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('amount');
                $enrollments[] = Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $labels[] = $date->format('M');
                $revenue[] = (float) Payment::query()->withoutGlobalScope('branch')->forBranch($branchId)->where('status', Payment::STATUS_COMPLETED)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('amount');
                $enrollments[] = Enrollment::query()->withoutGlobalScope('branch')->forBranch($branchId)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
            }
        }

        return compact('labels', 'revenue', 'enrollments');
    }

    /**
     * Get applicant enrollment chart.
     */
    private function getApplicantEnrollmentChart(int $months = 12, ?int $branchId = null): array
    {
        $labels = [];
        $applicants = [];
        $enrollments = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $applicants[] = Applicant::withoutGlobalScope('branch')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
            $enrollments[] = Enrollment::withoutGlobalScope('branch')
                ->forBranch($branchId)
                ->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
        }

        return compact('labels', 'applicants', 'enrollments');
    }

    /**
     * Recent security logs.
     */
    private function recentSecurityLogs(int $limit = 5): Collection
    {
        if (! Schema::hasTable('activity_log')) {
            return collect();
        }

        return Activity::with('causer:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
