@extends('layouts.instructor')

@section('title', 'Take Attendance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('instructor.campus.index') }}" class="text-primary small">&larr; Back to Campus Timetable</a>
        <h1 class="out-page-title mt-1 mb-0">{{ $assignment->course?->title }}</h1>
        <small class="text-muted">{{ $assignment->branch?->name }} &bull; {{ $assignment->venue?->name ?? 'TBA' }} &bull; {{ ucfirst($assignment->session) }}</small>
    </div>
    <form method="GET" class="d-flex gap-2 mt-2 mt-md-0">
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ now()->toDateString() }}">
        <button type="submit" class="btn btn-sm btn-outline-primary">Load Date</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <form action="{{ route('instructor.campus.attendance.store', $assignment) }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Student</th><th>Overall %</th><th>Status</th><th>Method</th></tr></thead>
                            <tbody>
                                @forelse ($roster as $enr)
                                    @php $rec = $existing->get($enr->id); $cur = $rec?->status ?? 'present'; @endphp
                                    <tr>
                                        <td><strong>{{ $enr->user?->name }}</strong><br><small class="text-muted">{{ $enr->user?->email }}</small></td>
                                        <td><span class="badge bg-{{ $enr->attendance_percentage >= ($assignment->course?->attendance_threshold ?? 75) ? 'success' : 'warning text-dark' }}">{{ number_format($enr->attendance_percentage, 0) }}%</span></td>
                                        <td>
                                            @foreach (['present', 'late', 'absent', 'excused'] as $st)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="marks[{{ $enr->id }}]" id="i_{{ $enr->id }}_{{ $st }}" value="{{ $st }}" {{ $cur === $st ? 'checked' : '' }}>
                                                    <label class="form-check-label text-capitalize" for="i_{{ $enr->id }}_{{ $st }}">{{ $st }}</label>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="small text-muted">{{ $rec ? ucwords(str_replace('_', ' ', $rec->check_in_method)) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No students enrolled in this cohort.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($roster->isNotEmpty())
                    <div class="card-footer bg-white text-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Attendance</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <h6 class="fw-bold mb-3">Student QR Check-In</h6>
            <form action="{{ route('instructor.campus.qr', $assignment) }}" method="POST" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-info text-white w-100"><i class="fa-solid fa-qrcode me-1"></i> Generate 15-Minute QR</button>
            </form>
            @if (!empty($qr))
                <img src="{{ $qr }}" alt="Attendance QR" class="img-fluid border rounded p-2 mx-auto mb-2" style="max-width:220px;">
                <input type="text" class="form-control form-control-sm" readonly value="{{ route('student.campus.checkin', $qrToken) }}">
            @else
                <p class="small text-muted mb-0">Display a QR code in class so students check in from their portal.</p>
            @endif
        </div>
    </div>
</div>
@endsection
