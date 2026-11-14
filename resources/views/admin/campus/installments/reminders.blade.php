@extends('layouts.admin')

@section('title', 'Payment Reminders Queue')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.installments.index') }}">Installments</a></div>
        <div class="breadcrumb-item active">Due Reminders</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4><i class="fas fa-bell mr-2 text-warning"></i>Installments Due Within {{ $daysAhead }} Days &amp; Overdue</h4>
            <div class="d-flex align-items-center">
                <form method="GET" class="form-inline mr-2">
                    <select name="days" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach ([3, 7, 14, 30] as $d)
                            <option value="{{ $d }}" {{ (int) $daysAhead === $d ? 'selected' : '' }}>Next {{ $d }} Days</option>
                        @endforeach
                    </select>
                </form>
                <form action="{{ route('admin.campus.installments.reminders') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-paper-plane mr-1"></i> Dispatch All Due Reminders</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course / Branch</th>
                            <th>Invoice #</th>
                            <th>Due Date</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Sent Count</th>
                            <th class="text-right">Send One</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dueSoon as $s)
                            <tr>
                                <td>{{ $s->invoice?->user?->name ?? '-' }}<div class="small text-muted">{{ $s->invoice?->user?->email ?? '-' }}</div></td>
                                <td>{{ $s->invoice?->course?->title ?? '-' }}<div class="small text-muted">{{ $s->invoice?->branch?->name ?? '-' }}</div></td>
                                <td><a href="{{ route('admin.campus.installments.show', $s->invoice_id) }}"><code>{{ $s->invoice?->invoice_number }}</code></a></td>
                                <td>{{ optional($s->due_date)->format('d M Y') }}</td>
                                <td class="font-weight-bold">{{ currency_format($s->remaining_amount) }}</td>
                                <td><span class="badge badge-{{ $s->isOverdue() ? 'danger' : 'warning' }}">{{ $s->isOverdue() ? 'Overdue' : ucfirst($s->status) }}</span></td>
                                <td>{{ $s->reminder_count }}</td>
                                <td class="text-right">
                                    <form action="{{ route('admin.campus.installments.mark-sent', $s) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-envelope mr-1"></i> Send</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No installments due in this window.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($dueSoon->hasPages())
            <div class="card-footer">{{ $dueSoon->links() }}</div>
        @endif
    </div>
@endsection
