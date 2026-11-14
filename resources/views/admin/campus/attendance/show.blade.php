@extends('layouts.admin')

@section('title', 'Take Attendance')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.attendance.index') }}">Attendance</a></div>
        <div class="breadcrumb-item active">{{ $assignment->course?->title }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $assignment->course?->title }} &mdash; {{ $date }}</h4>
                    <div class="card-header-action">
                        <form method="GET" class="form-inline d-inline-flex">
                            <input type="date" name="date" class="form-control form-control-sm mr-1" value="{{ $date }}" max="{{ now()->toDateString() }}">
                            <button type="submit" class="btn btn-sm btn-secondary">Load Date</button>
                        </form>
                    </div>
                </div>
                <form action="{{ route('admin.campus.attendance.store', $assignment) }}" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead><tr><th>Student</th><th>Overall %</th><th>Status</th><th>Method</th></tr></thead>
                                <tbody>
                                    @forelse ($roster as $enr)
                                        @php $rec = $existing->get($enr->id); $cur = $rec?->status ?? 'present'; @endphp
                                        <tr>
                                            <td><strong>{{ $enr->user?->name }}</strong><br><small class="text-muted">{{ $enr->user?->email }}</small></td>
                                            <td><span class="badge badge-{{ $enr->attendance_percentage >= ($assignment->course?->attendance_threshold ?? 75) ? 'success' : 'warning' }}">{{ number_format($enr->attendance_percentage, 0) }}%</span></td>
                                            <td>
                                                @foreach (['present', 'late', 'absent', 'excused'] as $st)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="marks[{{ $enr->id }}]" id="m_{{ $enr->id }}_{{ $st }}" value="{{ $st }}" {{ $cur === $st ? 'checked' : '' }}>
                                                        <label class="form-check-label text-capitalize" for="m_{{ $enr->id }}_{{ $st }}">{{ $st }}</label>
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
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card text-center">
                <div class="card-header"><h4>Student QR Check-In</h4></div>
                <div class="card-body">
                    <form action="{{ route('admin.campus.attendance.qr', $assignment) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-info btn-block"><i class="fas fa-qrcode"></i> Generate 15-Minute QR Code</button>
                    </form>
                    @if (!empty($qr))
                        <img src="{{ $qr }}" alt="Attendance QR" class="img-fluid border rounded p-2 mb-2" style="max-width:240px;">
                        <p class="small text-muted mb-1">Students signed into their portal can scan this QR or open:</p>
                        <input type="text" class="form-control form-control-sm" readonly value="{{ route('student.campus.checkin', $qrToken) }}">
                    @else
                        <p class="text-muted small mb-0">Open a QR window for students to scan and mark themselves present/late automatically.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
