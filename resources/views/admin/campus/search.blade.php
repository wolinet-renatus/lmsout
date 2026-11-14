@extends('layouts.admin')

@section('title', 'Global Search')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Global Search</div>
    </div>
@endsection

@section('main-content')
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.campus.search') }}" method="GET" class="input-group input-group-lg">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search applicant name/phone, invoice/control number, student registration, or course...">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    @if ($empty)
        <div class="alert alert-info">Enter at least 2 characters to search across Applicants, Invoices &amp; Control Numbers, Students/Tutors, and Courses.</div>
    @else
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h4>Applicants ({{ $applicants->count() }})</h4></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($applicants as $a)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('admin.campus.applicants.show', $a) }}"><strong>{{ $a->full_name }}</strong></a>
                                        <div class="small text-muted">{{ $a->email }} &bull; {{ $a->phone }}</div>
                                    </div>
                                    <span class="badge badge-light">{{ $a->branch?->name ?? '-' }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No matching applicants.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h4>Invoices &amp; Control Numbers ({{ $invoices->count() }})</h4></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($invoices as $inv)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('admin.campus.invoices.show', $inv) }}"><code>{{ $inv->invoice_number }}</code></a>
                                        @if ($inv->control_number)
                                            <span class="badge badge-info ml-1">{{ $inv->control_number }}</span>
                                        @endif
                                        <div class="small text-muted">{{ $inv->applicant?->full_name ?? $inv->user?->name ?? '-' }} &bull; {{ $inv->course?->title ?? '-' }}</div>
                                    </div>
                                    <span class="badge badge-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                        {{ strtoupper($inv->payment_status) }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No matching invoices.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h4>Students &amp; Tutors ({{ $users->count() }})</h4></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($users as $u)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $u->name }}</strong>
                                        <div class="small text-muted">{{ $u->email }} {{ $u->registration_number ? '• ' . $u->registration_number : '' }}</div>
                                    </div>
                                    <span class="badge badge-primary text-capitalize">{{ $u->role }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No matching users.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h4>Courses ({{ $courses->count() }})</h4></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($courses as $c)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $c->title }}</strong>
                                        <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $c->delivery_mode ?? 'online')) }}</div>
                                    </div>
                                    <span class="badge badge-secondary">{{ ucfirst($c->status) }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No matching courses.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
