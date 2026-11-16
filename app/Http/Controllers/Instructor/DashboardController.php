<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Support\DashboardFilters;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Dashboard Controller
 *
 * This controller handles the instructor dashboard functionality with filtering and card pagination.
 *
 * @package App\Http\Controllers\Instructor
 * @author Renatus Bernardo
 */
class DashboardController extends Controller
{
    /**
     * Display the instructor dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $filters = DashboardFilters::fromRequest($request);
        $dateWindow = DashboardFilters::dateWindow($filters['date_range']);
        $search = $filters['search'];
        $status = $filters['status'];
        $perPage = $filters['per_page'];

        $coursesQuery = $user->courses();

        $stats = [
            'courses' => (clone $coursesQuery)->count(),
            'students' => $this->countUniqueStudents($user->id),
            'average_rating' => $this->averageRating($user->id),
            'earnings' => $this->totalEarnings($user->id),
        ];

        $recentCourses = $user->courses()
            ->with(['topics.lessons:id,course_topic_id'])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
            ])
            ->withAvg('reviews as average_rating', 'rating')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when(in_array($status, ['published', 'draft', 'pending'], true), fn ($q) => $q->where('status', $status))
            ->when($dateWindow, fn ($q) => $q->whereBetween('updated_at', $dateWindow))
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'courses_page')
            ->withQueryString();

        $activities = $this->recentActivity($user->id, $search, $dateWindow, $perPage);

        $statCards = [
            [
                'icon' => 'fa-solid fa-graduation-cap',
                'class' => 'stat-icon-1',
                'label' => 'Total Courses',
                'value' => number_format($stats['courses']),
            ],
            [
                'icon' => 'fa-solid fa-users',
                'class' => 'stat-icon-2',
                'label' => 'Unique Students',
                'value' => number_format($stats['students']),
            ],
            [
                'icon' => 'fa-solid fa-star',
                'class' => 'stat-icon-3',
                'label' => 'Average Rating',
                'value' => $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '—',
            ],
            [
                'icon' => 'fa-solid fa-dollar-sign',
                'class' => 'stat-icon-4',
                'label' => 'Total Earnings',
                'value' => $stats['earnings'] > 0 ? currency_format($stats['earnings']) : currency_format(0),
            ],
        ];

        $statusOptions = [
            'all' => 'All Statuses',
            'published' => 'Published',
            'draft' => 'Draft',
            'pending' => 'Pending Review',
        ];

        return view('instructor.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentCourses' => $recentCourses,
            'activities' => $activities,
            'statCards' => $statCards,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Count unique students.
     */
    private function countUniqueStudents(int $instructorId): int
    {
        return Enrollment::query()
            ->whereIn('status', [
                Enrollment::STATUS_APPROVED,
                Enrollment::STATUS_COMPLETED,
            ])
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Average rating.
     */
    private function averageRating(int $instructorId): float
    {
        $rating = Review::query()
            ->where('is_approved', true)
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->avg('rating');

        return $rating ? round($rating, 1) : 0.0;
    }

    /**
     * Total earnings.
     */
    private function totalEarnings(int $instructorId): float
    {
        $amount = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereHas('enrollment.course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->sum('instructor_earning');

        return (float) $amount;
    }

    /**
     * Recent activity.
     */
    private function recentActivity(int $instructorId, string $search, ?array $dateWindow, int $perPage): LengthAwarePaginator
    {
        $enrollments = Enrollment::query()
            ->withoutGlobalScope('branch')
            ->with(['user:id,name', 'course:id,title'])
            ->whereIn('status', [
                Enrollment::STATUS_APPROVED,
                Enrollment::STATUS_COMPLETED,
            ])
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->when($dateWindow, fn ($q) => $q->whereBetween('enrolled_at', $dateWindow))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest('enrolled_at')
            ->take(20)
            ->get()
            ->toBase()
            ->map(fn ($enrollment) => [
                'icon' => 'fa-solid fa-user-plus',
                'title' => 'New student enrolled',
                'description' => sprintf('%s enrolled in "%s"', $enrollment->user?->name ?? 'A student', $enrollment->course?->title ?? 'your course'),
                'timestamp' => $enrollment->enrolled_at ?? $enrollment->created_at,
            ]);

        $reviews = Review::query()
            ->with(['user:id,name', 'course:id,title'])
            ->where('is_approved', true)
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->when($dateWindow, fn ($q) => $q->whereBetween('created_at', $dateWindow))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            }))
            ->latest()
            ->take(15)
            ->get()
            ->toBase()
            ->map(fn ($review) => [
                'icon' => 'fa-solid fa-star',
                'title' => 'New review received',
                'description' => sprintf('%s rated "%s" %d stars', $review->user?->name ?? 'A learner', $review->course?->title ?? 'your course', (int) $review->rating),
                'timestamp' => $review->created_at,
            ]);

        $merged = $enrollments
            ->merge($reviews)
            ->sortByDesc('timestamp')
            ->map(function ($activity) {
                $activity['relative_time'] = $activity['timestamp']
                    ? Carbon::parse($activity['timestamp'])->diffForHumans()
                    : null;

                return $activity;
            })
            ->values();

        return DashboardFilters::paginateCollection($merged, $perPage, 'activity_page');
    }
}
