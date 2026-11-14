@extends('layouts.admin')

@section('title', 'Staff & Role Management')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Staff &amp; Role Management</div>
    </div>
@endsection

@section('main-content')
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'staff' ? 'active' : '' }}" href="{{ route('admin.staff.index', ['tab' => 'staff']) }}">
                <i class="fas fa-user-shield mr-1"></i> Super Admins &amp; Staff
                <span class="badge badge-{{ $activeTab === 'staff' ? 'white' : 'primary' }} ml-1">{{ $staffMembers->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'roles' ? 'active' : '' }}" href="{{ route('admin.staff.index', ['tab' => 'roles']) }}">
                <i class="fas fa-key mr-1"></i> Roles &amp; Granular Permissions
                <span class="badge badge-{{ $activeTab === 'roles' ? 'white' : 'primary' }} ml-1">{{ $roles->count() }}</span>
            </a>
        </li>
    </ul>

    @if ($activeTab === 'staff')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h4>Super Admins, Directors, Branch Managers &amp; Staff</h4>
                <a href="{{ route('admin.staff.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus mr-1"></i> Add Staff / Admin
                </a>
            </div>
            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('admin.staff.index') }}" class="form-row align-items-end">
                    <input type="hidden" name="tab" value="staff">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Search Name or Email</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search staff...">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Role</label>
                        <select name="role" class="form-control form-control-sm">
                            <option value="">All Roles</option>
                            @foreach ($roles as $r)
                                @if (! in_array($r->name, ['student', 'instructor'], true))
                                    <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ strtoupper(str_replace('-', ' ', $r->name)) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Branch / Center</label>
                        <select name="branch_id" class="form-control form-control-sm">
                            <option value="">All Branches</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex">
                        <select name="status" class="form-control form-control-sm mr-2">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        <button class="btn btn-sm btn-primary mr-1" type="submit"><i class="fas fa-filter"></i></button>
                        <a href="{{ route('admin.staff.index') }}" class="btn btn-sm btn-light"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name &amp; Email</th>
                                <th>Assigned Role &amp; Branch</th>
                                <th>Status &amp; Access Level</th>
                                <th>Created</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($staffMembers as $member)
                                @php
                                    $roleName = $member->roles->first()?->name ?? 'super-admin';
                                    $perms = $member->getAllPermissions();
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $member->name }}</strong>
                                        @if ($member->id === auth()->id())
                                            <span class="badge badge-info ml-1">You</span>
                                        @endif
                                        <div class="text-muted small">{{ $member->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $member->isSuperAdmin() ? 'success' : 'primary' }} text-uppercase">
                                            {{ str_replace('-', ' ', $roleName) }}
                                        </span>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-code-branch mr-1"></i>{{ $member->branch?->name ?? 'Institutional / All Branches' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($member->is_blocked)
                                            <span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>Blocked</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                        @if ($member->isSuperAdmin())
                                            <span class="badge badge-light ml-1"><i class="fas fa-unlock-alt text-success mr-1"></i>Unrestricted ({{ $perms->count() }} perms)</span>
                                        @else
                                            <span class="badge badge-warning ml-1">{{ $perms->count() }} Permissions</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($member->created_at)->format('M d, Y') }}</td>
                                    <td class="text-right text-nowrap">
                                        <a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary" title="Edit / Permissions">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if ($member->id !== auth()->id())
                                            @if ($member->is_blocked)
                                                <form action="{{ route('admin.user-security.unblock', $member) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Unblock User"><i class="fas fa-unlock"></i></button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.user-security.block', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Block {{ $member->name }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Block User"><i class="fas fa-ban"></i></button>
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.user-security.force-reset', $member) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-info" title="Force Password Reset"><i class="fas fa-key"></i></button>
                                            </form>
                                            <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete staff account {{ $member->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($staffMembers->hasPages())
                <div class="card-footer">{{ $staffMembers->links() }}</div>
            @endif
        </div>
    @else
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Create New Role</h4>
                    </div>
                    <form action="{{ route('admin.staff.roles.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. finance-manager, support-agent" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="d-block">Initial Permissions</label>
                                <div style="max-height: 280px; overflow-y: auto;" class="border rounded p-2">
                                    @foreach ($permissionGroups as $groupName => $items)
                                        <div class="font-weight-bold text-primary small mt-2 mb-1">{{ $groupName }}</div>
                                        @foreach ($items as $permKey => $permLabel)
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input" id="new_{{ $permKey }}" name="permissions[]" value="{{ $permKey }}">
                                                <label class="custom-control-label small" for="new_{{ $permKey }}">{{ $permLabel }} (<code>{{ $permKey }}</code>)</label>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Create Role</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                @foreach ($roles as $role)
                    @php
                        $rolePerms = $role->permissions->pluck('name')->toArray();
                        $isCore = in_array($role->name, ['super-admin', 'admin', 'staff', 'instructor', 'student'], true);
                    @endphp
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="d-inline text-uppercase">{{ $role->name }}</h4>
                                <span class="badge badge-light ml-2">{{ count($rolePerms) }} permissions</span>
                            </div>
                            @unless ($isCore)
                                <form action="{{ route('admin.staff.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete role {{ $role->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete Role</button>
                                </form>
                            @endunless
                        </div>
                        <form action="{{ route('admin.staff.roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($permissionGroups as $groupName => $items)
                                        <div class="col-md-6 mb-3">
                                            <h6 class="font-weight-bold text-dark border-bottom pb-1 mb-2" style="font-size: 13px;">{{ $groupName }}</h6>
                                            @foreach ($items as $permKey => $permLabel)
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="r_{{ $role->id }}_{{ $permKey }}"
                                                        name="permissions[]"
                                                        value="{{ $permKey }}"
                                                        {{ in_array($permKey, $rolePerms, true) ? 'checked' : '' }}
                                                        {{ $role->name === 'super-admin' ? 'disabled' : '' }}>
                                                    <label class="custom-control-label small" for="r_{{ $role->id }}_{{ $permKey }}">
                                                        {{ $permLabel }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if ($role->name !== 'super-admin')
                                <div class="card-footer text-right py-2">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save mr-1"></i> Save {{ $role->name }} Permissions</button>
                                </div>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection

