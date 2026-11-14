@extends('layouts.admin')

@section('title', 'Applicant Registration & Payment Report')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></div>
        <div class="breadcrumb-item active">Applicant Report</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-user-friends"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Matching Applicants</h4></div>
                    <div class="card-body">{{ number_format($totalApplicants) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-percent"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Discounted / Staff Waivers</h4></div>
                    <div class="card-body">{{ number_format($discountedCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4><i class="fas fa-file-alt mr-2 text-primary"></i>F2F / Hybrid Applicant Report</h4>
            @if (auth()->user()->hasAdminPermission('export-reports'))
                <div class="btn-group">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv mr-1"></i> CSV
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                </div>
            @endif
        </div>

        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('admin.reports.applicants') }}" class="form-row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Applicant Name / Email / Phone</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search applicant...">
                </div>
                @if (auth()->user()->isMultiBranchAdmin())
                    <div class="col-md-2 mb-2">
                        <label class="small text-muted mb-1">Branch</label>
                        <select name="branch_id" class="form-control form-control-sm">
                            <option value="">All Branches</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Course</label>
                    <select name="course_id" class="form-control form-control-sm">
                        <option value="">All Courses</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ (string) request('course_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Payment Status</label>
                    <select name="payment_status" class="form-control form-control-sm">
                        <option value="">Any</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Full Paid</option>
                        <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex">
                    <select name="discount_filter" class="form-control form-control-sm mr-2">
                        <option value="">All Fee Types</option>
                        <option value="discounted" {{ request('discount_filter') === 'discounted' ? 'selected' : '' }}>Discounted</option>
                        <option value="staff" {{ request('discount_filter') === 'staff' ? 'selected' : '' }}>Staff Students</option>
                    </select>
                    <button class="btn btn-sm btn-primary mr-1" type="submit"><i class="fas fa-filter"></i></button>
                    <a href="{{ route('admin.reports.applicants') }}" class="btn btn-sm btn-light"><i class="fas fa-times"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Branch &amp; Country</th>
                            <th>Enrolled Course(s)</th>
                            <th>Status / Waiver</th>
                            <th class="text-right">Invoiced</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applicants as $applicant)
                            @php
                                $invoiced = (float) $applicant->invoices->sum('net_amount');
                                $paid = (float) $applicant->invoices->sum('paid_amount');
                                $balance = (float) $applicant->invoices->sum('balance');
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.campus.applicants.show', $applicant) }}"><strong>{{ $applicant->full_name }}</strong></a>
                                    <div class="small text-muted">{{ $applicant->email }} • {{ $applicant->phone }}</div>
                                </td>
                                <td>
                                    {{ $applicant->branch?->name ?? '-' }}
                                    <div class="small text-muted">{{ $applicant->country?->display_name ?? '-' }}</div>
                                </td>
                                <td>
                                    {{ $applicant->enrollments->map(fn ($e) => $e->course?->title)->filter()->unique()->implode(', ') ?: 'Pending' }}
                                </td>
                                <td>
                                    @if ($applicant->has_discount)
                                        <span class="badge badge-info">{{ $applicant->discount_reason ?? 'Discount' }}</span>
                                    @elseif ($applicant->is_staff_student)
                                        <span class="badge badge-primary">Staff</span>
                                    @else
                                        <span class="badge badge-light">Standard</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ currency_format($invoiced) }}</td>
                                <td class="text-right text-success font-weight-bold">{{ currency_format($paid) }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ currency_format($balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No matching applicants found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($applicants->hasPages())
            <div class="card-footer">{{ $applicants->links() }}</div>
        @endif
    </div>
@endsection
