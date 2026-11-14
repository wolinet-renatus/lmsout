@extends('layouts.instructor')

@section('title', $bank->title . ' — Question Bank')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('instructor.question-banks.index') }}" class="text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>Back to Question Banks</a>
            <h1 class="h4 mb-0 mt-1">{{ $bank->title }} @if($bank->subject)<span class="badge bg-primary-subtle text-primary fs-6">{{ $bank->subject }}</span>@endif</h1>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#csvImportModal"><i class="fa-solid fa-file-csv me-1"></i>Import CSV</button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal"><i class="fa-solid fa-plus me-1"></i>Add Question</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="difficulty" class="form-select form-select-sm">
                        <option value="">All Difficulties</option>
                        <option value="easy" @selected($difficultyFilter === 'easy')>Easy</option>
                        <option value="medium" @selected($difficultyFilter === 'medium')>Medium</option>
                        <option value="hard" @selected($difficultyFilter === 'hard')>Hard</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="subject" class="form-control form-control-sm" placeholder="Filter by subject..." value="{{ $subjectFilter }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">Filter</button>
                    <a href="{{ route('instructor.question-banks.show', $bank) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Question</th>
                        <th>Subject</th>
                        <th>Difficulty</th>
                        <th>Correct Answer</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td style="max-width:420px;">
                                <div class="fw-semibold">{{ $item->question }}</div>
                                @if(!empty($item->options))
                                    <div class="text-muted mt-1">{{ implode(' | ', $item->options) }}</div>
                                @endif
                            </td>
                            <td>{{ $item->subject ?: '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $item->difficulty === 'hard' ? 'danger' : ($item->difficulty === 'medium' ? 'warning text-dark' : 'success') }}-subtle text-{{ $item->difficulty === 'hard' ? 'danger' : ($item->difficulty === 'medium' ? 'warning' : 'success') }}">
                                    {{ ucfirst($item->difficulty) }}
                                </span>
                            </td>
                            <td><span class="badge bg-success-subtle text-success">{{ $item->correct_answer }}</span></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('instructor.question-banks.items.destroy', [$bank, $item]) }}" onsubmit="return confirm('Delete this question?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No questions in this bank yet. Add manually or import a CSV.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>

<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('instructor.question-banks.items.store', $bank) }}" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title h6">Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Question *</label>
                    <textarea name="question_text" class="form-control form-control-sm" rows="2" required></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Option A</label>
                        <input type="text" name="options[]" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Option B</label>
                        <input type="text" name="options[]" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Option C</label>
                        <input type="text" name="options[]" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Option D</label>
                        <input type="text" name="options[]" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Correct Answer (Exact text) *</label>
                        <input type="text" name="correct_answer" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Difficulty *</label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control form-control-sm" value="{{ $bank->subject }}">
                    </div>
                </div>
                <div>
                    <label class="form-label small fw-semibold">Explanation</label>
                    <input type="text" name="explanation" class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Question</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('instructor.question-banks.import-csv', $bank) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title h6">Import Questions from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">CSV columns: <code>question_text, option_a, option_b, option_c, option_d, correct_option (A/B/C/D or text), difficulty (easy/medium/hard), subject, explanation</code></p>
                <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv,.txt" required>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-upload me-1"></i>Import CSV</button>
            </div>
        </form>
    </div>
</div>
@endsection

