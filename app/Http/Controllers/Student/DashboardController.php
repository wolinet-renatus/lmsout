<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ChatService;
use App\Services\LiveClassService;
use App\Services\SrsWorkflowService;
use App\Support\DashboardFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Dashboard Controller
 *
 * This controller handles the student dashboard functionality with filtering,
 * internal communications, live notifications, and card pagination.
 *
 * @package App\Http\Controllers\Student
 * @author Renatus Bernardo
 */
class DashboardController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        protected LiveClassService $liveClassService,
        protected ChatService $chatService
    ) {}

    /**
     * Display the student dashboard
     */
    public function index(Request $request)
    {
        $user = $this->currentUser();
        app(SrsWorkflowService::class)->syncMissingInvoicesForStudent($user);

        $filters = DashboardFilters::fromRequest($request);
        $dateWindow = DashboardFilters::dateWindow($filters['date_range']);
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];

        $upcomingLiveClass = $this->liveClassService->getUpcomingForStudent($user);
        $kpis = $this->buildKpis($user);

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
            ->ownedByUser($user)
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

        $recentNotifications = $user->notifications()->latest()->limit(6)->get();

        $recentConversations = Conversation::query()
            ->with(['instructor:id,name,profile_photo', 'latestMessage'])
            ->where('student_id', $user->id)
            ->where('is_closed', false)
            ->orderByDesc('last_message_at')
            ->limit(4)
            ->get()
            ->map(function (Conversation $conv) use ($user) {
                $conv->unread_count = $conv->unreadCountFor($user->id);

                return $conv;
            });

        $upcomingCampusSchedule = Enrollment::query()
            ->with(['course:id,title', 'courseAssignment.venue:id,name', 'courseAssignment.instructor:id,name', 'venue:id,name'])
            ->where('user_id', $user->id)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_PENDING])
            ->whereHas('course', fn ($q) => $q->whereIn('delivery_mode', ['face_to_face', 'hybrid']))
            ->latest('id')
            ->first();

        $stats = $kpis;

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
            'recentNotifications',
            'recentConversations',
            'upcomingCampusSchedule',
            'firstName',
            'upcomingLiveClass',
            'filters',
            'statusOptions'
        ));
    }

    /**
     * Return live dashboard summary for real-time KPIs, invoices, and notification feed syncing.
     */
    public function liveSummary(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        app(SrsWorkflowService::class)->syncMissingInvoicesForStudent($user);
        $kpis = $this->buildKpis($user);

        $notifications = $user->notifications()
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($notification): array {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => $notification->id,
                    'title' => (string) ($data['title'] ?? 'Notification'),
                    'message' => (string) ($data['message'] ?? ''),
                    'type' => (string) ($data['type'] ?? 'info'),
                    'url' => $data['url'] ?? null,
                    'read_url' => route('student.notifications.read', $notification->id),
                    'is_unread' => $notification->read_at === null,
                    'time_ago' => $notification->created_at?->diffForHumans() ?? 'Just now',
                ];
            })
            ->values();

        $invoices = Invoice::withoutGlobalScope('branch')
            ->with(['course:id,title'])
            ->ownedByUser($user)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Invoice $inv): array => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'control_number' => $inv->control_number ?: '-',
                'course_title' => Str::limit($inv->course?->title ?? 'Course Fee', 28),
                'balance_formatted' => currency_format($inv->balance),
                'payment_status' => $inv->payment_status,
            ])
            ->values();

        return response()->json([
            'stats' => [
                'total_enrollments' => $kpis['total_enrollments'],
                'completed_courses' => $kpis['completed_courses'],
                'pending_enrollments' => $kpis['pending_enrollments'],
                'total_lessons' => $kpis['total_lessons'],
                'outstanding_balance' => $kpis['outstanding_balance'],
                'outstanding_balance_formatted' => currency_format($kpis['outstanding_balance']),
                'unread_notifications' => $kpis['unread_notifications'],
                'unread_messages' => $kpis['unread_messages'],
            ],
            'notifications' => $notifications,
            'invoices' => $invoices,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Compute student dashboard KPI metrics from database records.
     *
     * @return array<string, int|float>
     */
    private function buildKpis(User $user): array
    {
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

        $outstandingBalance = (float) Invoice::withoutGlobalScope('branch')
            ->ownedByUser($user)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('balance');

        return [
            'total_enrollments' => $totalEnrollments,
            'completed_courses' => $completedCourses,
            'pending_enrollments' => $pendingEnrollments,
            'total_lessons' => $totalLessons,
            'outstanding_balance' => round($outstandingBalance, 2),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'unread_messages' => $this->chatService->getUnreadCount($user->id, 'student'),
        ];
    }

    /**
     * Current user.
     */
    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
