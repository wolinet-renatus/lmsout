@extends('layouts.admin')

@section('title', 'Campus Timetable & Cohorts')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Timetable &amp; Cohorts</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Scheduled F2F &amp; Hybrid Cohorts</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.timetable.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Schedule Cohort</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <select name="branch_id" class="form-control form-control-sm mr-2">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="instructor_id" class="form-control form-control-sm mr-2">
                    <option value="">All Tutors</option>
                    @foreach ($instructors as $inst)
                        <option value="{{ $inst->id }}" {{ request('instructor_id') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All Status</option>
                    @foreach (['active', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
                <a href="{{ route('admin.campus.timetable.index') }}" class="btn btn-sm btn-light">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Branch &amp; Venue</th>
                            <th>Tutor</th>
                            <th>Session &amp; Dates</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $a)
                            <tr>
                                <td>
                                    <strong>{{ $a->course?->title ?? '-' }}</strong>
                                    <br><span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $a->course?->delivery_mode ?? '')) }}</span>
                                </td>
                                <td>{{ $a->branch?->name ?? '-' }}<br><small class="text-muted">{{ $a->venue?->name ?? 'TBA' }}</small></td>
                                <td>{{ $a->instructor?->name ?? 'Unassigned' }}</td>
                                <td>
                                    <span class="text-capitalize font-weight-bold">{{ $a->session }}</span>
                                    @if ($a->start_time) <small>({{ substr($a->start_time, 0, 5) }} - {{ substr((string) $a->end_time, 0, 5) }})</small> @endif
                                    <br><small class="text-muted">{{ optional($a->start_date)->format('M d, Y') }} &rarr; {{ optional($a->end_date)->format('M d, Y') ?? 'Ongoing' }}</small>
                                </td>
                                <td>{{ $a->enrollments_count }}{{ $a->max_capacity ? ' / ' . $a->max_capacity : '' }}</td>
                                <td><span class="badge badge-{{ $a->status === 'active' ? 'success' : ($a->status === 'completed' ? 'primary' : 'secondary') }}">{{ ucfirst($a->status) }}</span></td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.campus.attendance.show', $a) }}" class="btn btn-sm btn-outline-info" title="Attendance"><i class="fas fa-clipboard-check"></i></a>
                                    <form action="{{ route('admin.campus.timetable.toggle-status', $a) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $a->status === 'active' ? 'success' : 'secondary' }}" title="{{ $a->status === 'active' ? 'Mark Completed' : 'Reactivate Cohort' }}">
                                            <i class="fas fa-{{ $a->status === 'active' ? 'check' : 'redo' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.campus.timetable.edit', $a) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.campus.timetable.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete timetable entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No timetable cohorts scheduled yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $assignments->links() }}
        </div>
    </div>
@endsection
