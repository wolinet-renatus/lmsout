@extends('layouts.admin')

@section('title', 'Certificates')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Certificates</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Issued</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['total'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Last 7 Days</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['last_seven_days'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>This Month</h4>
                    </div>
                    <div class="card-body">
                        {{ $statistics['this_month'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>&times;</span></button>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" href="{{ route('admin.certificates.index', ['tab' => 'templates']) }}">
                <i class="fas fa-palette mr-1"></i> Certificate Templates <span class="badge badge-{{ $activeTab === 'templates' ? 'white' : 'primary' }}">{{ $templates->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'issued' ? 'active' : '' }}" href="{{ route('admin.certificates.index', ['tab' => 'issued']) }}">
                <i class="fas fa-file-signature mr-1"></i> Issued Certificates <span class="badge badge-{{ $activeTab === 'issued' ? 'white' : 'primary' }}">{{ $statistics['total'] }}</span>
            </a>
        </li>
    </ul>

    @if ($activeTab === 'templates')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0">Certificate Templates</h4>
                <div class="card-header-action">
                    <form action="{{ route('admin.certificates.templates.install-prebuilt') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-swatchbook mr-1"></i> Install all 6 pre-built designs</button>
                    </form>
                    <a href="{{ route('admin.certificates.templates.create') }}" class="btn btn-primary ml-1"><i class="fas fa-plus mr-1"></i> New Template</a>
                </div>
            </div>
            <div class="card-body p-0">
                @if ($templates->count())
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Orientation</th>
                                    <th>Courses</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $tmpl)
                                    <tr>
                                        <td>
                                            <strong>{{ $tmpl->name }}</strong>
                                            @if ($tmpl->is_default)
                                                <span class="badge badge-success ml-1">Default</span>
                                            @endif
                                            <div class="text-muted small">{{ count($tmpl->elements ?? []) }} elements</div>
                                        </td>
                                        <td class="text-capitalize">{{ $tmpl->orientation }}</td>
                                        <td>{{ $tmpl->courses_count }}</td>
                                        <td>
                                            <span class="badge badge-{{ $tmpl->is_active ? 'primary' : 'secondary' }}">{{ $tmpl->is_active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('admin.certificates.templates.preview', $tmpl) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('admin.certificates.templates.edit', $tmpl) }}" class="btn btn-sm btn-outline-primary" title="Edit in builder"><i class="fas fa-pencil-alt"></i></a>
                                            <form action="{{ route('admin.certificates.templates.duplicate', $tmpl) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary" title="Duplicate"><i class="far fa-copy"></i></button>
                                            </form>
                                            @unless ($tmpl->is_default)
                                                <form action="{{ route('admin.certificates.templates.default', $tmpl) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success" title="Set as default"><i class="fas fa-star"></i></button>
                                                </form>
                                            @endunless
                                            <form action="{{ route('admin.certificates.templates.destroy', $tmpl) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="far fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        No templates yet. Install the pre-built designs or create a new template with the drag &amp; drop builder.
                        Until then, the classic certificate layout is used.
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Assign Templates to Courses</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="pl-4">Course</th>
                                <th style="width: 360px;">Certificate Template</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courses as $course)
                                <tr>
                                    <td class="pl-4 align-middle">{{ $course->title }}</td>
                                    <td>
                                        <form action="{{ route('admin.certificates.templates.assign', $course) }}" method="POST" class="d-flex">
                                            @csrf
                                            @method('PUT')
                                            <select name="certificate_template_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                                <option value="">— System default —</option>
                                                @foreach ($templates as $tmpl)
                                                    <option value="{{ $tmpl->id }}" {{ (int) $course->certificate_template_id === $tmpl->id ? 'selected' : '' }}>{{ $tmpl->name }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted p-3">No courses found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
    <div class="card">
        <div class="card-header">
            <h4>Certificates</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.certificates.index') }}" class="mb-4">
                <input type="hidden" name="tab" value="issued">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="sr-only">Search</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control"
                                placeholder="Search by student or course" value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from" class="sr-only">Date from</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ $dateFrom }}" placeholder="From">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_to" class="sr-only">Date to</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}"
                            placeholder="To">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-center">
                        @if ($search || $dateFrom || $dateTo)
                            <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($certificates->count())
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Completed At</th>
                                <th>Certificate #</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($certificates as $certificate)
                                <tr>
                                    <td>
                                        <strong>{{ $certificate->user?->name ?? '—' }}</strong>
                                        <div class="text-muted small">{{ $certificate->user?->email ?? '—' }}</div>
                                    </td>
                                    <td>
                                        {{ $certificate->course?->title ?? '—' }}
                                    </td>
                                    <td>{{ $certificate->course?->instructor?->name ?? '—' }}</td>
                                    <td>{{ $certificate->completion_date ?? '—' }}</td>
                                    <td><code>{{ $certificate->certificate_number ?? '—' }}</code></td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.certificates.download', $certificate) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            Showing {{ $certificates->firstItem() ?? 0 }} to {{ $certificates->lastItem() ?? 0 }} of
                            {{ $certificates->total() }} certificates
                        </p>
                    </div>
                    <div>
                        {{ $certificates->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">No certificates found.</p>
            @endif
        </div>
    </div>
    @endif
@endsection
