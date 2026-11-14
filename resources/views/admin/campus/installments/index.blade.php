@extends('layouts.admin')

@section('title', 'Installment Schedules & Reminders')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Installments</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-3">
        <div class="col-md-4"><div class="card card-statistic-1"><div class="card-icon bg-danger"><i class="fas fa-clock"></i></div><div class="card-wrap"><div class="card-header"><h4>Overdue</h4></div><div class="card-body">{{ $stats['overdue'] }}</div></div></div></div>
        <div class="col-md-4"><div class="card card-statistic-1"><div class="card-icon bg-warning"><i class="fas fa-calendar-week"></i></div><div class="card-wrap"><div class="card-header"><h4>Due Next 7 Days</h4></div><div class="card-body">{{ $stats['due_week'] }}</div></div></div></div>
        <div class="col-md-4"><div class="card card-statistic-1"><div class="card-icon bg-primary"><i class="fas fa-coins"></i></div><div class="card-wrap"><div class="card-header"><h4>Open Balance</h4></div><div class="card-body">{{ number_format($stats['outstanding'], 0) }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4>Installment Tracking</h4>
            <div class="card-header-action d-flex">
                <a href="{{ route('admin.campus.installments.create') }}" class="btn btn-primary mr-2"><i class="fas fa-plus"></i> Generate Plan</a>
                <a href="{{ route('admin.campus.installments.reminders-list') }}" class="btn btn-info mr-2"><i class="fas fa-list"></i> Queue</a>
                <form action="{{ route('admin.campus.installments.reminders') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning"><i class="fas fa-bell"></i> Send Reminders</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <select name="filter" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="open" {{ request('filter', 'open') === 'open' ? 'selected' : '' }}>Open Installments</option>
                    <option value="overdue" {{ request('filter') === 'overdue' ? 'selected' : '' }}>Overdue Only</option>
                    <option value="paid" {{ request('filter') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="all" {{ request('filter') === 'all' ? 'selected' : '' }}>All</option>
                </select>
                <select name="branch_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Student</th><th>Invoice / CN</th><th>Course</th><th>Installment</th><th>Due Date</th><th>Remaining</th><th>Reminders</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($schedules as $s)
                            <tr>
                                <td>{{ $s->invoice?->user?->name ?? '-' }}<br><small class="text-muted">{{ $s->invoice?->user?->email ?? '' }}</small></td>
                                <td><a href="{{ route('admin.campus.invoices.show', $s->invoice_id) }}">{{ $s->invoice?->invoice_number }}</a><br><small>{{ $s->invoice?->control_number ?? '-' }}</small></td>
                                <td>{{ $s->invoice?->course?->title ?? '-' }}</td>
                                <td>{{ $s->installment_label }}</td>
                                <td>
                                    {{ optional($s->due_date)->format('M d, Y') }}
                                    @if ($s->isOverdue()) <span class="badge badge-danger">Overdue</span> @endif
                                </td>
                                <td class="font-weight-bold">{{ number_format($s->remaining_amount, 2) }}</td>
                                <td>{{ $s->reminder_count }}</td>
                                <td><a href="{{ route('admin.campus.invoices.show', $s->invoice_id) }}" class="btn btn-sm btn-outline-primary">Pay</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No installment rows matching filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $schedules->links() }}
        </div>
    </div>
@endsection
