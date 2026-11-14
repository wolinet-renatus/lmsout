@extends('layouts.instructor')

@section('title', 'Course Student Roster')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="out-page-title mb-1">{{ $assignment->course?->title ?? 'Course Cohort' }} &mdash; Student Roster</h1>
        <p class="text-muted mb-0">{{ $assignment->branch?->name ?? 'Main Centre' }} &bull; {{ $assignment->venue?->name ?? 'TBA' }} &bull; {{ ucfirst($assignment->session) }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('instructor.campus.attendance', $assignment) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-qrcode me-1"></i> Take Attendance</a>
        <a href="{{ route('instructor.campus.index') }}" class="btn btn-sm btn-outline-secondary">Back to Timetable</a>
    </div>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    @foreach (['all' => 'All Students', 'paid' => 'Fully Paid', 'partial' => 'Partial Paid', 'unpaid' => 'Unpaid'] as $key => $label)
        <a href="{{ route('instructor.campus.roster', [$assignment, 'payment' => $key]) }}"
           class="btn btn-sm {{ $paymentFilter === $key ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Reg # / Contact</th>
                        <th>Invoice #</th>
                        <th>Paid / Balance</th>
                        <th>Tuition Status</th>
                        <th>Certificate Readiness</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roster as $e)
                        <tr>
                            <td><strong>{{ $e->applicant?->full_name ?? $e->user?->name ?? 'Learner' }}</strong></td>
                            <td class="small">{{ $e->user?->registration_number ?? '-' }}<br><span class="text-muted">{{ $e->user?->email ?? '' }}</span></td>
                            <td><code>{{ $e->invoice?->invoice_number ?? '-' }}</code></td>
                            <td class="small">
                                <span class="text-success fw-semibold">{{ number_format((float) ($e->invoice?->paid_amount ?? 0), 2) }}</span>
                                / <span class="text-danger">{{ number_format((float) ($e->invoice?->balance ?? 0), 2) }}</span>
                            </td>
                            <td>
                                @php $ps = $e->invoice?->payment_status ?? 'unpaid'; @endphp
                                <span class="badge bg-{{ $ps === 'paid' ? 'success' : ($ps === 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ strtoupper($ps) }}
                                </span>
                            </td>
                            <td>
                                @if (in_array($e->certificate_status, ['ready', 'issued'], true))
                                    <span class="badge bg-success">{{ ucfirst($e->certificate_status) }} ({{ $e->certificate_serial }})</span>
                                @elseif ($e->is_cert_eligible)
                                    <span class="badge bg-info text-dark">Eligible (Pending Admin Clearance)</span>
                                @else
                                    <span class="badge bg-secondary">In Progress</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No students match this payment filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
