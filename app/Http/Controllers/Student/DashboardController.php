<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\LiveClassService;
use App\Support\DashboardFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Dashboard Controller
 *
 * This controller handles the student dashboard functionality with filtering and card pagination.
 *
 * @package App\Http\Controllers\Student
 */
class DashboardController extends Controller
{
    public function __construct(protected LiveClassService $liveClassService) {}

    /**
     * Display the student dashboard
     */
    public function index(Request $request)
    {
        $user = $this->currentUser();
        $filters = DashboardFilters::fromRequest($request);
        $dateWindow = DashboardFilters::dateWindow($filters['date_range']);
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];

        $upcomingLiveClass = $this->liveClassService->getUpcomingForStudent($user);

        $totalEnrollments = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        $completedCourses = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->count();

        $pendingEnrollments = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_PENDING)
            ->count();

        $totalLessons = Enrollment::with('course.topics.lessons')
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->get()
            ->sum(function (Enrollment $enrollment) {
                return $enrollment->course?->topics->sum(fn ($topic) => $topic->lessons->count()) ?? 0;
            });

        $enrollmentStatuses = in_array($status, [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED, Enrollment::STATUS_PENDING], true)
            ? [$status]
            : [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED];

        $recentEnrollments = Enrollment::with(['course.instructor', 'course.topics.lessons'])
            ->where('user_id', $user->id)
            ->whereIn('status', $enrollmentStatuses)
            ->when($dateWindow, fn ($q) => $q->whereBetween('enrolled_at', $dateWindow))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('course.instructor', fn ($i) => $i->where('name', 'like', "%{$search}%"));
            }))
            ->orderByDesc('enrolled_at')
            ->paginate($perPage, ['*'], 'courses_page')
            ->withQueryString()
            ->through(function (Enrollment $enrollment) {
                $enrollment->lessons_count = $enrollment->course?->topics->sum(fn ($topic) => $topic->lessons->count()) ?? 0;

                return $enrollment;
            });

        $recentInvoices = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title', 'branch:id,name'])
            ->where('user_id', $user->id)
            ->when(in_array($status, ['paid', 'partial', 'unpaid'], true), fn ($q) => $q->where('payment_status', $status))
            ->when($dateWindow, fn ($q) => $q->whereBetween('created_at', $dateWindow))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('control_number', 'like', "%{$search}%")
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'invoices_page')
            ->withQueryString();

        $stats = [
            'total_enrollments' => $totalEnrollments,
            'completed_courses' => $completedCourses,
            'pending_enrollments' => $pendingEnrollments,
            'total_lessons' => $totalLessons,
        ];

        $firstName = Str::of($user->name)->before(' ');
        $firstName = $firstName === '' ? $user->name : $firstName;

        $statusOptions = [
            'all' => 'All Statuses',
            'approved' => 'Active / Approved',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'paid' => 'Paid Invoices',
            'partial' => 'Partial Invoices',
            'unpaid' => 'Unpaid Invoices',
        ];

        return view('student.dashboard', compact(
            'user',
            'stats',
            'recentEnrollments',
            'recentInvoices',
            'firstName',
            'upcomingLiveClass',
            'filters',
            'statusOptions'
        ));
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
