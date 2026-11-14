@php
    $adminUser = auth()->user();
    $can = fn (string $perm): bool => $adminUser ? $adminUser->hasAdminPermission($perm) : false;
    $canAny = function (array $perms) use ($can): bool {
        foreach ($perms as $p) {
            if ($can($p)) {
                return true;
            }
        }
        return false;
    };

    $inCoursesGroup = request()->routeIs('admin.courses.*')
        || request()->routeIs('admin.bundles.*')
        || request()->routeIs('admin.course-categories.*')
        || request()->routeIs('admin.course-languages.*')
        || request()->routeIs('admin.quiz-attempts.*')
        || request()->routeIs('admin.assignment-submissions.*')
        || request()->routeIs('admin.reviews.*');

    $inUsersGroup = request()->routeIs('admin.instructors.*')
        || request()->routeIs('admin.students.*')
        || request()->routeIs('admin.campus.applicants.*')
        || request()->routeIs('admin.enrollments.*');

    $inCampusGroup = (
        request()->routeIs('admin.campus.*')
        && ! request()->routeIs('admin.campus.applicants.*')
        && ! request()->routeIs('admin.campus.invoices.*')
        && ! request()->routeIs('admin.campus.installments.*')
        && ! request()->routeIs('admin.campus.analytics.*')
        && ! request()->routeIs('admin.campus.templates.*')
    ) || request()->routeIs('admin.certificates.*') || request()->routeIs('admin.tickets.*');

    $inFinanceGroup = request()->routeIs('admin.campus.invoices.*')
        || request()->routeIs('admin.campus.installments.*')
        || request()->routeIs('admin.payments.*')
        || request()->routeIs('admin.withdrawals.*')
        || request()->routeIs('admin.coupons.*');

    $inReportsGroup = request()->routeIs('admin.reports.*') || request()->routeIs('admin.campus.analytics.*');

    $inSystemSettings = request()->routeIs([
        'admin.settings.*',
        'admin.staff.*',
        'admin.roles.*',
        'admin.frontend.*',
        'admin.custom-pages.*',
        'admin.banners.*',
        'admin.account-deletions.*',
        'admin.languages.*',
        'admin.notifications.*',
        'admin.campus.templates.*',
        'admin.audit-logs.*',
        'admin.backups.*',
        'admin.updater.*',
        'admin.help.*',
    ]);

    $systemSettingsRoute = match (true) {
        $can('manage-settings') => route('admin.settings.index'),
        $canAny(['manage-staff', 'manage-roles']) => route('admin.staff.index'),
        $can('manage-frontend') => route('admin.frontend.home.edit'),
        $can('view-audit-logs') => route('admin.audit-logs.index'),
        $can('manage-backups') => route('admin.backups.index'),
        $can('manage-notifications') => route('admin.notifications.index'),
        default => route('admin.help.index'),
    };
@endphp

