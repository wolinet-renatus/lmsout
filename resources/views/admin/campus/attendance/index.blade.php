@extends('layouts.admin')

@section('title', 'Campus Attendance')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Attendance</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Active F2F / Hybrid Classes ({{ $todayCount }} marked today)</h4>
            <div class="card-header-action">
                <form method="GET" class="form-inline">
                    <select name="branch_id" class="form-control form-control-sm mr-1" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Course</th><th>Branch / Venue</th><th>Tutor</th><th>Session</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse ($assignments as $a)
                            <tr>
                                <td><strong>{{ $a->course?->title ?? '-' }}</strong></td>
                                <td>{{ $a->branch?->name ?? '-' }} / {{ $a->venue?->name ?? 'TBA' }}</td>
                                <td>{{ $a->instructor?->name ?? 'Unassigned' }}</td>
                                <td class="text-capitalize">{{ $a->session }} ({{ optional($a->start_date)->format('M d') }} - {{ optional($a->end_date)->format('M d, Y') }})</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.campus.attendance.show', $a) }}" class="btn btn-sm btn-primary"><i class="fas fa-qrcode"></i> Take / QR Attendance</a>
                                    <a href="{{ route('admin.campus.attendance.export', $a) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i> CSV</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No active timetable cohorts found. Create a cohort in Timetable first.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
