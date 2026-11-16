<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CommunityAnswer;
use App\Models\CommunityQuestion;
use App\Models\CommunityVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Community controller — handles student campus portal workflows and learning progress.
 *
 * @author Renatus Bernardo
 */
class CommunityController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $filter = (string) $request->get('filter', 'all');
        $search = trim((string) $request->get('search', ''));
        $sort = (string) $request->get('sort', 'latest');
        $perPage = max(3, (int) $request->get('per_page', 3));

        $statusCounts = [
            'all' => CommunityQuestion::count(),
            'unanswered' => CommunityQuestion::has('answers', '=', 0)->count(),
            'my_questions' => $user ? CommunityQuestion::where('user_id', $user->id)->count() : 0,
        ];

        $query = CommunityQuestion::with(['user:id,name,profile_photo'])
            ->withCount('answers')
            ->withSum('votes', 'vote');

        if ($user) {
            $query->with([
                'votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }
            ]);
        }

        if ($filter === 'unanswered') {
            $query->has('answers', '=', 0);
        } elseif ($filter === 'my_questions') {
            $query->where('user_id', $user ? $user->id : null);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'most_answered':
                $query->orderByDesc('answers_count')->orderByDesc('created_at');
                break;
            case 'most_voted':
                $query->orderByDesc('votes_sum_vote')->orderByDesc('created_at');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $questions = $query->paginate($perPage)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('student.partials.community-list', [
                'questions' => $questions,
                'currentSearch' => $search,
                'filter' => $filter,
            ])->render();

            return response()->json([
                'html' => $html,
                'pagination' => (string) $questions->links(),
                'statusCounts' => $statusCounts,
                'from' => $questions->firstItem() ?? 0,
                'to' => $questions->lastItem() ?? 0,
                'total' => $questions->total(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ]);
        }

        return view('student.community.index', [
            'questions' => $questions,
            'filter' => $filter,
            'user' => $user,
            'statusCounts' => $statusCounts,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Display the specified resource details.
     */
    public function show($id)
    {
        $user = Auth::user();

        $query = CommunityQuestion::with(['user:id,name,profile_photo', 'answers.user:id,name,profile_photo'])
            ->withCount('answers')
            ->withSum('votes', 'vote');

        if ($user) {
            $query->with([
                'votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }
            ]);

            $query->with([
                'answers' => function ($q) use ($user) {
                    $q->withSum('votes', 'vote')
                        ->with([
                            'votes' => function ($v) use ($user) {
                                $v->where('user_id', $user->id);
                            }
                        ]);
                }
            ]);
        } else {
            $query->with([
                'answers' => function ($q) {
                    $q->withSum('votes', 'vote');
                }
            ]);
        }

        $question = $query->findOrFail($id);
        $question->increment('views');

        return view('student.community.show', [
            'question' => $question,
            'user' => $user
        ]);
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|string', // Web usually sends comma-separated tags
        ]);

        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $question = CommunityQuestion::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'tags' => $tags,
        ]);

        return redirect()->route('student.community.show', $question->id)
            ->with('success', 'Question posted successfully');
    }

    /**
     * Store answer.
     */
    public function storeAnswer(Request $request, $questionId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $question = CommunityQuestion::findOrFail($questionId);

        CommunityAnswer::create([
            'user_id' => Auth::id(),
            'question_id' => $question->id,
            'content' => $request->input('content'),
        ]);

        return redirect()->back()->with('success', 'Answer posted successfully');
    }

    /**
     * Mark answer accepted.
     */
    public function markAnswerAccepted($id)
    {
        $answer = CommunityAnswer::with('question')->findOrFail($id);

        if ($answer->question->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        CommunityAnswer::where('question_id', $answer->question_id)
            ->update(['is_accepted' => false]);

        $answer->update(['is_accepted' => true]);

        return redirect()->back()->with('success', 'Answer marked as accepted');
    }

    /**
     * Vote.
     */
    public function vote(Request $request, $type, $id)
    {
        $request->validate([
            'vote' => 'required|in:1,-1,0',
        ]);

        $userId = Auth::id();
        $votableType = $type === 'question' ? CommunityQuestion::class : CommunityAnswer::class;

        if ($request->vote == 0) {
            CommunityVote::where([
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ])->delete();

            return response()->json(['success' => true, 'message' => 'Vote removed']);
        }

        CommunityVote::updateOrCreate(
            [
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ],
            ['vote' => $request->vote]
        );

        return response()->json(['success' => true, 'message' => 'Vote recorded']);
    }
}