<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            @php($siteName = site_name())
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ branding_asset(['primary_logo_path', 'logos.primary'], 'assets/front/img/logo/white-logo.svg') }}"
                    alt="{{ $siteName }} logo" class="img-fluid mt-3" style="max-height: 40px;">
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('admin.dashboard') }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($siteName, 0, 2)) }}</a>
        </div>

        <ul class="sidebar-menu">
            @if ($adminUser)
                <li class="px-3 py-2 hide-sidebar-mini">
                    <div class="rounded p-2 bg-light border small">
                        <div class="font-weight-bold text-primary">{{ $adminUser->primaryRoleLabel() }}</div>
                        <div class="text-muted" style="font-size: 11px;">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ $adminUser->dashboardScopeDescription() }}
                        </div>
                    </div>
                </li>
            @endif

            <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
            </li>

            @if ($canAny(['manage-courses', 'manage-bundles']))
                <li class="dropdown {{ $inCoursesGroup ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                        <i class="fas fa-book"></i> <span>Courses &amp; Learning</span>
                    </a>
                    <ul class="dropdown-menu">
                        @if ($can('manage-courses'))
                            <li class="{{ request()->routeIs('admin.courses.index') && ! request('status') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.courses.index') }}">All Courses</a></li>
                            <li><a class="nav-link" href="{{ route('admin.courses.index', ['status' => 'pending']) }}">Pending Courses</a></li>
                            <li><a class="nav-link" href="{{ route('admin.courses.index', ['status' => 'published']) }}">Published Courses</a></li>
                            <li><a class="nav-link" href="{{ route('admin.courses.index', ['status' => 'draft']) }}">Draft Courses</a></li>
                            <li><a class="nav-link" href="{{ route('admin.courses.index', ['status' => 'archived']) }}">Archived Courses</a></li>
                            <li class="{{ request()->routeIs('admin.course-categories.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.course-categories.index') }}">Categories</a></li>
                            <li class="{{ request()->routeIs('admin.course-languages.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.course-languages.index') }}">Languages</a></li>
                        @endif
                        @if ($can('manage-bundles'))
                            <li class="{{ request()->routeIs('admin.bundles.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.bundles.index') }}">Course Bundles</a></li>
                        @endif
                        @if ($can('manage-courses'))
                            <li class="{{ request()->routeIs('admin.quiz-attempts.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.quiz-attempts.index') }}">Quiz Attempts</a></li>
                            <li class="{{ request()->routeIs('admin.assignment-submissions.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.assignment-submissions.index') }}">Assignments</a></li>
                            <li class="{{ request()->routeIs('admin.reviews.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.reviews.index') }}">All Reviews</a></li>
                            <li class="{{ request()->routeIs('admin.reviews.pending') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.reviews.pending') }}">
                                    Pending Reviews
                                    @php($pendingReviewsCount = \App\Models\Review::where('is_approved', false)->count())
                                    @if ($pendingReviewsCount > 0)
                                        <span class="badge badge-warning ml-1">{{ $pendingReviewsCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($canAny(['approve-instructor', 'reject-instructor', 'instructor:create', 'manage-courses', 'view-users', 'student:create', 'student:update', 'manage-applicants']))
                <li class="dropdown {{ $inUsersGroup ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                        <i class="fas fa-users"></i> <span>Users &amp; Intake</span>
                    </a>
                    <ul class="dropdown-menu">
                        @if ($can('manage-applicants'))
                            <li class="{{ request()->routeIs('admin.campus.applicants.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.applicants.index') }}">F2F Applicants Form</a></li>
                        @endif
                        @if ($canAny(['view-users', 'student:create', 'student:update']))
                            <li class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.students.index') }}">Students</a></li>
                        @endif
                        @if ($canAny(['approve-instructor', 'reject-instructor', 'instructor:create', 'manage-courses']))
                            <li class="{{ request()->routeIs('admin.instructors.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.instructors.index') }}">All Instructors</a></li>
                            <li class="{{ request()->routeIs('admin.instructors.pending') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.instructors.pending') }}">Pending Instructors</a></li>
                        @endif
                        @if ($canAny(['manage-courses', 'manage-applicants']))
                            <li class="{{ request()->routeIs('admin.enrollments.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.enrollments.index') }}">All Enrollments</a></li>
                            <li class="{{ request()->routeIs('admin.enrollments.pending') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.enrollments.pending') }}">Pending Enrollments</a></li>
                            <li class="{{ request()->routeIs('admin.enrollments.completed') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.enrollments.completed') }}">Completed Enrollments</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($canAny(['manage-branches', 'manage-venues', 'manage-countries', 'manage-timetables', 'manage-attendance', 'manage-certificates', 'manage-tickets', 'manage-applicants', 'manage-invoices', 'view-users', 'manage-courses']))
                <li class="dropdown {{ $inCampusGroup ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                        <i class="fas fa-university"></i> <span>Campus Operations</span>
                    </a>
                    <ul class="dropdown-menu">
                        @if ($can('manage-branches'))
                            <li class="{{ request()->routeIs('admin.campus.branches.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.branches.index') }}">Regional Branches</a></li>
                        @endif
                        @if ($can('manage-venues'))
                            <li class="{{ request()->routeIs('admin.campus.venues.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.venues.index') }}">Venues &amp; Labs</a></li>
                        @endif
                        @if ($can('manage-countries'))
                            <li class="{{ request()->routeIs('admin.campus.countries.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.countries.index') }}">Countries Lookup</a></li>
                        @endif
                        @if ($can('manage-timetables'))
                            <li class="{{ request()->routeIs('admin.campus.timetable.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.timetable.index') }}">Timetable &amp; Classes</a></li>
                        @endif
                        @if ($can('manage-attendance'))
                            <li class="{{ request()->routeIs('admin.campus.attendance.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.attendance.index') }}">QR / Manual Attendance</a></li>
                        @endif
                        @if ($can('manage-certificates'))
                            <li class="{{ request()->routeIs('admin.campus.certificates.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.certificates.index') }}">F2F Certificate Clearance</a></li>
                            <li class="{{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.certificates.index') }}">Certificates (Online)</a></li>
                        @endif
                        @if ($can('manage-tickets'))
                            <li class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.tickets.index') }}">
                                    Support Tickets
                                    @php($openTicketCount = \Illuminate\Support\Facades\Schema::hasTable('support_tickets') ? \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count() : 0)
                                    @if ($openTicketCount > 0)
                                        <span class="badge badge-danger ml-1">{{ $openTicketCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                        @if ($canAny(['manage-applicants', 'manage-invoices', 'view-users', 'manage-courses']))
                            <li class="{{ request()->routeIs('admin.campus.search') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.search') }}">Global Search</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($canAny(['manage-invoices', 'manage-installments', 'manage-payments', 'manage-withdrawals', 'manage-coupons']))
                <li class="dropdown {{ $inFinanceGroup ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                        <i class="fas fa-wallet"></i> <span>Finance &amp; Billing</span>
                    </a>
                    <ul class="dropdown-menu">
                        @if ($can('manage-invoices'))
                            <li class="{{ request()->routeIs('admin.campus.invoices.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.invoices.index') }}">Invoices &amp; Control Nos</a></li>
                        @endif
                        @if ($can('manage-installments'))
                            <li class="{{ request()->routeIs('admin.campus.installments.index') || request()->routeIs('admin.campus.installments.create') || request()->routeIs('admin.campus.installments.show') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.installments.index') }}">Payment Schedules</a></li>
                            <li class="{{ request()->routeIs('admin.campus.installments.reminders-list') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.installments.reminders-list') }}">Due Reminders Queue</a></li>
                        @endif
                        @if ($can('manage-payments'))
                            <li class="{{ request()->routeIs('admin.payments.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.payments.index') }}">All Payments</a></li>
                            <li class="{{ request()->routeIs('admin.payments.pending') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.payments.pending') }}">Pending Payments</a></li>
                            <li class="{{ request()->routeIs('admin.payments.offline-pending') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('admin.payments.offline-pending') }}">
                                    Offline Pending
                                    @php($offlinePendingCount = \App\Models\Payment::where('payment_method', 'offline')->where('status', 'pending')->count())
                                    @if ($offlinePendingCount > 0)
                                        <span class="badge badge-warning ml-1">{{ $offlinePendingCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                        @if ($can('manage-withdrawals'))
                            <li class="{{ request()->routeIs('admin.withdrawals.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.withdrawals.index') }}">Payout Requests</a></li>
                        @endif
                        @if ($can('manage-coupons'))
                            <li class="{{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.coupons.index') }}">Coupons</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($canAny(['view-reports', 'view-analytics']))
                <li class="dropdown {{ $inReportsGroup ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                        <i class="fas fa-chart-pie"></i> <span>Reports &amp; Analytics</span>
                    </a>
                    <ul class="dropdown-menu">
                        @if ($can('view-reports'))
                            <li class="{{ request()->routeIs('admin.reports.index') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.reports.index') }}">Executive Overview</a></li>
                            <li class="{{ request()->routeIs('admin.reports.center-courses') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.reports.center-courses') }}">Center Course Report</a></li>
                            <li class="{{ request()->routeIs('admin.reports.applicants') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.reports.applicants') }}">Applicant Report</a></li>
                        @endif
                        @if ($can('view-analytics'))
                            <li class="{{ request()->routeIs('admin.campus.analytics.active-classes') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.analytics.active-classes') }}">Active Classes</a></li>
                            <li class="{{ request()->routeIs('admin.campus.analytics.branches') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.analytics.branches') }}">Branch Analytics</a></li>
                            <li class="{{ request()->routeIs('admin.campus.analytics.discounts') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.analytics.discounts') }}">Discount Waivers</a></li>
                            <li class="{{ request()->routeIs('admin.campus.analytics.tutor-workload') ? 'active' : '' }}"><a class="nav-link" href="{{ route('admin.campus.analytics.tutor-workload') }}">Tutor Workload</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            <li class="{{ $inSystemSettings ? 'active' : '' }}">
                <a href="{{ $systemSettingsRoute }}" class="nav-link">
                    <i class="fas fa-cogs"></i> <span>System Settings</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
