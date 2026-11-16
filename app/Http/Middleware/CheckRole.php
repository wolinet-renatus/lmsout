<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
/**
 * Check Role Middleware
 *
 * Enforces portal-level role isolation (/admin, /instructor, /student),
 * binds the active regional branch context into session for admin staff,
 * and evaluates route-prefix Spatie permissions for scoped staff roles.
 *
 * @package App\Http\Middleware
 * @author Renatus Bernardo
 */
class CheckRole
{
    /**
     * Authorize the authenticated user for the requested portal and route permission.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Allow administrators who have initiated an impersonation session to enter target portals.
        if (session('impersonating_admin_id')) {
            if ($role === 'instructor' && session('impersonating_instructor_id')) {
                return $next($request);
            }
            if ($role === 'student' && session('impersonating_student_id')) {
                return $next($request);
            }
        }
        
        if ($user->role !== $role) {
            abort(403, 'Unauthorized access');
        }

        // Bind branch context & enforce granular admin restrictions for Staff / Branch / Coordinator / Marketing / Accountant / Director accounts
        if ($role === 'admin' && method_exists($user, 'effectiveBranchId')) {
            session(['user_branch_id' => $user->effectiveBranchId()]);
        }

        if ($role === 'admin' && method_exists($user, 'isStaff') && $user->isStaff()) {
            $routeName = (string) ($request->route()?->getName() ?? '');
            $permissionMap = [
                'admin.staff.'                 => 'manage-staff',
                'admin.roles.'                 => 'manage-roles',
                'admin.backups.'               => 'manage-backups',
                'admin.audit-logs.'            => 'view-audit-logs',
                'admin.settings.'              => 'manage-settings',
                'admin.languages.'             => 'manage-settings',
                'admin.updater.'               => 'manage-settings',
                'admin.payments.'              => 'manage-payments',
                'admin.withdrawals.'           => 'manage-withdrawals',
                'admin.coupons.'               => 'manage-coupons',
                'admin.certificates.'          => 'manage-certificates',
                'admin.reports.'               => 'view-reports',
                'admin.frontend.'              => 'manage-frontend',
                'admin.custom-pages.'          => 'manage-frontend',
                'admin.banners.'               => 'manage-frontend',
                'admin.notifications.'         => 'manage-notifications',
                'admin.tickets.'               => 'manage-tickets',
                'admin.courses.'               => 'manage-courses',
                'admin.course-categories.'     => 'manage-courses',
                'admin.course-languages.'      => 'manage-courses',
                'admin.bundles.'               => 'manage-bundles',
                'admin.campus.branches.'       => 'manage-branches',
                'admin.campus.venues.'         => 'manage-venues',
                'admin.campus.countries.'      => 'manage-countries',
                'admin.campus.applicants.'     => 'manage-applicants',
                'admin.campus.timetable.'      => 'manage-timetables',
                'admin.campus.attendance.'     => 'manage-attendance',
                'admin.campus.invoices.'       => 'manage-invoices',
                'admin.campus.installments.'   => 'manage-installments',
                'admin.campus.certificates.'   => 'manage-certificates',
                'admin.campus.analytics.'      => 'view-analytics',
                'admin.campus.templates.'      => 'manage-settings',
                'admin.user-security.'         => 'student:update',
                'admin.students.create'        => 'student:create',
                'admin.students.store'         => 'student:create',
                'admin.students.destroy'       => 'student:delete',
                'admin.instructors.create'     => 'instructor:create',
                'admin.instructors.store'      => 'instructor:create',
                'admin.instructors.destroy'    => 'instructor:delete',
                'admin.students.'              => 'view-users',
                'admin.instructors.'           => 'view-users',
                'admin.users.'                 => 'view-users',
            ];

            foreach ($permissionMap as $prefix => $requiredPermission) {
                if (str_starts_with($routeName, $prefix)) {
                    if (! $user->hasAdminPermission($requiredPermission)) {
                        abort(403, 'Access restricted: your staff role does not have the [' . $requiredPermission . '] permission.');
                    }
                    break;
                }
            }
        }

        return $next($request);
    }
}

