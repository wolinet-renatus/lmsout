<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * My Courses Controller
 *
 * Handles student enrolled course listings with real-time search,
 * status filtering, and dynamic pagination.
 *
 * @package App\Http\Controllers\Student
 * @author Renatus Bernardo
 */
class MyCoursesController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $this->currentUser();

        // Base Query
        $query = Enrollment::with(['course.instructor', 'course.topics.lessons', 'payment'])
            ->where('user_id', $user->id);

        // Tab count calculations across all user enrollments
        $statusCounts = [
            'all' => (clone $query)->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])->count(),
            'in_progress' => (clone $query)->where('status', Enrollment::STATUS_APPROVED)->count(),
            'completed' => (clone $query)->where('status', Enrollment::STATUS_COMPLETED)->count(),
        ];

        // Status Filter
        $status = $request->input('status', 'all');
        if ($status === 'completed') {
            $query->where('status', Enrollment::STATUS_COMPLETED);
        } elseif ($status === 'in_progress') {
            $query->where('status', Enrollment::STATUS_APPROVED);
        } elseif ($status === 'pending') {
            $query->where('status', Enrollment::STATUS_PENDING);
        } else {
            $query->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED]);
        }

        // Real-Time Search Query (Course Title, Category, or Instructor Name)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('course', function ($cq) use ($search) {
                    $cq->where('title', 'like', "%{$search}%")
                       ->orWhere('category', 'like', "%{$search}%");
                })->orWhereHas('course.instructor', function ($iq) use ($search) {
                    $iq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        if ($sort === 'oldest') {
            $query->orderBy('enrolled_at', 'asc');
        } elseif ($sort === 'title_asc') {
            $query->join('courses', 'enrollments.course_id', '=', 'courses.id')
                  ->orderBy('courses.title', 'asc')
                  ->select('enrollments.*');
        } elseif ($sort === 'title_desc') {
            $query->join('courses', 'enrollments.course_id', '=', 'courses.id')
                  ->orderBy('courses.title', 'desc')
                  ->select('enrollments.*');
        } else {
            $query->orderByDesc('enrolled_at');
        }

        // Configurable pagination (default 3 per page as requested)
        $perPage = max(3, (int) $request->input('per_page', 3));

        $enrollments = $query->paginate($perPage)->withQueryString();

        $transformed = $enrollments->getCollection()->values()->map(function (Enrollment $enrollment, int $index) {
            $course = $enrollment->course;

            return [
                'id' => $enrollment->id,
                'course_id' => $course?->id,
                'course_title' => $course?->title ?? 'Untitled Course',
                'course_category' => $course?->category ? ucfirst($course->category) : null,
                'is_live_course' => $course?->is_live_course ?? false,
                'featured_image' => $course?->featured_image,
                'intro_video' => $course?->intro_video,
                'status' => $enrollment->status,
                'price_label' => $this->priceLabel($course),
                'lesson_count' => $course?->topics->sum(fn ($topic) => $topic->lessons->count()) ?? 0,
                'max_students' => $course?->max_students,
                'instructor_name' => $course?->instructor->name ?? 'OUT Faculty',
                'instructor_avatar' => $course?->instructor->profile_photo,
                'enrolled_at' => $enrollment->enrolled_at,
                'animation_delay' => ($index % 6) * 0.2,
                'is_pending' => $enrollment->status === Enrollment::STATUS_PENDING,
                'is_approved' => $enrollment->status === Enrollment::STATUS_APPROVED,
                'is_completed' => $enrollment->status === Enrollment::STATUS_COMPLETED,
            ];
        });

        $enrollments->setCollection($transformed);

        // Instant AJAX Response for real-time search, filter tabs, per-page switch, or pagination
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('student.partials.my-courses-list', compact('enrollments'))->render(),
                'pagination' => $enrollments->links()->render(),
                'total' => $enrollments->total(),
                'from' => $enrollments->firstItem() ?? 0,
                'to' => $enrollments->lastItem() ?? 0,
                'hasPages' => $enrollments->hasPages(),
                'statusCounts' => $statusCounts,
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
            ]);
        }

        return view('student.my-courses', [
            'enrollments' => $enrollments,
            'user' => $user,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'currentSearch' => $request->input('search', ''),
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Price label.
     */
    private function priceLabel($course): string
    {
        if (! $course) {
            return 'Free';
        }

        if ($course->pricing_model === 'free') {
            return 'Free';
        }

        if ($course->sale_price) {
            return '$' . number_format($course->sale_price, 2);
        }

        return '$' . number_format($course->regular_price ?? 0, 2);
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
