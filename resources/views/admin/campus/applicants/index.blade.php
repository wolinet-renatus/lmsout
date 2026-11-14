@extends('layouts.admin')

@section('title', 'F2F & Hybrid Applicants')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Applicants</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Registered Applicants</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.applicants.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> New Applicant</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <input type="text" name="search" class="form-control form-control-sm mr-2" value="{{ request('search') }}" placeholder="Name, email or phone...">
                <select name="branch_id" class="form-control form-control-sm mr-2">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="source" class="form-control form-control-sm mr-2">
                    <option value="">All Sources</option>
                    <option value="admin" {{ request('source') === 'admin' ? 'selected' : '' }}>Registrar Desk</option>
                    <option value="public" {{ request('source') === 'public' ? 'selected' : '' }}>Public Self-Application</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
                <a href="{{ route('admin.campus.applicants.index') }}" class="btn btn-sm btn-light">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th>Branch</th>
                            <th>Course</th>
                            <th>Invoice / Control No.</th>
                            <th>Source</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applicants as $a)
                            @php $inv = $a->invoices->first(); $enr = $a->enrollments->first(); @endphp
                            <tr>
                                <td>
                                    <strong>{{ $a->full_name }}</strong>
                                    @if ($a->is_staff_student) <span class="badge badge-info">Staff 50%</span> @endif
                                    @if ($a->has_discount) <span class="badge badge-success">Discount</span> @endif
                                </td>
                                <td class="small">{{ $a->phone }}<br>{{ $a->email ?? '-' }}</td>
                                <td>{{ $a->branch?->name ?? '-' }}</td>
                                <td>{{ $enr?->course?->title ?? '-' }}</td>
                                <td class="small">
                                    @if ($inv)
                                        <a href="{{ route('admin.campus.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a>
                                        <br><span class="text-muted">CN: {{ $inv->control_number ?? '-' }}</span>
                                        <span class="badge badge-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($inv->payment_status) }}</span>
                                    @else - @endif
                                </td>
                                <td><span class="badge badge-light">{{ ucfirst($a->source ?? 'admin') }}</span></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.campus.applicants.show', $a) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No applicants found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $applicants->links() }}
        </div>
    </div>
@endsection
