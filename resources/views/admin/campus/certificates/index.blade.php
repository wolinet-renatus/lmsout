@extends('layouts.admin')

@section('title', 'F2F Certificate Clearance')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Certificate Clearance</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Short Course Certificate Clearance</h4>
            <div class="card-header-action">
                <div class="btn-group">
                    @foreach (['pending' => 'Pending', 'ready' => 'Ready', 'issued' => 'Issued'] as $k => $lbl)
                        <a href="{{ route('admin.campus.certificates.index', ['status' => $k, 'branch_id' => request('branch_id')]) }}"
                            class="btn btn-{{ $status === $k ? 'primary' : 'outline-primary' }}">{{ $lbl }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Student</th><th>Course</th><th>Branch</th><th>Attendance</th><th>Tuition Balance</th><th>Serial</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse ($enrollments as $e)
                            @php $bal = (float) ($e->invoice?->balance ?? 0); $thr = $e->course?->attendance_threshold ?? 75; @endphp
                            <tr>
                                <td><strong>{{ $e->user?->name }}</strong><br><small class="text-muted">{{ $e->user?->email }}</small></td>
                                <td>{{ $e->course?->title }}</td>
                                <td>{{ $e->branch?->name ?? '-' }}</td>
                                <td><span class="badge badge-{{ $e->attendance_percentage >= $thr ? 'success' : 'warning' }}">{{ number_format($e->attendance_percentage, 0) }}% / {{ $thr }}%</span></td>
                                <td><span class="badge badge-{{ $bal <= 0.01 ? 'success' : 'danger' }}">{{ number_format($bal, 2) }}</span></td>
                                <td><code>{{ $e->certificate_serial ?? '-' }}</code></td>
                                <td class="text-right text-nowrap">
                                    @if ($e->certificate_serial && in_array($e->certificate_status, ['ready', 'issued'], true))
                                        <a href="{{ route('admin.campus.certificates.pdf', $e) }}" class="btn btn-sm btn-outline-primary">PDF</a>
                                    @endif
                                    @if ($e->is_eligible)
                                        @if ($status !== 'ready' && $status !== 'issued')
                                            <form action="{{ route('admin.campus.certificates.update', $e) }}" method="POST" class="d-inline">
                                                @csrf <input type="hidden" name="action" value="ready">
                                                <button class="btn btn-sm btn-info" type="submit">Mark Ready</button>
                                            </form>
                                        @endif
                                        @if ($status !== 'issued')
                                            <form action="{{ route('admin.campus.certificates.update', $e) }}" method="POST" class="d-inline">
                                                @csrf <input type="hidden" name="action" value="issued">
                                                <button class="btn btn-sm btn-success" type="submit">Issue Certificate</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-muted small">Blocked by fee or attendance</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No {{ $status }} campus certificates.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $enrollments->links() }}
        </div>
    </div>
@endsection
