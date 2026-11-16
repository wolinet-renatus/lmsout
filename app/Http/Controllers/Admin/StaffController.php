<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Rules\StrongPassword;
use Database\Seeders\RoleSeeder;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Staff & Role Management Controller (v2.2.0)
 *
 * Manages Super Admins, Branch Managers, Coordinators, Marketing Officers, Directors, Staff & Role Permissions.
 * @author Renatus Bernardo
 */
class StaffController extends Controller
{
    /**
     * List staff/admin users and role-permission matrices.
     */
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'staff');
        $roleFilter = $request->query('role');
        $branchFilter = $request->query('branch_id');
        $statusFilter = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $staffQuery = User::where('role', 'admin')
            ->with(['roles', 'permissions', 'branch:id,name'])
            ->when($roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $roleFilter)))
            ->when($branchFilter, fn ($q) => $q->where('branch_id', $branchFilter))
            ->when($statusFilter === 'blocked', fn ($q) => $q->where('is_blocked', true))
            ->when($statusFilter === 'active', fn ($q) => $q->where('is_blocked', false))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")));

        $staffMembers = $staffQuery->orderBy('id')->paginate(20)->withQueryString();

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get();

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $permissionGroups = RoleSeeder::permissionGroups();

        return view('admin.staff.index', compact(
            'activeTab',
            'staffMembers',
            'roles',
            'branches',
            'permissionGroups'
        ));
    }

    /**
     * Show form to create a new Staff / Admin user.
     */
    public function create(): View
    {
        $roles = Role::whereNotIn('name', ['student', 'instructor'])->orderBy('id')->get();
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $permissionGroups = RoleSeeder::permissionGroups();

        return view('admin.staff.create', compact('roles', 'branches', 'permissionGroups'));
    }

    /**
     * Store a newly created Staff / Admin user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                => ['nullable', 'string', 'max:30'],
            'branch_id'            => ['nullable', 'integer', 'exists:branches,id'],
            'password'             => ['required', 'confirmed', new StrongPassword()],
            'spatie_role'          => ['required', 'string', 'exists:roles,name'],
            'force_password_reset' => ['nullable', 'boolean'],
            'permissions'          => ['nullable', 'array'],
            'permissions.*'        => ['string', 'exists:permissions,name'],
        ]);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'phone'                => $validated['phone'] ?? null,
            'branch_id'            => $validated['branch_id'] ?? null,
            'password'             => Hash::make($validated['password']),
            'role'                 => 'admin',
            'is_approved'          => true,
            'force_password_reset' => (bool) ($validated['force_password_reset'] ?? true),
            'password_changed_at'  => now(),
            'email_verified_at'    => now(),
        ]);

        $user->syncRoles([$validated['spatie_role']]);

        if (! empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        try {
            activity('security')->causedBy(auth()->user())->performedOn($user)->event('staff_created')
                ->log("Created staff/admin {$user->email} with role {$validated['spatie_role']}");
        } catch (\Throwable) {
        }

        ToastMagic::success("Staff / Admin account \"{$user->name}\" created with role [{$validated['spatie_role']}].");

        return redirect()->route('admin.staff.index');
    }

    /**
     * Show form to edit an existing Staff / Admin user.
     */
    public function edit(User $staff): View
    {
        abort_unless($staff->role === 'admin', 404);

        $roles = Role::whereNotIn('name', ['student', 'instructor'])->orderBy('id')->get();
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $permissionGroups = RoleSeeder::permissionGroups();
        $assignedPermissions = $staff->permissions->pluck('name')->toArray();
        $currentRole = $staff->roles->first()?->name ?? 'super-admin';

        return view('admin.staff.edit', compact(
            'staff',
            'roles',
            'branches',
            'permissionGroups',
            'assignedPermissions',
            'currentRole'
        ));
    }

    /**
     * Update a Staff / Admin user and their granular permissions.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'admin', 404);

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone'                => ['nullable', 'string', 'max:30'],
            'branch_id'            => ['nullable', 'integer', 'exists:branches,id'],
            'password'             => ['nullable', 'confirmed', new StrongPassword()],
            'spatie_role'          => ['required', 'string', 'exists:roles,name'],
            'force_password_reset' => ['nullable', 'boolean'],
            'permissions'          => ['nullable', 'array'],
            'permissions.*'        => ['string', 'exists:permissions,name'],
        ]);

        $updateData = [
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'phone'                => $validated['phone'] ?? null,
            'branch_id'            => $validated['branch_id'] ?? null,
            'force_password_reset' => (bool) ($validated['force_password_reset'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
            $updateData['password_changed_at'] = now();
        }

        $staff->update($updateData);
        $staff->syncRoles([$validated['spatie_role']]);
        $staff->syncPermissions($validated['permissions'] ?? []);

        try {
            activity('security')->causedBy(auth()->user())->performedOn($staff)->event('staff_updated')
                ->log("Updated staff/admin {$staff->email} (role: {$validated['spatie_role']})");
        } catch (\Throwable) {
        }

        ToastMagic::success("Staff / Admin \"{$staff->name}\" updated successfully.");

        return redirect()->route('admin.staff.index');
    }

    /**
     * Remove a Staff / Admin account.
     */
    public function destroy(User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'admin', 404);

        if ($staff->id === auth()->id()) {
            ToastMagic::error('You cannot delete your own active admin account.');
            return back();
        }

        if (User::where('role', 'admin')->count() <= 1) {
            ToastMagic::error('Cannot delete the last remaining administrator.');
            return back();
        }

        $name = $staff->name;
        $staff->delete();

        ToastMagic::success("Account \"{$name}\" removed.");

        return redirect()->route('admin.staff.index');
    }

    /**
     * Create a new custom Role with granular permissions.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:60', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name'       => strtolower(str_replace(' ', '-', trim($validated['name']))),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        ToastMagic::success("Role \"{$role->name}\" created successfully.");

        return redirect()->route('admin.staff.index', ['tab' => 'roles']);
    }

    /**
     * Update granular permissions for a Role.
     */
    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super-admin') {
            $role->syncPermissions(Permission::all());
            ToastMagic::info('Super Admin always retains all platform permissions.');
            return redirect()->route('admin.staff.index', ['tab' => 'roles']);
        }

        $validated = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        ToastMagic::success("Permissions for role [{$role->name}] updated.");

        return redirect()->route('admin.staff.index', ['tab' => 'roles']);
    }

    /**
     * Delete a custom Role.
     */
    public function destroyRole(Role $role): RedirectResponse
    {
        $protected = ['super-admin', 'admin', 'staff', 'instructor', 'student'];
        if (in_array($role->name, $protected, true)) {
            ToastMagic::error("System role [{$role->name}] cannot be deleted.");
            return redirect()->route('admin.staff.index', ['tab' => 'roles']);
        }

        $name = $role->name;
        $role->delete();

        ToastMagic::success("Role [{$name}] deleted.");

        return redirect()->route('admin.staff.index', ['tab' => 'roles']);
    }
}

