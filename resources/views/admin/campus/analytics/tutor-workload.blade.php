@extends('layouts.admin')

@section('title', 'Tutor & Instructor Workload')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Analytics</div>
        <div class="breadcrumb-item active">Tutor Workload</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4><i class="fas fa-chalkboard mr-2 text-primary"></i>Tutor / Instructor Workload Summary</h4>
            @if (auth()->user()->isMultiBranchAdmin())
                <form method="GET" class="form-inline">
                    <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tutor Name</th>
                            <th>Primary Branch</th>
                            <th>Specialization</th>
                            <th class="text-center">Active Cohorts</th>
                            <th class="text-center">Completed Cohorts</th>
                            <th class="text-center">Total Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tutors as $t)
                            <tr>
                                <td><strong>{{ $t['name'] }}</strong><div class="small text-muted">{{ $t['email'] }}</div></td>
                                <td>{{ $t['branch'] }}</td>
                                <td>{{ $t['specialization'] }}</td>
                                <td class="text-center"><span class="badge badge-primary">{{ $t['active_sessions'] }}</span></td>
                                <td class="text-center"><span class="badge badge-success">{{ $t['completed_sessions'] }}</span></td>
                                <td class="text-center font-weight-bold">{{ $t['total_sessions'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No instructors found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
