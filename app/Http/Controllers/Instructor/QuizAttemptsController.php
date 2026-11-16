<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Quiz Attempts Controller
 *
 * This controller handles the quiz attempts functionality.
 *
 * @package App\Http\Controllers\Instructor
 * @author Renatus Bernardo
 */
class QuizAttemptsController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $courseId = $request->integer('course_id');
        $search = trim((string) $request->input('search'));

        $quizzesQuery = Quiz::query()
            ->with(['topic.course:id,title,instructor_id,assistant_instructor_id'])
            ->withCount([
                'attempts as attempts_count',
                'attempts as passed_attempts_count' => fn($q) => $q->where('passed', true),
            ])
            ->whereHas('topic.course', function ($q) use ($user) {
                $q->where('instructor_id', $user->id)
                    ->orWhere('assistant_instructor_id', $user->id)
                    ->orWhereHas('coInstructors', fn ($co) => $co->where('user_id', $user->id)->where('can_edit', true));
            });

        if ($courseId) {
            $quizzesQuery->whereHas('topic.course', fn($q) => $q->where('id', $courseId));
        }

        if ($search !== '') {
            $quizzesQuery->where('title', 'like', '%' . $search . '%');
        }

        $quizIds = (clone $quizzesQuery)->select('quizzes.id')->pluck('quizzes.id');
        $summary = $this->summarizeQuizzes($quizIds);

        $quizzes = $quizzesQuery
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $quizzes->getCollection()->transform(function (Quiz $quiz) {
            $quiz->course_title = optional($quiz->topic->course)->title ?? 'Unknown Course';
            $description = $quiz->description ?? 'No description provided.';
            $quiz->short_description = Str::limit(strip_tags($description), 140);
            $quiz->pass_rate = $quiz->attempts_count > 0
                ? (int) round(($quiz->passed_attempts_count / $quiz->attempts_count) * 100)
                : null;

            return $quiz;
        });

        return view('instructor.quizzes.index', [
            'user' => $user,
            'quizzes' => $quizzes,
            'summary' => $summary,
            'selectedCourseId' => $courseId,
            'searchTerm' => $search,
            'courses' => $user->courses()->select(['id', 'title'])->orderBy('title')->get(),
        ]);
    }

    /**
     * Display the specified resource details.
     */
    public function show(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $quiz->load('topic.course');
        $this->authorizeQuiz($quiz, $user);

        $status = $request->input('status');
        $search = trim((string) $request->input('search'));

        // Refresh merit ranks for completed attempts on this quiz
        $this->refreshMeritRanks($quiz);

        $attemptsQuery = QuizAttempt::query()
            ->with(['user:id,name,email', 'enrollment:id,status'])
            ->where('quiz_id', $quiz->id);

        if ($status === 'passed') {
            $attemptsQuery->where('passed', true);
        } elseif ($status === 'failed') {
            $attemptsQuery->where('passed', false);
        }

        if ($search !== '') {
            $attemptsQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $attempts = $attemptsQuery
            ->orderByDesc('completed_at')
            ->paginate(15)
            ->withQueryString();

        $attempts->getCollection()->transform(function (QuizAttempt $attempt) {
            $attempt->status_label = $attempt->passed ? 'Passed' : 'Failed';
            $attempt->status_class = $attempt->passed ? 'passed' : 'failed';
            $attempt->completed_at_formatted = optional($attempt->completed_at)?->format('M d, Y h:i A');
            $attempt->started_at_formatted = optional($attempt->started_at)?->format('M d, Y h:i A');
            $attempt->time_taken_formatted = $this->formatSeconds($attempt->time_taken);

            return $attempt;
        });

        $stats = $this->summarizeAttempts($quiz->id);
        $subjectPerformance = $this->subjectPerformanceForCourse($quiz->topic->course_id ?? 0);

        return view('instructor.quizzes.show', [
            'user' => $user,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'stats' => $stats,
            'subjectPerformance' => $subjectPerformance,
            'statusOptions' => [
                '' => 'All Attempts',
                'passed' => 'Passed',
                'failed' => 'Failed',
            ],
        ]);
    }

    /**
     * Merit list.
     */
    public function meritList(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        $quiz->load('topic.course');
        $this->authorizeQuiz($quiz, $user);

        $this->refreshMeritRanks($quiz);

        $ranked = QuizAttempt::query()
            ->with('user:id,name,email')
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->orderBy('merit_rank')
            ->orderByDesc('score')
            ->orderBy('time_taken')
            ->get();

        if ($request->query('export') === 'csv') {
            $filename = 'merit-list-quiz-' . $quiz->id . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];
            $callback = function () use ($ranked) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Merit Rank', 'Student Name', 'Email', 'Score (%)', 'Result', 'Time Taken (s)', 'Completed At']);
                foreach ($ranked as $attempt) {
                    fputcsv($out, [
                        $attempt->merit_rank ?? '-',
                        $attempt->user->name ?? 'Student',
                        $attempt->user->email ?? '',
                        $attempt->score ?? 0,
                        $attempt->passed ? 'Passed' : 'Failed',
                        $attempt->time_taken ?? 0,
                        optional($attempt->completed_at)?->toDateTimeString() ?? '',
                    ]);
                }
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('instructor.quizzes.merit-list', [
            'user' => $user,
            'quiz' => $quiz,
            'ranked' => $ranked,
            'subjectPerformance' => $this->subjectPerformanceForCourse($quiz->topic->course_id ?? 0),
        ]);
    }

    /**
     * Refresh merit ranks.
     */
    private function refreshMeritRanks(Quiz $quiz): void
    {
        $orderedIds = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('score')
            ->orderBy('time_taken')
            ->orderBy('completed_at')
            ->pluck('id');

        foreach ($orderedIds as $index => $attemptId) {
            QuizAttempt::where('id', $attemptId)->update(['merit_rank' => $index + 1]);
        }
    }

    /**
     * Subject performance for course.
     */
    private function subjectPerformanceForCourse(int $courseId): array
    {
        if (!$courseId) {
            return [];
        }
        $quizzes = Quiz::query()
            ->whereHas('topic', fn ($q) => $q->where('course_id', $courseId))
            ->with(['attempts' => fn ($q) => $q->whereNotNull('completed_at')])
            ->get();

        $bySubject = [];
        foreach ($quizzes as $q) {
            $subject = $q->subject ?: ($q->topic->title ?? 'General');
            if (!isset($bySubject[$subject])) {
                $bySubject[$subject] = ['subject' => $subject, 'attempts' => 0, 'passed' => 0, 'score_sum' => 0];
            }
            foreach ($q->attempts as $a) {
                $bySubject[$subject]['attempts']++;
                $bySubject[$subject]['score_sum'] += (int) $a->score;
                if ($a->passed) {
                    $bySubject[$subject]['passed']++;
                }
            }
        }

        return array_values(array_map(function ($row) {
            $row['avg_score'] = $row['attempts'] > 0 ? round($row['score_sum'] / $row['attempts'], 1) : 0;
            $row['pass_rate'] = $row['attempts'] > 0 ? round(($row['passed'] / $row['attempts']) * 100, 1) : 0;
            unset($row['score_sum']);
            return $row;
        }, $bySubject));
    }

    /**
     * Authorize quiz.
     */
    private function authorizeQuiz(Quiz $quiz, $user): void
    {
        $quiz->loadMissing('topic.course');

        if (!$quiz->topic || !$quiz->topic->course || !$quiz->topic->course->canUserManageCurriculum($user)) {
            abort(403, 'You are not authorized to access this quiz.');
        }
    }

    /**
     * Summarize quizzes.
     */
    private function summarizeQuizzes($quizIds): array
    {
        $ids = collect($quizIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_quizzes' => 0,
                'total_attempts' => 0,
                'passed_attempts' => 0,
                'average_score' => null,
            ];
        }

        $attemptsQuery = QuizAttempt::query()->whereIn('quiz_id', $ids);

        $totalAttempts = (clone $attemptsQuery)->count();

        return [
            'total_quizzes' => $ids->count(),
            'total_attempts' => $totalAttempts,
            'passed_attempts' => (clone $attemptsQuery)->where('passed', true)->count(),
            'average_score' => $totalAttempts > 0 ? (int) round((clone $attemptsQuery)->avg('score')) : null,
        ];
    }

    /**
     * Summarize attempts.
     */
    private function summarizeAttempts(int $quizId): array
    {
        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quizId)
            ->get();

        if ($attempts->isEmpty()) {
            return [
                'total_attempts' => 0,
                'passed_attempts' => 0,
                'failed_attempts' => 0,
                'average_score' => null,
                'best_score' => null,
                'average_time' => null,
            ];
        }

        $total = $attempts->count();
        $passed = $attempts->where('passed', true)->count();
        $failed = $total - $passed;
        $avgScore = (int) round($attempts->avg('score'));
        $bestScore = $attempts->max('score');
        $avgTime = $attempts->avg('time_taken');

        return [
            'total_attempts' => $total,
            'passed_attempts' => $passed,
            'failed_attempts' => $failed,
            'average_score' => $avgScore,
            'best_score' => $bestScore,
            'average_time' => $avgTime ? $this->formatSeconds($avgTime) : null,
        ];
    }

    /**
     * Format seconds.
     */
    private function formatSeconds(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $interval = Carbon::now()->startOfDay()->addSeconds($seconds)->diff(Carbon::now()->startOfDay());

        return $interval->format('%H:%I:%S');
    }
}
