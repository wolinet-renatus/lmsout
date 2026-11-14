@extends('layouts.admin')

@section('title', 'Edit Venue')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.venues.index') }}">Venues</a></div>
        <div class="breadcrumb-item active">Edit</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Edit Venue: {{ $venue->name }}</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.venues.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <form action="{{ route('admin.campus.venues.update', $venue) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $venue->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Venue Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $venue->name) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $venue->capacity) }}" min="1" required>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Equipment</label>
                        <input type="text" name="equipment" class="form-control" value="{{ old('equipment', $venue->equipment) }}">
                    </div>
                    <div class="form-group col-12">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $venue->notes) }}</textarea>
                    </div>
                    <div class="form-group col-12 mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $venue->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
