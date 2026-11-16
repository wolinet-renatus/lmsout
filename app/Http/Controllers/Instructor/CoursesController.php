<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Courses Controller
 *
 * Handles instructor course listings with real-time search,
 * status filtering, sorting, and dynamic pagination.
 *
 * @package App\Http\Controllers\Instructor
 * @author Renatus Bernardo
 */
class CoursesController extends Controller
{
    /**
     * Display all courses for the instructor
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $scopeFilter = function ($q) use ($user) {
            $q->where('instructor_id', $user->id)
                ->orWhere('assistant_instructor_id', $user->id)
                ->orWhereHas('coInstructors', fn ($co) => $co->where('user_id', $user->id));
        };

        $baseQuery = Course::where($scopeFilter);

        // Live counts for filter tabs
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'published' => (clone $baseQuery)->where('status', 'published')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
        ];

        $query = Course::where($scopeFilter)
            ->with([
                'topics.lessons:id,course_topic_id',
                'reviews' => fn ($q) => $q->select('id', 'course_id', 'rating', 'is_approved')->where('is_approved', true),
            ])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
            ]);

        // Status filter
        $status = $request->input('status', 'all');
        if (in_array($status, ['published', 'pending', 'draft'], true)) {
            $query->where('status', $status);
        }

        // Real-time search (title or category)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'title_asc') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'title_desc') {
            $query->orderBy('title', 'desc');
        } elseif ($sort === 'students_desc') {
            $query->orderByDesc('students_count');
        } else {
            $query->orderByDesc('created_at');
        }

        // Configurable pagination (at least 3 per page)
        $perPage = max(3, (int) $request->input('per_page', 3));

        $courses = $query->paginate($perPage)->withQueryString();

        $transformed = $courses->getCollection()->values()->map(function ($course) {
            $totalLessons = $course->topics->sum(fn ($topic) => $topic->lessons->count());

            $displayPrice = $course->pricing_model === 'free'
                ? 0
                : ($course->sale_price ?? $course->regular_price ?? 0);

            $imageUrl = $course->featured_image
                ? asset('storage/' . $course->featured_image)
                : asset('assets/front/img/home-1/courses/courses-01.jpg');

            $averageRating = $course->reviews->avg('rating');
            $ratingValue = $averageRating ? round($averageRating, 1) : null;
            $ratingForStars = $ratingValue ? round($ratingValue * 2) / 2 : 0;

            return [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category ? ucfirst($course->category) : 'Uncategorized',
                'image_url' => $imageUrl,
                'featured_image' => $course->featured_image,
                'rating' => $ratingValue,
                'rating_for_stars' => $ratingForStars,
                'lessons' => $totalLessons,
                'students' => (int) $course->students_count,
                'price' => $displayPrice > 0 ? $course->formatPrice($displayPrice) : currency_format(0),
                'pricing_model' => $course->pricing_model,
                'status' => $course->status ?? 'draft',
                'is_live_course' => (bool) $course->is_live_course,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
            ];
        });

        $courses->setCollection($transformed);

        // Instant AJAX response for real-time search, filter tabs, per-page change, or pagination
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('instructor.partials.courses-list', compact('courses', 'statusCounts'))->render(),
                'pagination' => $courses->links()->render(),
                'total' => $courses->total(),
                'from' => $courses->firstItem() ?? 0,
                'to' => $courses->lastItem() ?? 0,
                'hasPages' => $courses->hasPages(),
                'statusCounts' => $statusCounts,
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
            ]);
        }

        return view('instructor.courses', [
            'user' => $user,
            'courses' => $courses,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'currentSearch' => $request->input('search', ''),
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }
}
