@extends('layouts.app')

@section('title', 'Apply: ' . $selectedCourse->title)

@section('content')
<section class="section-padding" style="background:#f5f8fd; padding:50px 0 80px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('courses.show', $selectedCourse->id) }}" class="text-primary small fw-semibold">&larr; Back to Course Details</a>
                        <h2 class="mt-2 mb-1">{{ $selectedCourse->title }}</h2>
                        <span class="badge bg-primary">{{ $selectedCourse->isHybrid() ? 'Hybrid (Campus + Online LMS)' : 'Face-to-Face Campus Short Course' }}</span>
                        @if ($selectedCourse->defaultBranch)
                            <span class="badge bg-light text-dark border ms-1">{{ $selectedCourse->defaultBranch->name }}</span>
                        @endif
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('courses.apply.store', $selectedCourse->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('campus.partials.applicant-form', [
                        'public' => true,
                        'feeUrl' => route('courses.apply.fee', ['course' => ':id']),
                    ])

                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                            <div class="small text-muted mb-2 mb-md-0">
                                Offline-first: you will immediately receive your Invoice &amp; GePG Control Number, or continue to online payment if selected.
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fa-solid fa-paper-plane me-1"></i> Submit Application
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
