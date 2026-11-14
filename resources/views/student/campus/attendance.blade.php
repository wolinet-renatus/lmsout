@extends('layouts.student')

@section('title', 'Campus Attendance & Clearance')

@section('content')
<div class="out-page-header mb-4">
    <h1 class="out-page-title">Timetable, Attendance &amp; Certificate Clearance</h1>
    <p class="out-page-sub text-muted">Track your in-person attendance percentage and tuition clearance status.</p>
</div>

<div class="row g-3 mb-4">
    @forelse ($enrollments as $e)
        @php $bal = (float) ($e->invoice?->balance ?? 0); $thr = $e->course?->attendance_threshold ?? 75; @endphp
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0">{{ $e->course?->title }}</h5>
                        <span class="badge bg-info">{{ ucfirst($e->certificate_status) }}</span>
                    </div>
                    <p class="small text-muted mb-2">
                        {{ $e->branch?->name ?? '-' }} &bull; Venue: {{ $e->venue?->name ?? 'TBA' }} &bull; {{ ucfirst((string) $e->session) }}
                        <br>{{ optional($e->start_date)->format('M d, Y') }} &rarr; {{ optional($e->end_date)->format('M d, Y') ?? 'Ongoing' }}
                    </p>
                    <div class="d-flex gap-3 small">
                        <span>Attendance: <strong>{{ number_format($e->attendance_percentage, 0) }}%</strong> (Min {{ $thr }}%)</span>
                        <span>Balance: <strong class="{{ $bal <= 0.01 ? 'text-success' : 'text-danger' }}">{{ number_format($bal, 2) }}</strong></span>
                    </div>
                    @if ($e->certificate_serial)
                        <div class="mt-2 small d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span>Certificate Serial: <code>{{ $e->certificate_serial }}</code></span>
                            @if (in_array($e->certificate_status, ['ready', 'issued'], true))
                                <a href="{{ route('student.campus.certificates.pdf', $e) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-download me-1"></i> Download Certificate PDF
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body text-muted">No Face-to-Face or Hybrid enrollments yet.</div></div></div>
    @endforelse
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Attendance Log</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Date</th><th>Course</th><th>Branch</th><th>Status</th><th>Method</th></tr></thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr>
                            <td>{{ optional($r->attendance_date)->format('M d, Y') }}</td>
                            <td>{{ $r->course?->title ?? '-' }}</td>
                            <td>{{ $r->branch?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ in_array($r->status, ['present', 'late']) ? 'success' : 'danger' }}">{{ ucfirst($r->status) }}</span></td>
                            <td class="small text-muted">{{ ucwords(str_replace('_', ' ', $r->check_in_method)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No attendance records recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if ($records->hasPages()) <div class="mt-3">{{ $records->links() }}</div> @endif
@endsection
