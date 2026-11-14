@extends('layouts.admin')

@section('title', 'Regional Branches & Centres')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Branches</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Add Branch / Centre</h4>
                </div>
                <form action="{{ route('admin.campus.branches.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Kinondoni Regional Centre">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Branch Code</label>
                            <input type="text" name="branch_code" class="form-control @error('branch_code') is-invalid @enderror" value="{{ old('branch_code') }}" placeholder="e.g. KND">
                            @error('branch_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <select name="country_id" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach ($countries as $c)
                                    <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="2" class="form-control">{{ old('address') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Branch</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>All Branches</h4>
                    <div class="card-header-action">
                        <form method="GET" class="form-inline">
                            <input type="text" name="search" class="form-control form-control-sm mr-1" value="{{ request('search') }}" placeholder="Search branch...">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Contact</th>
                                    <th>Venues</th>
                                    <th>Enrollments</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branches as $branch)
                                    <tr>
                                        <td><strong>{{ $branch->name }}</strong></td>
                                        <td><span class="badge badge-light">{{ $branch->branch_code ?? '-' }}</span></td>
                                        <td class="small">{{ $branch->phone ?? '-' }}<br>{{ $branch->email ?? '' }}</td>
                                        <td>{{ $branch->venues_count }}</td>
                                        <td>{{ $branch->enrollments_count }}</td>
                                        <td>
                                            <span class="badge badge-{{ $branch->is_active ? 'success' : 'secondary' }}">
                                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('admin.campus.branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('admin.campus.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this branch?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No branches found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($branches->hasPages())
                    <div class="card-footer">{{ $branches->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
