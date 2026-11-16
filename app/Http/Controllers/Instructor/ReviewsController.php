<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\ReviewReply;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Reviews Controller
 *
 * This controller handles the reviews functionality.
 *
 * @package App\Http\Controllers\Instructor
 * @author Renatus Bernardo
 */
class ReviewsController extends Controller
{
    /**
     * Display instructor reviews
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $rating = (string) $request->input('rating', 'all');
        $courseId = $request->integer('course_id');
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'latest');
        $perPage = max(3, (int) $request->input('per_page', 3));

        $courses = Course::where('instructor_id', $user->id)
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get();

        $baseQuery = Review::query()
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->where('is_approved', true);

        if ($courseId) {
            $baseQuery->where('course_id', $courseId);
        }

        $allForStats = (clone $baseQuery)->with('replies')->get();
        $stats = [
            'total_reviews' => $allForStats->count(),
            'average_rating' => $allForStats->count() > 0 ? round($allForStats->avg('rating'), 1) : 0,
            'five_star' => $allForStats->where('rating', 5)->count(),
            'four_star' => $allForStats->where('rating', 4)->count(),
            'three_star' => $allForStats->where('rating', 3)->count(),
            'two_star' => $allForStats->where('rating', 2)->count(),
            'one_star' => $allForStats->where('rating', 1)->count(),
            'total_replies' => $allForStats->sum(fn($r) => $r->replies ? $r->replies->count() : 0),
        ];

        $ratingCounts = [
            'all' => $stats['total_reviews'],
            '5' => $stats['five_star'],
            '4' => $stats['four_star'],
            '3' => $stats['three_star'],
            '2' => $stats['two_star'],
            '1' => $stats['one_star'],
        ];

        $reviewsQuery = Review::with(['user', 'course', 'replies.user'])
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->where('is_approved', true);

        if ($courseId) {
            $reviewsQuery->where('course_id', $courseId);
        }

        if ($rating !== 'all' && in_array($rating, ['1', '2', '3', '4', '5'], true)) {
            $reviewsQuery->where('rating', (int) $rating);
        }

        if ($search !== '') {
            $reviewsQuery->where(function ($q) use ($search) {
                $q->where('comment', 'like', '%' . $search . '%')
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('course', fn($cq) => $cq->where('title', 'like', '%' . $search . '%'));
            });
        }

        switch ($sort) {
            case 'oldest':
                $reviewsQuery->orderBy('created_at', 'asc');
                break;
            case 'highest':
                $reviewsQuery->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'lowest':
                $reviewsQuery->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'latest':
            default:
                $reviewsQuery->orderBy('created_at', 'desc');
                break;
        }

        $reviews = $reviewsQuery->paginate($perPage)->withQueryString();

        $currentUserId = $user->id;
        $reviews->getCollection()->transform(function ($review) use ($currentUserId) {
            $hasReplied = false;
            if ($review->replies && $review->replies->count() > 0) {
                foreach ($review->replies as $reply) {
                    if ($reply->is_instructor && $reply->user_id == $currentUserId) {
                        $hasReplied = true;
                        break;
                    }
                }
            }
            $review->hasReplied = $hasReplied;
            return $review;
        });

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('instructor.partials.reviews-list', [
                'reviews' => $reviews,
                'currentSearch' => $search,
                'currentRating' => $rating,
                'selectedCourseId' => $courseId,
            ])->render();

            return response()->json([
                'html' => $html,
                'pagination' => (string) $reviews->links(),
                'ratingCounts' => $ratingCounts,
                'from' => $reviews->firstItem() ?? 0,
                'to' => $reviews->lastItem() ?? 0,
                'total' => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ]);
        }

        return view('instructor.reviews', [
            'user' => $user,
            'reviews' => $reviews,
            'stats' => $stats,
            'courses' => $courses,
            'ratingCounts' => $ratingCounts,
            'currentRating' => $rating,
            'selectedCourseId' => $courseId,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Store a reply to a review
     */
    public function reply(Request $request, $reviewId)
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            ToastMagic::error('You must be an approved instructor to reply to reviews.');
            return back();
        }

        $review = Review::with('course')
            ->where('id', $reviewId)
            ->whereHas('course', function($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->firstOrFail();

        $existingReply = ReviewReply::where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->first();

        if ($existingReply) {
            ToastMagic::warning('You have already replied to this review. You can update your existing reply.');
            return back();
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ], [
            'reply.required' => 'Please enter a reply.',
            'reply.max' => 'Reply cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        ReviewReply::create([
            'review_id' => $reviewId,
            'user_id' => $user->id,
            'reply' => $request->reply,
            'is_instructor' => true,
        ]);

        ToastMagic::success('Your reply has been posted successfully!');
        return back();
    }

    /**
     * Update an existing reply
     */
    public function updateReply(Request $request, $reviewId, $replyId)
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            ToastMagic::error('You must be an approved instructor to update replies.');
            return back();
        }

        $reply = ReviewReply::where('id', $replyId)
            ->where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ], [
            'reply.required' => 'Please enter a reply.',
            'reply.max' => 'Reply cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        try {
            $reply->update([
                'reply' => $request->reply,
            ]);

            ToastMagic::success('Your reply has been updated successfully!');
            return back();
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your reply. Please try again.');
            return back()->withInput();
        }
    }
}
