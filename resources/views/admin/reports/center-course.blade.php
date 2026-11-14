@extends('layouts.admin')

@section('title', 'Regional Center Course Report')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></div>
        <div class="breadcrumb-item active">Center Course Report</div>
    </div>
@endsection

@section('main-content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4><i class="fas fa-university mr-2 text-primary"></i>Ongoing Regional Center Course Report (Weekly / Monthly / Quarterly)</h4>
            @if (auth()->user()->hasAdminPermission('export-reports'))
                <div class="btn-group">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-csv mr-1"></i> Export CSV
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf mr-1"></i> Download PDF
                    </a>
                </div>
            @endif
        </div>
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('admin.reports.center-courses') }}" class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="small font-weight-bold">Reporting Period</label>
                    <select name="period" class="form-control">
                        <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly (This Week)</option>
                        <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                        <option value="quarterly" {{ $period === 'quarterly' ? 'selected' : '' }}>Quarterly (This Quarter)</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                    </select>
                </div>
                @if (auth()->user()->isMultiBranchAdmin())
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold">Regional Center / Branch</label>
                        <select name="branch_id" class="form-control">
                            <option value="">All Centers</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-filter mr-1"></i> Apply</button>
                    <a href="{{ route('admin.reports.center-courses') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Centre Name</th>
                            <th>Course Name</th>
                            <th>Delivery Mode</th>
                            <th class="text-center">Total Students</th>
                            <th class="text-center">Full Paid</th>
                            <th class="text-center">Half / Partial</th>
                            <th class="text-center">Unpaid</th>
                            <th class="text-right">Collected</th>
                            <th class="text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><strong>{{ $row['branch_name'] }}</strong></td>
                                <td>{{ $row['course_title'] }}</td>
                                <td><span class="badge badge-info">{{ $row['teaching_method'] }}</span></td>
                                <td class="text-center font-weight-bold">{{ $row['total_students'] }}</td>
                                <td class="text-center"><span class="badge badge-success">{{ $row['full_paid'] }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ $row['half_paid'] }}</span></td>
                                <td class="text-center"><span class="badge badge-danger">{{ $row['unpaid'] }}</span></td>
                                <td class="text-right text-success font-weight-bold">{{ currency_format($row['collected']) }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ currency_format($row['outstanding']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No regional course enrollments found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
