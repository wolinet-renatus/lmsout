@extends('layouts.instructor')

@section('title', 'Question Banks')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-layer-group me-2 text-primary"></i>Question Banks</h1>
            <p class="text-muted small mb-0">Reusable question pools tagged by subject and difficulty, with CSV import and random paper generation.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBankModal">
            <i class="fa-solid fa-plus me-1"></i>New Question Bank
        </button>
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
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search banks..." value="{{ $searchTerm }}">
                </div>
                <div class="col-md-4">
                    <select name="subject" class="form-select form-select-sm">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj }}" @selected($subjectFilter === $subj)>{{ $subj }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('instructor.question-banks.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse($banks as $bank)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="h6 mb-0">{{ $bank->title }}</h5>
                            @if($bank->subject)
                                <span class="badge bg-primary-subtle text-primary">{{ $bank->subject }}</span>
                            @endif
                        </div>
                        <p class="text-muted small flex-grow-1 mb-3">{{ $bank->description ?: 'No description provided.' }}</p>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="small fw-semibold text-secondary"><i class="fa-solid fa-circle-question me-1"></i>{{ $bank->items_count }} questions</span>
                            <div class="d-flex gap-2">
                                <a href="{{ route('instructor.question-banks.show', $bank) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                <form method="POST" action="{{ route('instructor.question-banks.destroy', $bank) }}" onsubmit="return confirm('Delete this question bank?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="fa-solid fa-database fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">No question banks yet.</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newBankModal">Create Your First Bank</button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $banks->links() }}</div>
</div>

<div class="modal fade" id="newBankModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('instructor.question-banks.store') }}" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title h6">Create Question Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Title *</label>
                    <input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Calculus & Linear Algebra Pool">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Subject Tag</label>
                    <input type="text" name="subject" class="form-control form-control-sm" placeholder="e.g. Mathematics">
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Create Bank</button>
            </div>
        </form>
    </div>
</div>
@endsection

