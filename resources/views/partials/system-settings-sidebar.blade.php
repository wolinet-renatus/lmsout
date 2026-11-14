@php
    $sysUser = auth()->user();
    $can = fn (string $perm): bool => $sysUser ? $sysUser->hasAdminPermission($perm) : false;
    $canAny = function (array $perms) use ($can): bool {
        foreach ($perms as $p) {
            if ($can($p)) {
                return true;
            }
        }
        return false;
    };
@endphp

<div class="card shadow-sm">
    <div class="card-header py-3">
        <h4 class="mb-0"><i class="fas fa-cogs mr-1 text-primary"></i> System Settings</h4>
    </div>
    <div class="card-body p-2">
        <ul class="nav nav-pills flex-column small">
            @if ($can('manage-settings'))
                <li class="nav-item mb-1">
                    <a href="{{ route('admin.settings.index') }}"
                       class="nav-link {{ request()->routeIs('admin.settings.index') || request()->routeIs('admin.settings.platform') || request()->routeIs('admin.settings.branding') || request()->routeIs('admin.settings.course') || request()->routeIs('admin.settings.authentication*') || request()->routeIs('admin.settings.gateways.*') || request()->routeIs('admin.settings.tax*') || request()->routeIs('admin.settings.revenue*') || request()->routeIs('admin.settings.withdrawals*') || request()->routeIs('admin.settings.email*') || request()->routeIs('admin.settings.smtp*') || request()->routeIs('admin.settings.ai*') || request()->routeIs('admin.settings.live_class*') || request()->routeIs('admin.settings.chat*') || request()->routeIs('admin.settings.recaptcha*') || request()->routeIs('admin.settings.support*') || request()->routeIs('admin.settings.custom_scripts*') || request()->routeIs('admin.settings.contact*') || request()->routeIs('admin.settings.cron_setup*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h mr-2"></i> Platform Configuration
                    </a>
                </li>
            @endif

            @if ($canAny(['manage-staff', 'manage-roles', 'manage-settings']))
                <li class="nav-header px-3 pt-2 pb-1 text-uppercase font-weight-bold text-muted" style="font-size: 10px;">
                    Access &amp; Accounts
                </li>
                @if ($canAny(['manage-staff', 'manage-roles']))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.staff.index') }}"
                           class="nav-link {{ request()->routeIs('admin.staff.*') || request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <i class="fas fa-user-shield mr-2"></i> Staff &amp; Role Management
                        </a>
                    </li>
                @endif
                @if ($can('manage-settings'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.account-deletions.index') }}"
                           class="nav-link {{ request()->routeIs('admin.account-deletions.*') || request()->routeIs('admin.settings.account_deletion*') ? 'active' : '' }}">
                            <i class="fas fa-user-slash mr-2"></i> Account Deletions
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.languages.index') }}"
                           class="nav-link {{ request()->routeIs('admin.languages.*') ? 'active' : '' }}">
                            <i class="fas fa-language mr-2"></i> Language Manager
                        </a>
                    </li>
                @endif
            @endif

            @if ($canAny(['manage-frontend', 'manage-notifications', 'manage-settings']))
                <li class="nav-header px-3 pt-2 pb-1 text-uppercase font-weight-bold text-muted" style="font-size: 10px;">
                    Frontend &amp; Messaging
                </li>
                @if ($can('manage-frontend'))
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('admin.frontend.*') || request()->routeIs('admin.custom-pages.*') || request()->routeIs('admin.banners.*') ? 'active' : '' }}"
                           data-toggle="collapse" href="#sysFrontendCollapse" role="button"
                           aria-expanded="{{ request()->routeIs('admin.frontend.*') || request()->routeIs('admin.custom-pages.*') || request()->routeIs('admin.banners.*') ? 'true' : 'false' }}">
                            <i class="fas fa-paint-brush mr-2"></i> Frontend &amp; CMS
                            <i class="fas fa-chevron-down float-right mt-1" style="font-size: 10px;"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('admin.frontend.*') || request()->routeIs('admin.custom-pages.*') || request()->routeIs('admin.banners.*') ? 'show' : '' }}" id="sysFrontendCollapse">
                            <ul class="nav flex-column pl-3 py-1" style="font-size: 12px;">
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.home.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.home.edit') }}">Homepage Content</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.theme.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.theme.edit') }}">Homepage Theme</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.menus.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.menus.index') }}">Navigation Menus</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.about.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.about.edit') }}">About Page</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.contact.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.contact.edit') }}">Contact Page</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.faq.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.faq.edit') }}">FAQ Content</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.marquee.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.marquee.edit') }}">Marquee Items</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.seo.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.seo.edit') }}">SEO Metadata</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.custom-pages.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.custom-pages.index') }}">Custom Pages</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.banners.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.banners.index') }}">App Banners</a></li>
                                <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('admin.frontend.settings.*') ? 'font-weight-bold text-primary' : 'text-muted' }}" href="{{ route('admin.frontend.settings.edit') }}">Footer &amp; Contact</a></li>
                            </ul>
                        </div>
                    </li>
                @endif
                @if ($can('manage-notifications'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.notifications.index') }}"
                           class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                            <i class="fas fa-bell mr-2"></i> Push Notifications
                        </a>
                    </li>
                @endif
                @if ($can('manage-settings'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.settings.newsletter.index') }}"
                           class="nav-link {{ request()->routeIs('admin.settings.newsletter.*') ? 'active' : '' }}">
                            <i class="fas fa-envelope mr-2"></i> Newsletter
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.campus.templates.index') }}"
                           class="nav-link {{ request()->routeIs('admin.campus.templates.*') ? 'active' : '' }}">
                            <i class="fas fa-file-alt mr-2"></i> Reminder Templates
                        </a>
                    </li>
                @endif
            @endif

            @if ($canAny(['manage-settings', 'manage-backups', 'view-audit-logs']))
                <li class="nav-header px-3 pt-2 pb-1 text-uppercase font-weight-bold text-muted" style="font-size: 10px;">
                    Maintenance &amp; Security
                </li>
                @if ($can('view-audit-logs'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.audit-logs.index') }}"
                           class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                            <i class="fas fa-history mr-2"></i> Audit &amp; Security Logs
                        </a>
                    </li>
                @endif
                @if ($can('manage-backups'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.backups.index') }}"
                           class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                            <i class="fas fa-database mr-2"></i> Backup Management
                        </a>
                    </li>
                @endif
                @if ($can('manage-settings'))
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.settings.system.status') }}"
                           class="nav-link {{ request()->routeIs('admin.settings.system.status') ? 'active' : '' }}">
                            <i class="fas fa-heartbeat mr-2"></i> System Status
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.settings.system.cache') }}"
                           class="nav-link {{ request()->routeIs('admin.settings.system.cache') ? 'active' : '' }}">
                            <i class="fas fa-broom mr-2"></i> Cache Management
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.settings.system.logs') }}"
                           class="nav-link {{ request()->routeIs('admin.settings.system.logs') ? 'active' : '' }}">
                            <i class="fas fa-bug mr-2"></i> Error Logs
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="{{ route('admin.updater.index') }}"
                           class="nav-link {{ request()->routeIs('admin.updater.*') ? 'active' : '' }}">
                            <i class="fas fa-sync-alt mr-2"></i> System Updater
                        </a>
                    </li>
                @endif
            @endif

            <li class="nav-header px-3 pt-2 pb-1 text-uppercase font-weight-bold text-muted" style="font-size: 10px;">
                Documentation
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.help.index') }}"
                   class="nav-link {{ request()->routeIs('admin.help.*') ? 'active' : '' }}">
                    <i class="fas fa-question-circle mr-2"></i> Online Help
                </a>
            </li>
        </ul>
    </div>
</div>
