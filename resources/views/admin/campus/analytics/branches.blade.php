@extends('layouts.admin')

@section('title', 'Branch Performance Analytics')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Analytics</div>
        <div class="breadcrumb-item active">Branches</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-code-branch"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Regional Branches</h4></div>
                    <div class="card-body">{{ $branches->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-coins"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Collected</h4></div>
                    <div class="card-body">{{ currency_format($branches->sum('collected')) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-exclamation-circle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Outstanding</h4></div>
                    <div class="card-body">{{ currency_format($branches->sum('outstanding')) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-building mr-2 text-primary"></i>Branch Revenue &amp; Capacity Breakdown</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Branch / Center</th>
                            <th>Code</th>
                            <th>Venues</th>
                            <th>Applicants</th>
                            <th>Invoices</th>
                            <th class="text-right">Invoiced</th>
                            <th class="text-right">Collected</th>
                            <th class="text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $row)
                            <tr>
                                <td><strong>{{ $row['name'] }}</strong><div class="small text-muted">{{ $row['address'] }}</div></td>
                                <td><code>{{ $row['code'] }}</code></td>
                                <td><span class="badge badge-light">{{ $row['venues_count'] }}</span></td>
                                <td>{{ $row['applicants_count'] }}</td>
                                <td>{{ $row['invoice_count'] }}</td>
                                <td class="text-right">{{ currency_format($row['invoiced']) }}</td>
                                <td class="text-right text-success font-weight-bold">{{ currency_format($row['collected']) }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ currency_format($row['outstanding']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No branches available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
