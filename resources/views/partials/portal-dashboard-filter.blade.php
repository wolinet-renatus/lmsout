@php
    $filters = $filters ?? [];
    $statusOptions = $statusOptions ?? ['all' => 'All Statuses'];
    $hasActiveFilters = ! empty($filters['search'])
        || ($filters['date_range'] ?? 'all') !== 'all'
        || ($filters['status'] ?? 'all') !== 'all'
        || ((int) ($filters['per_page'] ?? 5) !== 5);
@endphp
<div class="out-card mb-4">
    <div class="out-card-body p-3">
        <form method="GET" action="{{ $actionRoute }}" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        class="form-control" placeholder="{{ $searchPlaceholder ?? 'Search courses, activities, status...' }}">
                </div>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small text-muted mb-1">Period</label>
                <select name="date_range" class="form-select form-select-sm">
                    <option value="all" @selected(($filters['date_range'] ?? 'all') === 'all')>All Time</option>
                    <option value="today" @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="7d" @selected(($filters['date_range'] ?? '') === '7d')>Last 7 Days</option>
                    <option value="30d" @selected(($filters['date_range'] ?? '') === '30d')>Last 30 Days</option>
                    <option value="month" @selected(($filters['date_range'] ?? '') === 'month')>This Month</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-4 col-5">
                <label class="form-label small text-muted mb-1">Rows</label>
                <select name="per_page" class="form-select form-select-sm">
                    @foreach ([5, 10, 15, 25] as $size)
                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 5) === $size)>{{ $size }} / card</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-8 col-7 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-pill fw-semibold">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if ($hasActiveFilters)
                    <a href="{{ $actionRoute }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
