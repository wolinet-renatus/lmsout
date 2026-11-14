@extends('layouts.student')

@section('title', 'Campus Class Timetable')

@section('content')
<div class="out-page-header mb-4">
    <h1 class="out-page-title">Campus Class Timetable &amp; Cohorts</h1>
    <p class="out-page-sub text-muted">Your assigned Face-to-Face / Hybrid schedules and branch timetables.</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">My Enrolled Campus Classes</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Course</th><th>Branch &amp; Venue</th><th>Tutor</th><th>Session &amp; Dates</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $e)
                        <tr>
                            <td><strong>{{ $e->course?->title ?? '-' }}</strong></td>
                            <td>{{ $e->branch?->name ?? $e->courseAssignment?->branch?->name ?? '-' }} / {{ $e->courseAssignment?->venue?->name ?? 'TBA' }}</td>
                            <td>{{ $e->courseAssignment?->instructor?->name ?? 'Assigned Tutor' }}</td>
                            <td class="small">
                                <span class="text-capitalize">{{ $e->courseAssignment?->session ?? 'Scheduled' }}</span>
                                @if ($e->courseAssignment?->start_date)
                                    <br><span class="text-muted">{{ $e->courseAssignment->start_date->format('d M Y') }} &rarr; {{ optional($e->courseAssignment->end_date)->format('d M Y') ?? 'Ongoing' }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-primary text-capitalize">{{ $e->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">You have no Face-to-Face or Hybrid enrollments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Upcoming &amp; Active Regional Schedules</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Course</th><th>Branch</th><th>Venue</th><th>Tutor</th><th>Session</th><th>Starts</th></tr>
                </thead>
                <tbody>
                    @forelse ($branchTimetable as $row)
                        <tr>
                            <td><strong>{{ $row->course?->title ?? '-' }}</strong></td>
                            <td>{{ $row->branch?->name ?? '-' }}</td>
                            <td>{{ $row->venue?->name ?? 'TBA' }}</td>
                            <td>{{ $row->instructor?->name ?? 'TBA' }}</td>
                            <td class="text-capitalize">{{ $row->session }}</td>
                            <td class="small">{{ optional($row->start_date)->format('d M Y') ?? 'TBA' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No active branch timetables published.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
