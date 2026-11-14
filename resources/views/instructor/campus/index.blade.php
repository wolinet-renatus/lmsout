@extends('layouts.instructor')

@section('title', 'Campus Timetable')

@section('content')
<div class="out-page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="out-page-title">Campus Timetable &amp; Cohorts</h1>
        <p class="out-page-sub text-muted mb-0">Assigned Face-to-Face and Hybrid sessions, student payment rosters, and attendance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('instructor.campus.index', ['scope' => 'mine']) }}" class="btn btn-sm {{ ($scope ?? 'mine') === 'mine' ? 'btn-primary' : 'btn-outline-primary' }}">My Assigned Classes</a>
        @if (auth()->user()->branch_id)
            <a href="{{ route('instructor.campus.index', ['scope' => 'branch']) }}" class="btn btn-sm {{ ($scope ?? 'mine') === 'branch' ? 'btn-primary' : 'btn-outline-primary' }}">Branch Timetable</a>
        @endif
    </div>
</div>

@if (($readyCertificatesCount ?? 0) > 0)
    <div class="alert alert-success d-flex align-items-center justify-content-between mb-4">
        <span><i class="fa-solid fa-certificate me-2"></i><strong>{{ $readyCertificatesCount }} student certificate(s)</strong> in your courses are cleared &amp; ready for release.</span>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Course</th><th>Branch / Venue</th><th>Tutor</th><th>Session</th><th>Dates</th><th>Enrolled</th><th>Status</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    @forelse ($assignments as $a)
                        <tr>
                            <td><strong>{{ $a->course?->title ?? '-' }}</strong></td>
                            <td>{{ $a->branch?->name ?? '-' }} / {{ $a->venue?->name ?? 'TBA' }}</td>
                            <td class="small">{{ $a->instructor?->name ?? '-' }}</td>
                            <td class="text-capitalize">{{ $a->session }}
                                @if ($a->start_time) <br><small class="text-muted">{{ substr($a->start_time, 0, 5) }} - {{ substr((string) $a->end_time, 0, 5) }}</small> @endif
                            </td>
                            <td class="small">{{ optional($a->start_date)->format('M d, Y') }} &rarr; {{ optional($a->end_date)->format('M d, Y') ?? 'Ongoing' }}</td>
                            <td>{{ $a->enrollments_count }}</td>
                            <td><span class="badge bg-{{ $a->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($a->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('instructor.campus.roster', $a) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fa-solid fa-users me-1"></i> Roster
                                </a>
                                @if ((int) $a->instructor_id === (int) auth()->id())
                                    <a href="{{ route('instructor.campus.attendance', $a) }}" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-qrcode me-1"></i> Attendance
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No campus cohorts found in this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if ($assignments->hasPages()) <div class="mt-3">{{ $assignments->links() }}</div> @endif
@endsection
