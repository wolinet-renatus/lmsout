@php
    $dashUser = auth()->user();
    $can = fn (string $perm): bool => $dashUser ? $dashUser->hasAdminPermission($perm) : false;
    $scope = $analytics['scope'] ?? [];
    $campus = $analytics['campus'] ?? ($scope['campus'] ?? []);
@endphp
<div class="card mb-3 border-left-primary">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <span class="badge badge-primary mr-2">{{ $scope['role_label'] ?? ($dashUser?->primaryRoleLabel() ?? 'Staff') }}</span>
            <span class="text-muted small">
                <i class="fas fa-map-marker-alt mr-1 text-primary"></i>Center:
                <strong>{{ $scope['scope_label'] ?? ($dashUser?->dashboardScopeDescription() ?? 'Assigned Center') }}</strong>
            </span>
        </div>
        <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0" style="gap: 8px;">
            @if ($can('manage-applicants'))
                <a href="{{ route('admin.campus.applicants.index') }}" class="badge badge-light p-2">
                    <i class="fas fa-user-plus text-primary mr-1"></i> Applicants: <strong>{{ $campus['f2f_applicants'] ?? 0 }}</strong>
                </a>
            @endif
            @if ($can('view-analytics'))
                <a href="{{ route('admin.campus.analytics.active-classes') }}" class="badge badge-light p-2">
                    <i class="fas fa-chalkboard-teacher text-info mr-1"></i> Active Classes: <strong>{{ $campus['active_classes'] ?? 0 }}</strong>
                </a>
            @endif
            @if ($can('manage-certificates'))
                <a href="{{ route('admin.campus.certificates.index', ['status' => 'ready']) }}" class="badge badge-light p-2">
                    <i class="fas fa-certificate text-success mr-1"></i> Ready Certs: <strong>{{ $campus['ready_certificates'] ?? 0 }}</strong>
                </a>
            @endif
            @if ($can('manage-installments'))
                <a href="{{ route('admin.campus.installments.index', ['filter' => 'overdue']) }}" class="badge badge-light p-2">
                    <i class="fas fa-clock text-danger mr-1"></i> Overdue Schedules: <strong>{{ $campus['overdue_installments'] ?? 0 }}</strong>
                </a>
            @endif
            @if ($can('view-reports'))
                <a href="{{ route('admin.reports.center-courses') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-chart-bar mr-1"></i> Center Report
                </a>
            @endif
        </div>
    </div>
</div>
@if (($scope['profile'] ?? 'custom') !== 'custom')
    @include('admin.dashboards.partials.filter-bar')
@endif
