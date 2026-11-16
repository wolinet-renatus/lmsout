<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role seeder — seeds baseline institutional, RBAC, or reference records.
 *
 * @author Renatus Bernardo
 */
class RoleSeeder extends Seeder
{
    /**
     * Granular permission groups for Admin, Super Admin & Staff (v2.2.0).
     */
    public static function permissionGroups(): array
    {
        return [
            'Courses & Curriculum' => [
                'view-course'       => 'View courses',
                'create-course'     => 'Create courses',
                'edit-course'       => 'Edit & approve courses',
                'delete-course'     => 'Delete courses',
                'manage-courses'    => 'Manage all courses, categories & languages',
                'manage-bundles'    => 'Manage course bundles',
            ],
            'Students & Instructors' => [
                'view-users'         => 'View students & instructors',
                'student:create'     => 'Create students directly',
                'student:update'     => 'Edit student accounts',
                'student:delete'     => 'Delete students',
                'instructor:create'  => 'Create instructors directly',
                'instructor:update'  => 'Edit instructor accounts',
                'instructor:delete'  => 'Delete instructors',
                'approve-instructor' => 'Approve instructor applications',
                'reject-instructor'  => 'Reject instructor applications',
                'delete-users'       => 'Delete user accounts',
            ],
            'Finance & Billing' => [
                'manage-payments'      => 'Manage payments & offline approvals',
                'manage-withdrawals'   => 'Manage instructor payout requests',
                'manage-coupons'       => 'Manage discount coupons',
                'manage-invoices'      => 'Manage campus invoices & control numbers',
                'manage-installments'  => 'Manage payment schedules & overdue reminders',
            ],
            'Campus & Short Courses' => [
                'manage-branches'      => 'Manage regional branches & centers',
                'manage-venues'        => 'Manage physical rooms & labs',
                'manage-countries'     => 'Manage countries & nationality lookup',
                'manage-applicants'    => 'Register & manage F2F/Hybrid applicants',
                'manage-timetables'    => 'Assign tutors, venues & cohorts',
                'manage-attendance'    => 'Mark & verify QR/manual attendance',
            ],
            'Reports & Analytics' => [
                'view-reports'         => 'View executive, applicant & center course reports',
                'export-reports'       => 'Export applicant & center reports (PDF/Excel/CSV)',
                'view-analytics'       => 'View active classes, branch, discount & tutor workload analytics',
            ],
            'CMS, Certificates & Marketing' => [
                'manage-certificates'  => 'Manage certificates & drag-and-drop builder',
                'manage-frontend'      => 'Manage homepage, about page, menus & SEO',
                'manage-notifications' => 'Send push & broadcast notifications',
            ],
            'Support & Administration' => [
                'manage-tickets'  => 'Manage student & instructor support tickets',
                'manage-staff'    => 'Manage staff members & admin restrictions',
                'manage-roles'    => 'Manage roles & granular permissions',
                'view-audit-logs' => 'View security & activity audit logs',
                'manage-settings' => 'Manage platform, tax, live class & gateway settings',
                'manage-backups'  => 'Create, download & delete system backups',
                'manage-all'      => 'Full Super Admin unrestricted access',
            ],
            'Learner Actions' => [
                'enroll-course'   => 'Enroll in courses',
                'unenroll-course' => 'Unenroll from courses',
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (static::permissionGroups() as $group => $items) {
            foreach (array_keys($items) as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        // Create core & campus roles
        $superAdminRole     = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole          = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $sysAdminRole       = Role::firstOrCreate(['name' => 'system-administrator', 'guard_name' => 'web']);
        $directorRole       = Role::firstOrCreate(['name' => 'director', 'guard_name' => 'web']);
        $deputyDirectorRole = Role::firstOrCreate(['name' => 'deputy-director', 'guard_name' => 'web']);
        $branchRegistrar    = Role::firstOrCreate(['name' => 'branch-registrar', 'guard_name' => 'web']);
        $coordinatorRole    = Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        $marketingRole      = Role::firstOrCreate(['name' => 'marketing-officer', 'guard_name' => 'web']);
        $accountantRole     = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $staffRole          = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $instructorRole     = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        $studentRole        = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Super Admin, Admin & System Administrator get all permissions
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
        $adminRole->syncPermissions($allPermissions);
        $sysAdminRole->syncPermissions($allPermissions);

        // Director & Deputy Director: institutional multi-branch academic, finance & certificate oversight
        $directorPermissions = [
            'view-course',
            'manage-courses',
            'view-users',
            'approve-instructor',
            'reject-instructor',
            'manage-branches',
            'manage-venues',
            'manage-countries',
            'manage-applicants',
            'manage-timetables',
            'manage-attendance',
            'manage-invoices',
            'manage-installments',
            'manage-payments',
            'manage-certificates',
            'manage-notifications',
            'manage-tickets',
            'view-reports',
            'export-reports',
            'view-analytics',
            'view-audit-logs',
        ];
        $directorRole->syncPermissions($directorPermissions);
        $deputyDirectorRole->syncPermissions($directorPermissions);

        // Staff gets default operational permissions (without destructive system/settings access)
        $staffRole->syncPermissions([
            'view-course',
            'edit-course',
            'manage-courses',
            'view-users',
            'student:create',
            'student:update',
            'approve-instructor',
            'manage-certificates',
            'manage-tickets',
            'manage-applicants',
            'manage-attendance',
            'manage-timetables',
            'view-reports',
            'view-analytics',
        ]);

        // Branch Manager / Registrar: branch-scoped operations across registration, classes, invoices & reports
        $branchRegistrar->syncPermissions([
            'view-course',
            'view-users',
            'student:create',
            'student:update',
            'manage-applicants',
            'manage-venues',
            'manage-invoices',
            'manage-installments',
            'manage-payments',
            'manage-attendance',
            'manage-timetables',
            'manage-certificates',
            'manage-tickets',
            'view-reports',
            'export-reports',
            'view-analytics',
        ]);

        // Training Coordinator: academic coordination, timetables, venues, applicants & center reports
        $coordinatorRole->syncPermissions([
            'view-course',
            'manage-courses',
            'view-users',
            'manage-applicants',
            'manage-venues',
            'manage-countries',
            'manage-timetables',
            'manage-attendance',
            'manage-certificates',
            'manage-notifications',
            'view-reports',
            'export-reports',
            'view-analytics',
        ]);

        // Marketing Officer: courses, student outreach, coupons, notifications, payments & reports
        $marketingRole->syncPermissions([
            'view-course',
            'manage-courses',
            'manage-bundles',
            'view-users',
            'student:create',
            'student:update',
            'manage-applicants',
            'manage-payments',
            'manage-invoices',
            'manage-coupons',
            'manage-frontend',
            'manage-notifications',
            'view-reports',
            'export-reports',
            'view-analytics',
        ]);

        // Accountant: finance, invoices, control numbers, installments & financial reports
        $accountantRole->syncPermissions([
            'view-users',
            'manage-payments',
            'manage-withdrawals',
            'manage-coupons',
            'manage-invoices',
            'manage-installments',
            'view-reports',
            'export-reports',
            'view-analytics',
        ]);

        // Instructor permissions
        $instructorRole->syncPermissions([
            'create-course',
            'edit-course',
            'delete-course',
            'view-course',
        ]);

        // Student permissions
        $studentRole->syncPermissions([
            'view-course',
            'enroll-course',
            'unenroll-course',
        ]);

        // Ensure existing admin users without a role get super-admin
        User::where('role', 'admin')->get()->each(function (User $user) {
            if ($user->roles()->count() === 0) {
                $user->assignRole('super-admin');
            }
        });
    }
}
