<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateAssignmentSubmissionRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Services\OriginalityCheckService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assignments Controller
 *
 * This controller handles the assignments functionality.
 *
 * @package App\Http\Controllers\Instructor
 * @author Renatus Bernardo
 */
class AssignmentsController extends Controller
{
    /**
     * Display the assignments index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $courses = $user->courses()
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get();

        $courseId = $request->integer('course_id');
        $status = (string) $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'latest');
        $perPage = max(3, (int) $request->input('per_page', 3));

        $baseQuery = Assignment::query()
            ->with([
                'topic.course:id,title,instructor_id',
            ])
            ->withCount([
                'submissions as submissions_count',
                'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
                'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
                'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
            ])
            ->whereHas('topic.course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            });

        if ($courseId) {
            $baseQuery->whereHas('topic.course', function ($query) use ($courseId) {
                $query->where('id', $courseId);
            });
        }

        $assignmentIds = (clone $baseQuery)
            ->select('assignments.id')
            ->pluck('assignments.id');

        $summary = $this->summarizeAssignments($assignmentIds);

        $statusCounts = [
            'all' => $assignmentIds->count(),
            'pending' => (clone $baseQuery)->whereHas('submissions', fn($q) => $q->where('status', AssignmentSubmission::STATUS_PENDING))->count(),
            'graded' => (clone $baseQuery)->whereHas('submissions', fn($q) => $q->where('status', AssignmentSubmission::STATUS_GRADED))->count(),
            'no_submissions' => (clone $baseQuery)->whereDoesntHave('submissions')->count(),
        ];

        $assignmentsQuery = clone $baseQuery;

        if ($status === 'pending') {
            $assignmentsQuery->whereHas('submissions', fn($q) => $q->where('status', AssignmentSubmission::STATUS_PENDING));
        } elseif ($status === 'graded') {
            $assignmentsQuery->whereHas('submissions', fn($q) => $q->where('status', AssignmentSubmission::STATUS_GRADED));
        } elseif ($status === 'no_submissions') {
            $assignmentsQuery->whereDoesntHave('submissions');
        }

        if ($search !== '') {
            $assignmentsQuery->where(function ($q) use ($search) {
                $q->where('assignments.title', 'like', '%' . $search . '%')
                    ->orWhere('assignments.description', 'like', '%' . $search . '%')
                    ->orWhereHas('topic.course', function ($cq) use ($search) {
                        $cq->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        switch ($sort) {
            case 'oldest':
                $assignmentsQuery->orderBy('assignments.created_at', 'asc');
                break;
            case 'title_asc':
                $assignmentsQuery->orderBy('assignments.title', 'asc');
                break;
            case 'title_desc':
                $assignmentsQuery->orderBy('assignments.title', 'desc');
                break;
            case 'pending_desc':
                $assignmentsQuery->orderByDesc('pending_submissions_count')->orderByDesc('assignments.created_at');
                break;
            case 'latest':
            default:
                $assignmentsQuery->orderBy('assignments.created_at', 'desc');
                break;
        }

        $assignments = $assignmentsQuery
            ->paginate($perPage)
            ->withQueryString();

        $assignments->getCollection()->transform(function (Assignment $assignment) {
            $raw = $assignment->description ?? $assignment->instructions ?? 'No description provided.';
            $assignment->short_description = Str::limit(strip_tags($raw), 140);

            return $assignment;
        });

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('instructor.partials.assignments-list', [
                'assignments' => $assignments,
                'currentSearch' => $search,
                'currentStatus' => $status,
                'selectedCourseId' => $courseId,
            ])->render();

            return response()->json([
                'html' => $html,
                'pagination' => (string) $assignments->links(),
                'statusCounts' => $statusCounts,
                'summary' => $summary,
                'from' => $assignments->firstItem() ?? 0,
                'to' => $assignments->lastItem() ?? 0,
                'total' => $assignments->total(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
            ]);
        }

        return view('instructor.assignments.index', [
            'user' => $user,
            'assignments' => $assignments,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Display the assignment show page
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Assignment $assignment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function show(Request $request, Assignment $assignment): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $assignment->loadMissing('topic.course');
        $this->authorizeAssignment($assignment, $user->id);

        $assignment->loadCount([
            'submissions as submissions_count',
            'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
            'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
            'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
        ]);

        $status = $request->input('status');
        $search = $request->input('search');

        $submissionsQuery = AssignmentSubmission::query()
            ->with(['user', 'files', 'enrollment'])
            ->where('assignment_id', $assignment->id)
            ->whereHas('assignment.topic.course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            });

        if ($status) {
            $submissionsQuery->where('status', $status);
        }

        if ($search) {
            $submissionsQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $submissions = $submissionsQuery
            ->orderByDesc('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $statusOptions = [
            AssignmentSubmission::STATUS_PENDING => 'Pending',
            AssignmentSubmission::STATUS_GRADED => 'Graded',
            AssignmentSubmission::STATUS_RETURNED => 'Returned',
        ];

        $statusClasses = [
            AssignmentSubmission::STATUS_PENDING => 'pending',
            AssignmentSubmission::STATUS_GRADED => 'graded',
            AssignmentSubmission::STATUS_RETURNED => 'returned',
        ];

        $submissions->getCollection()->transform(function (AssignmentSubmission $submission) use ($statusClasses) {
            $submission->status_class = $statusClasses[$submission->status] ?? 'pending';

            return $submission;
        });

        return view('instructor.assignments.show', [
            'user' => $user,
            'assignment' => $assignment,
            'submissions' => $submissions,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'statusOptions' => $statusOptions,
            'statusClasses' => $statusClasses,
        ]);
    }

    /**
     * Display the assignment submission show page
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\AssignmentSubmission $submission
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function showSubmission(Request $request, AssignmentSubmission $submission): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $submission->loadMissing([
            'assignment.topic.course',
            'user',
            'files',
        ]);

        $this->authorizeAssignment($submission->assignment, $user->id);

        $submission->assignment->loadCount([
            'submissions as submissions_count',
            'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
            'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
            'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
        ]);

        return view('instructor.assignments.submissions.show', [
            'user' => $user,
            'submission' => $submission,
            'assignment' => $submission->assignment,
            'statusOptions' => [
                AssignmentSubmission::STATUS_PENDING => 'Pending',
                AssignmentSubmission::STATUS_GRADED => 'Graded',
                AssignmentSubmission::STATUS_RETURNED => 'Returned',
            ],
            'statusClasses' => [
                AssignmentSubmission::STATUS_PENDING => 'pending',
                AssignmentSubmission::STATUS_GRADED => 'graded',
                AssignmentSubmission::STATUS_RETURNED => 'returned',
            ],
        ]);
    }

    /**
     * Update the assignment submission
     *
     * @param \App\Http\Requests\Instructor\UpdateAssignmentSubmissionRequest $request
     * @param \App\Models\AssignmentSubmission $submission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSubmission(UpdateAssignmentSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $submission->loadMissing('assignment.topic.course');
        $this->authorizeAssignment($submission->assignment, $user->id);

        $validated = $request->validated();

        $submission->status = $validated['status'];
        $submission->grade = $validated['grade'] ?? null;
        $submission->instructor_notes = $validated['instructor_notes'] ?? null;
        $submission->save();

        ToastMagic::success('Submission updated successfully.');

        return redirect()->back();
    }

    /**
     * Authorize the assignment
     *
     * @param \App\Models\Assignment $assignment
     * @param int $instructorId
     * @return void
     */
    private function authorizeAssignment(Assignment $assignment, int $instructorId): void
    {
        $assignment->loadMissing('topic.course');

        if (!$assignment->topic || !$assignment->topic->course || !$assignment->topic->course->canUserManageCurriculum(\App\Models\User::find($instructorId))) {
            abort(403, 'You are not authorized to manage this assignment.');
        }
    }

