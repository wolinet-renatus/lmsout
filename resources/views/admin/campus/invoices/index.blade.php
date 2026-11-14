@extends('layouts.admin')

@section('title', 'Campus Invoices & Control Numbers')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Campus Invoices</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-file-invoice"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Invoices</h4></div>
                    <div class="card-body">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Collected</h4></div>
                    <div class="card-body">{{ number_format($stats['collected'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Outstanding</h4></div>
                    <div class="card-body">{{ number_format($stats['outstanding'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-receipt"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Receipts Pending Verify</h4></div>
                    <div class="card-body">{{ $pendingVerifications }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Invoices</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.applicants.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Applicant &amp; Invoice</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <input type="text" name="search" class="form-control form-control-sm mr-2" value="{{ request('search') }}" placeholder="Invoice #, Control #, Student...">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All Status</option>
                    @foreach (['unpaid', 'partial', 'paid'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <select name="branch_id" class="form-control form-control-sm mr-2">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
                <a href="{{ route('admin.campus.invoices.index') }}" class="btn btn-sm btn-light">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invoice &amp; Control No.</th>
                            <th>Student</th>
                            <th>Course / Branch</th>
                            <th>Plan</th>
                            <th>Net Fee</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $inv)
                            <tr>
                                <td>
                                    <strong>{{ $inv->invoice_number }}</strong>
                                    <br><small class="text-primary">CN: {{ $inv->control_number ?? '-' }}</small>
                                </td>
                                <td>{{ $inv->user?->name ?? $inv->applicant?->full_name ?? '-' }}<br><small class="text-muted">{{ $inv->user?->email ?? '' }}</small></td>
                                <td>{{ $inv->course?->title ?? '-' }}<br><small class="text-muted">{{ $inv->branch?->name ?? '-' }}</small></td>
                                <td><span class="badge badge-light">{{ ucfirst($inv->installment_plan) }}</span></td>
                                <td>{{ number_format($inv->net_amount, 2) }}</td>
                                <td class="text-success">{{ number_format($inv->paid_amount, 2) }}</td>
                                <td class="text-danger font-weight-bold">{{ number_format($inv->balance, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($inv->payment_status) }}
                                    </span>
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.campus.invoices.show', $inv) }}" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Open</a>
                                    <a href="{{ route('admin.campus.invoices.print', $inv) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No campus invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
