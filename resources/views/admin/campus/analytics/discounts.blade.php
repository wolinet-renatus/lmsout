@extends('layouts.admin')

@section('title', 'Discount & Waiver Analytics')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Analytics</div>
        <div class="breadcrumb-item active">Discounts</div>
    </div>
@endsection

@section('main-content')
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h6 class="text-muted text-uppercase small mb-1">Total Discounts / Staff Waivers Granted</h6>
                <h3 class="text-primary mb-0">{{ currency_format($totalWaived) }}</h3>
            </div>
            @if (auth()->user()->isMultiBranchAdmin())
                <form method="GET" class="form-inline mt-2 mt-md-0">
                    <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Discounted Invoices</h4></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Student / Applicant</th>
                            <th>Course</th>
                            <th>Branch</th>
                            <th>Reason</th>
                            <th class="text-right">Original Fee</th>
                            <th class="text-right">Waiver Amount</th>
                            <th class="text-right">Net Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discountedInvoices as $inv)
                            <tr>
                                <td><a href="{{ route('admin.campus.invoices.show', $inv) }}"><code>{{ $inv->invoice_number }}</code></a></td>
                                <td>{{ $inv->applicant?->full_name ?? $inv->user?->name ?? '-' }}</td>
                                <td>{{ $inv->course?->title ?? '-' }}</td>
                                <td>{{ $inv->branch?->name ?? '-' }}</td>
                                <td><span class="badge badge-info">{{ $inv->applicant?->discount_reason ?? 'Waiver / Staff' }}</span></td>
                                <td class="text-right">{{ currency_format($inv->total_amount) }}</td>
                                <td class="text-right text-warning font-weight-bold">-{{ currency_format($inv->discount_amount) }}</td>
                                <td class="text-right font-weight-bold">{{ currency_format($inv->net_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No discounted invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($discountedInvoices->hasPages())
            <div class="card-footer">{{ $discountedInvoices->links() }}</div>
        @endif
    </div>
@endsection