    /**
     * Check originality.
     */
    public function checkOriginality(Request $request, AssignmentSubmission $submission, OriginalityCheckService $originalityService): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $submission->loadMissing('assignment.topic.course');

        if (!$submission->assignment || !$submission->assignment->topic || !$submission->assignment->topic->course?->canUserManageCurriculum($user)) {
            abort(403, 'Unauthorized.');
        }

        $provider = $request->input('external_provider') ?: null;
        $report = $originalityService->checkSubmission($submission, $provider);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'report' => $report]);
        }

        ToastMagic::success('Originality check completed. Max similarity: ' . $report['max_similarity_percent'] . '%.');
        return back();
    }

    /**
     * @param \Illuminate\Support\Collection<int,int>|\Illuminate\Contracts\Support\Arrayable<int,int>|array<int,int> $assignmentIds
     */
    private function summarizeAssignments($assignmentIds): array
    {
        $ids = collect($assignmentIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_assignments' => 0,
                'total_submissions' => 0,
                'pending_submissions' => 0,
                'graded_submissions' => 0,
                'returned_submissions' => 0,
            ];
        }

        $submissionsQuery = AssignmentSubmission::query()
            ->whereIn('assignment_id', $ids);

        return [
            'total_assignments' => $ids->count(),
            'total_submissions' => (clone $submissionsQuery)->count(),
            'pending_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_PENDING)->count(),
            'graded_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_GRADED)->count(),
            'returned_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_RETURNED)->count(),
        ];
    }
}

