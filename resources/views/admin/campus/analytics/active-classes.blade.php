@extends('layouts.admin')

@section('title', 'Active Classes & Cohorts')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Analytics</div>
        <div class="breadcrumb-item active">Active Classes</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4><i class="fas fa-chalkboard-teacher mr-2 text-primary"></i>Active &amp; Ongoing Campus Classes</h4>
            @if (auth()->user()->isMultiBranchAdmin())
                <form method="GET" class="form-inline">
                    <select name="branch_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Centre / Branch</th>
                            <th>Venue</th>
                            <th>Instructor / Tutor</th>
                            <th>Session</th>
                            <th>Dates</th>
                            <th>Enrolled</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr>
                                <td>
                                    <strong>{{ $class->course?->title ?? 'Course' }}</strong>
                                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $class->course?->delivery_mode ?? 'face_to_face')) }}</div>
                                </td>
                                <td>{{ $class->branch?->name ?? '-' }}</td>
                                <td>{{ $class->venue?->name ?? '-' }}</td>
                                <td>{{ $class->instructor?->name ?? 'Unassigned' }}</td>
                                <td><span class="badge badge-info text-capitalize">{{ $class->session }}</span></td>
                                <td>
                                    {{ optional($class->start_date)->format('d M Y') ?? '-' }}
                                    @if ($class->end_date)
                                        <span class="text-muted">to {{ $class->end_date->format('d M Y') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $class->enrollments_count }}</span>
                                    @if ($class->max_capacity)
                                        <span class="small text-muted">/ {{ $class->max_capacity }}</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    @if (auth()->user()->hasAdminPermission('manage-attendance'))
                                        <a href="{{ route('admin.campus.attendance.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-user-check mr-1"></i>Attendance
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No active campus classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($classes->hasPages())
            <div class="card-footer">{{ $classes->links() }}</div>
        @endif
    </div>
@endsection
