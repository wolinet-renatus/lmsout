@extends('layouts.admin')

@section('title', 'Edit Staff / Admin')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff &amp; Roles</a></div>
        <div class="breadcrumb-item">Edit {{ $staff->name }}</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Edit Staff / Administrator: {{ $staff->name }}</h4>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
        <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $staff->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $staff->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Assigned Role <span class="text-danger">*</span></label>
                        <select name="spatie_role" class="form-control" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ old('spatie_role', $currentRole) === $role->name ? 'selected' : '' }}>
                                    {{ strtoupper(str_replace('-', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Assigned Regional Branch / Center</label>
                        <select name="branch_id" class="form-control">
                            <option value="">— Multi-Branch / Institutional —</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $staff->branch_id) === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>New Password <span class="text-muted small">(Leave blank to keep current)</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8">
                    </div>
                    <div class="form-group col-md-8 d-flex align-items-center pt-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="force_password_reset" name="force_password_reset" value="1"
                                {{ old('force_password_reset', $staff->force_password_reset) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="force_password_reset">Require password change on next login</label>
                        </div>
                    </div>
                </div>

                <hr>
                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-key mr-1 text-primary"></i> Granular Permissions</h6>
                <div class="row">
                    @foreach ($permissionGroups as $groupName => $items)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="font-weight-bold text-primary border-bottom pb-1 mb-2" style="font-size: 13px;">{{ $groupName }}</h6>
                                @foreach ($items as $permKey => $permLabel)
                                    <div class="custom-control custom-checkbox mb-1">
                                        <input type="checkbox" class="custom-control-input" id="perm_{{ $permKey }}"
                                            name="permissions[]" value="{{ $permKey }}"
                                            {{ in_array($permKey, old('permissions', $assignedPermissions), true) ? 'checked' : '' }}>
                                        <label class="custom-control-label small" for="perm_{{ $permKey }}">
                                            {{ $permLabel }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection

