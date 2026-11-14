@extends('layouts.admin')

@section('title', 'Countries & Nationalities')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Campus &amp; Short Courses</div>
        <div class="breadcrumb-item active">Countries</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header"><h4><i class="fas fa-plus mr-1 text-primary"></i>Add Country</h4></div>
                <form action="{{ route('admin.campus.countries.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>ISO Code (2 chars) <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" maxlength="10" required placeholder="e.g. TZ">
                        </div>
                        <div class="form-group">
                            <label>Official Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. TANZANIA, UNITED REPUBLIC OF">
                        </div>
                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="nicename" class="form-control" placeholder="e.g. Tanzania">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>ISO3</label>
                                <input type="text" name="iso3" class="form-control" maxlength="5" placeholder="TZA">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Phone Code <span class="text-danger">*</span></label>
                                <input type="number" name="phonecode" class="form-control" required value="255">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Save Country</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-globe mr-2 text-primary"></i>Countries List</h4>
                    <form method="GET" class="form-inline">
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm mr-2" placeholder="Search country...">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Country</th>
                                    <th>Dial Code</th>
                                    <th>Linked Records</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($countries as $country)
                                    <tr>
                                        <td><code>{{ $country->code }}</code></td>
                                        <td><strong>{{ $country->display_name }}</strong><div class="small text-muted">{{ $country->name }}</div></td>
                                        <td>+{{ $country->phonecode }}</td>
                                        <td><span class="badge badge-light">{{ $country->branches_count }} branches &bull; {{ $country->applicants_count }} applicants</span></td>
                                        <td class="text-right text-nowrap">
                                            <form action="{{ route('admin.campus.countries.destroy', $country) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete {{ $country->display_name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No countries found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($countries->hasPages())
                    <div class="card-footer">{{ $countries->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
