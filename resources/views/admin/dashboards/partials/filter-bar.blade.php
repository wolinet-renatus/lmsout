@php
    $filters = $analytics['filters'] ?? [];
    $scope = $analytics['scope'] ?? [];
    $statusOptions = $analytics['status_options'] ?? ['all' => 'All Statuses'];
    $hasActiveFilters = !empty($filters['search'])
        || ($filters['date_range'] ?? 'all') !== 'all'
        || ($filters['status'] ?? 'all') !== 'all'
        || (($scope['is_multi_branch'] ?? false) && !empty($filters['branch_id']))
        || ((int) ($filters['per_page'] ?? 5) !== 5);
@endphp
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="form-row align-items-end">
            <div class="col-md-{{ ($scope['is_multi_branch'] ?? false) ? '3' : '4' }} col-sm-6 mb-2 mb-md-0">
                <label class="small text-muted mb-1 d-block">Search</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        class="form-control" placeholder="Course, learner, invoice, venue...">
                </div>
            </div>

            @if (($scope['is_multi_branch'] ?? false) && ($scope['branches'] ?? collect())->isNotEmpty())
                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <label class="small text-muted mb-1 d-block">Center</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">All Centers</option>
                        @foreach ($scope['branches'] as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === (int) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2 col-sm-4 mb-2 mb-md-0">
                <label class="small text-muted mb-1 d-block">Period</label>
                <select name="date_range" class="form-control form-control-sm">
                    <option value="all" @selected(($filters['date_range'] ?? 'all') === 'all')>All Time</option>
                    <option value="today" @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="7d" @selected(($filters['date_range'] ?? '') === '7d')>Last 7 Days</option>
                    <option value="30d" @selected(($filters['date_range'] ?? '') === '30d')>Last 30 Days</option>
                    <option value="month" @selected(($filters['date_range'] ?? '') === 'month')>This Month</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4 mb-2 mb-md-0">
                <label class="small text-muted mb-1 d-block">Status</label>
                <select name="status" class="form-control form-control-sm">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-1 col-sm-4 mb-2 mb-md-0">
                <label class="small text-muted mb-1 d-block">Rows</label>
                <select name="per_page" class="form-control form-control-sm">
                    @foreach ([5, 10, 15, 25] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 5) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-12 d-flex align-items-center" style="gap: 6px;">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
