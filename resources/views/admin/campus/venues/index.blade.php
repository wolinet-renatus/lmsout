@extends('layouts.admin')

@section('title', 'Physical Venues & Labs')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Venues &amp; Labs</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4>Add Venue / Room</h4></div>
                <form action="{{ route('admin.campus.venues.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                <option value="">-- Select Branch --</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Venue Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. ICT Lab 1">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 30) }}" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Equipment</label>
                            <input type="text" name="equipment" class="form-control" value="{{ old('equipment') }}" placeholder="Projector, 30 PCs...">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Venue</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Venues List</h4>
                    <div class="card-header-action">
                        <form method="GET" class="form-inline">
                            <select name="branch_id" class="form-control form-control-sm mr-1" onchange="this.form.submit()">
                                <option value="">All Branches</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="search" class="form-control form-control-sm mr-1" value="{{ request('search') }}" placeholder="Search...">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Venue</th>
                                    <th>Branch</th>
                                    <th>Capacity</th>
                                    <th>Timetables</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($venues as $venue)
                                    <tr>
                                        <td><strong>{{ $venue->name }}</strong><br><small class="text-muted">{{ $venue->equipment }}</small></td>
                                        <td>{{ $venue->branch?->name ?? '-' }}</td>
                                        <td>{{ $venue->capacity }} seats</td>
                                        <td>{{ $venue->course_assignments_count }}</td>
                                        <td><span class="badge badge-{{ $venue->is_active ? 'success' : 'secondary' }}">{{ $venue->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('admin.campus.venues.edit', $venue) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('admin.campus.venues.destroy', $venue) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this venue?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No venues found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($venues->hasPages())
                    <div class="card-footer">{{ $venues->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
