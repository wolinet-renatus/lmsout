@extends('layouts.admin')

@section('title', 'Register F2F / Hybrid Applicant')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.applicants.index') }}">Applicants</a></div>
        <div class="breadcrumb-item active">New Applicant</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Applicants Form</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.applicants.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <form action="{{ route('admin.campus.applicants.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                @include('campus.partials.applicant-form', [
                    'public' => false,
                    'feeUrl' => route('admin.campus.applicants.course-fee', ['course' => ':id']),
                ])
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Submit Application &amp; Issue Invoice</button>
            </div>
        </form>
    </div>
@endsection
