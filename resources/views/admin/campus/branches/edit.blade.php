@extends('layouts.admin')

@section('title', 'Edit Branch')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.branches.index') }}">Branches</a></div>
        <div class="breadcrumb-item active">Edit</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4>Edit Branch: {{ $branch->name }}</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.campus.branches.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <form action="{{ route('admin.campus.branches.update', $branch) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $branch->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label>Branch Code</label>
                        <input type="text" name="branch_code" class="form-control @error('branch_code') is-invalid @enderror" value="{{ old('branch_code', $branch->branch_code) }}">
                        @error('branch_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $branch->email) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Country</label>
                        <select name="country_id" class="form-control">
                            <option value="">-- Select --</option>
                            @foreach ($countries as $c)
                                <option value="{{ $c->id }}" {{ old('country_id', $branch->country_id) == $c->id ? 'selected' : '' }}>{{ $c->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12">
                        <label>Address</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $branch->address) }}</textarea>
                    </div>
                    <div class="form-group col-12 mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
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
