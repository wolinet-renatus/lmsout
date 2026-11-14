@extends('layouts.instructor')

@section('title', 'Merit List — ' . $quiz->title)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>Back to Attempts</a>
            <h1 class="h4 mb-0 mt-1"><i class="fa-solid fa-trophy text-warning me-2"></i>Merit List — {{ $quiz->title }}</h1>
            @if($quiz->result_publish_at)
                <span class="small text-muted">Result Publish Time: {{ $quiz->result_publish_at->format('M d, Y h:i A') }}</span>
            @endif
        </div>
        <a href="{{ route('instructor.quizzes.merit-list', [$quiz, 'export' => 'csv']) }}" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-csv me-1"></i>Export Merit List CSV
        </a>
    </div>

    @if(!empty($subjectPerformance))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 fw-semibold small"><i class="fa-solid fa-chart-bar me-1 text-primary"></i>Class Performance by Subject</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th>Attempts</th>
                            <th>Average Score</th>
                            <th>Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjectPerformance as $sp)
                            <tr>
                                <td class="fw-semibold">{{ $sp['subject'] }}</td>
                                <td>{{ $sp['attempts'] }}</td>
                                <td>{{ $sp['avg_score'] }}%</td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $sp['pass_rate'] }}%</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Merit Rank</th>
                        <th>Student</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Time Taken</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranked as $attempt)
                        <tr>
                            <td><span class="badge bg-{{ $attempt->merit_rank <= 3 ? 'warning text-dark' : 'secondary-subtle text-secondary' }}">#{{ $attempt->merit_rank }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $attempt->user->name ?? 'Student' }}</div>
                                <div class="text-muted">{{ $attempt->user->email ?? '' }}</div>
                            </td>
                            <td class="fw-bold">{{ $attempt->score }}%</td>
                            <td><span class="badge bg-{{ $attempt->passed ? 'success' : 'danger' }}-subtle text-{{ $attempt->passed ? 'success' : 'danger' }}">{{ $attempt->passed ? 'Passed' : 'Failed' }}</span></td>
                            <td>{{ $attempt->time_taken ? gmdate('H:i:s', $attempt->time_taken) : '-' }}</td>
                            <td>{{ optional($attempt->completed_at)?->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No completed attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

