<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Courses Controller
 *
 * This controller handles the courses functionality.
 *
 * @package App\Http\Controllers\Front
 * @author Renatus Bernardo
 */
class CoursesController extends Controller
{
    /**
     * Display all published courses
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'topics.lessons'])
            ->where('status', 'published')
            ->where('visibility', 'public');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        if ($request->has('instructor') && $request->instructor) {
            $query->whereHas('instructor', function ($q) use ($request) {
                $q->where('id', $request->instructor);
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->has('price') && $request->price) {
            if ($request->price === 'free') {
                $query->where('pricing_model', 'free');
            } elseif ($request->price === 'paid') {
                $query->where('pricing_model', 'paid');
            }
        }

        if ($request->filled('level') && $request->level !== 'all') {
            $query->whereRaw('LOWER(difficulty) = ?', [strtolower($request->level)]);
        }

        if ($request->filled('delivery_mode') && in_array($request->delivery_mode, ['online', 'face_to_face', 'hybrid'], true)) {
            $query->where('delivery_mode', $request->delivery_mode);
        }

        if ($request->filled('rating')) {
            $desiredRating = (float) $request->rating;

            $query->where(function ($q) use ($desiredRating) {
                $q->whereHas('reviews', function ($reviewQuery) use ($desiredRating) {
                    $reviewQuery->select('course_id')
                        ->groupBy('course_id')
                        ->havingRaw('AVG(rating) >= ?', [$desiredRating]);
                });
            });
        }

        $usdToTzsRate = usd_to_tzs_rate();

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'popular':
                $query->withCount(['enrollments as popular_count' => function ($q) {
                    $q->whereIn('status', ['approved', 'completed']);
                }])->orderByDesc('popular_count');
                break;
            case 'price_asc':
                $query->orderByRaw('CASE WHEN pricing_model = "free" THEN 0 ELSE (COALESCE(sale_price, regular_price, 0) * CASE WHEN price_currency = "USD" THEN ? ELSE 1 END) END ASC', [$usdToTzsRate]);
                break;
            case 'price_desc':
                $query->orderByRaw('CASE WHEN pricing_model = "free" THEN 0 ELSE (COALESCE(sale_price, regular_price, 0) * CASE WHEN price_currency = "USD" THEN ? ELSE 1 END) END DESC', [$usdToTzsRate]);
                break;
            case 'rating':
                $query->withAvg('reviews as avg_rating_sort', 'rating')->orderByDesc('avg_rating_sort');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $courses = $query->paginate(12);

        $courses->load(['reviews']);
        $courses->getCollection()->transform(function (Course $course) {
            $reviews = $course->reviews ?? collect();
            $averageRating = $reviews->count() ? round($reviews->avg('rating'), 1) : 5.0;
            $course->setAttribute('average_rating', $averageRating);
            $course->setAttribute('rating_count', $reviews->count());

            $lessonCount = $course->topics->sum(function ($topic) {
                return $topic->lessons->count();
            });
            $totalMinutes = $course->topics->sum(function ($topic) {
                return $topic->lessons->sum(function ($lesson) {
                    return (int) ($lesson->duration ?? 25);
                });
            });
            if ($totalMinutes <= 0 && $lessonCount > 0) {
                $totalMinutes = $lessonCount * 25;
            }
            $course->setAttribute('computed_lessons_count', $lessonCount);
            $course->setAttribute('computed_duration_minutes', $totalMinutes);

            return $course;
        });

        $meta = frontend_meta('courses', null, []);

        $categories = Course::where('status', 'published')
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get();

        $languages = Course::where('status', 'published')
            ->whereNotNull('language')
            ->selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->get();

        $instructors = User::where('role', 'instructor')
            ->where('is_approved', true)
            ->whereHas('courses', function ($q) {
                $q->where('status', 'published');
            })
            ->select('id', 'name')
            ->get();

        $ratingCounts = Course::where('status', 'published')
            ->where('visibility', 'public')
            ->with('reviews')
            ->get()
            ->map(function (Course $course) {
                $reviews = $course->reviews ?? collect();
                $rounded = round($reviews->avg('rating'));

                return $rounded > 0 ? $rounded : null;
            })
            ->filter()
            ->countBy()
            ->map(function ($count, $rating) {
                return (int) $count;
            })
            ->toArray();

        if ($request->ajax() || $request->expectsJson()) {
            $coursesHtml = view('courses.partials.course-list', compact('courses'))->render();
            $paginationHtml = view('courses.partials.pagination', compact('courses'))->render();

            return response()->json([
                'success' => true,
                'courses_html' => $coursesHtml,
                'pagination_html' => $paginationHtml,
                'total' => $courses->total(),
            ]);
        }

        return view('courses.index', [
            'pageTitle' => $meta['title'] ?? 'Courses',
            'metaDescription' => $meta['description'] ?? null,
            'courses' => $courses,
            'categories' => $categories,
            'languages' => $languages,
            'instructors' => $instructors,
            'ratingCounts' => $ratingCounts,
        ]);
    }

    /**
     * Display course details
     */
    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'topics.lessons',
            'topics.quizzes.questions',
            'topics.assignments.files',
            'reviews.user',
            'reviews.replies.user',
        ])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->findOrFail($id);

        $lessonCount = $course->topics->sum(function ($topic) {
            return $topic->lessons->count();
        });

        $totalDuration = $course->topics->sum(function ($topic) {
            return $topic->lessons->sum(function ($lesson) {
                return (int) ($lesson->duration ?? 20);
            });
        });
        if ($totalDuration <= 0 && $lessonCount > 0) {
            $totalDuration = $lessonCount * 20;
        }

        $courseStartDate = null;
        if ($course->schedule_enabled && $course->schedule_date && $course->schedule_date->isFuture()) {
            $courseStartDate = $course->schedule_date;
        }

        $isEnrolled = false;
        $userReview = null;
        $canReview = false;

        if (Auth::check()) {
            $isEnrolled = Enrollment::where('user_id', Auth::id())
                ->where('course_id', $course->id)
                ->whereIn('status', ['approved', 'completed'])
                ->exists();

            if ($isEnrolled) {
                $canReview = true;
                $userReview = Review::where('course_id', $course->id)
                    ->where('user_id', Auth::id())
                    ->first();
            }
        }

        $reviews = $course->reviews;
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 5.0;

        $ratingDistribution = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        $relatedCourses = Course::with(['instructor', 'topics.lessons', 'reviews'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where('id', '!=', $course->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$course->category ?? ''])
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->map(function (Course $relCourse) {
                $relReviews = $relCourse->reviews ?? collect();
                $relCourse->setAttribute('average_rating', $relReviews->count() ? round($relReviews->avg('rating'), 1) : 5.0);
                $relCourse->setAttribute('rating_count', $relReviews->count());
                $relLessons = $relCourse->topics->sum(fn ($t) => $t->lessons->count());
                $relMins = $relCourse->topics->sum(fn ($t) => $t->lessons->sum(fn ($l) => (int) ($l->duration ?? 25)));
                $relCourse->setAttribute('computed_lessons_count', $relLessons);
                $relCourse->setAttribute('computed_duration_minutes', $relMins > 0 ? $relMins : ($relLessons * 25));

                return $relCourse;
            });

        $metaDescription = $course->meta_description
            ?? Str::limit(strip_tags((string) ($course->description ?? $course->summary ?? '')), 160);

        return view('courses.show', [
            'pageTitle' => $course->title,
            'metaDescription' => $metaDescription,
            'course' => $course,
            'lessonCount' => $lessonCount,
            'totalDuration' => $totalDuration,
            'courseStartDate' => $courseStartDate,
            'isEnrolled' => $isEnrolled,
            'canReview' => $canReview,
            'userReview' => $userReview,
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'ratingDistribution' => $ratingDistribution,
            'relatedCourses' => $relatedCourses,
        ]);
    }

    /**
     * Real-time live search across Courses and Instructors.
     */
    public function liveSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $coursesQuery = Course::with('instructor')
            ->where('status', 'published')
            ->where('visibility', 'public');

        if ($q !== '') {
            $coursesQuery->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('tags', 'like', "%{$q}%");
            });
        }

        $courses = $coursesQuery
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Course $course) {
                $isFree = ($course->pricing_model ?? 'paid') === 'free'
                    || ((float) ($course->sale_price ?? $course->regular_price ?? 0) <= 0);
                $effectivePrice = (float) ($course->sale_price ?? $course->regular_price ?? 0);

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'category' => $course->category ?: 'General',
                    'instructor' => optional($course->instructor)->name ?: 'Instructor',
                    'price_label' => $isFree ? 'Free' : $course->formatPrice($effectivePrice, 2, 'symbol'),
                    'is_free' => $isFree,
                    'thumbnail' => $course->thumbnail ?: asset('assets/front/img/home-1/courses/courses-01.jpg'),
                    'url' => route('courses.show', $course->id),
                ];
            })
            ->values();

        $instructors = collect();
        if ($q !== '') {
            $instructors = User::query()
                ->where('role', 'instructor')
                ->where('is_approved', true)
                ->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('specialization', 'like', "%{$q}%")
                        ->orWhere('bio', 'like', "%{$q}%");
                })
                ->withCount(['courses as published_courses_count' => function ($sub) {
                    $sub->where('status', 'published')->where('visibility', 'public');
                }])
                ->limit(3)
                ->get()
                ->map(function (User $instructor) {
                    return [
                        'id' => $instructor->id,
                        'name' => $instructor->name,
                        'headline' => $instructor->specialization ?: 'Expert Instructor',
                        'courses_count' => (int) ($instructor->published_courses_count ?? 0),
                        'avatar' => frontend_media($instructor->profile_photo, 'assets/front/img/home-1/team/team-01.jpg'),
                        'url' => route('instructors.show', $instructor->id),
                    ];
                })
                ->values();
        }

        return response()->json([
            'query' => $q,
            'courses' => $courses,
            'instructors' => $instructors,
            'all_courses_url' => route('courses', $q !== '' ? ['search' => $q] : []),
        ]);
    }
}
