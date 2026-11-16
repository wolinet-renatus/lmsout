@extends('layouts.admin')

@section('title', 'All Students')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Students</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-3">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-user-graduate"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Matching Students</h4></div>
                    <div class="card-body" id="kpi-matching">{{ number_format($summary['total_matching'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-user-check"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Active Accounts</h4></div>
                    <div class="card-body" id="kpi-active">{{ number_format($summary['active_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-book-open"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>With Enrollments</h4></div>
                    <div class="card-body" id="kpi-enrolled">{{ number_format($summary['enrolled_count'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-wallet"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Tuition Collected</h4></div>
                    <div class="card-body" id="kpi-spent" style="font-size: 16px;">{{ $summary['total_spent_formatted'] ?? currency_format(0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <h4 class="mb-0"><i class="fas fa-users mr-2 text-primary"></i>Students Directory</h4>
                        <span class="badge badge-light border" id="students-live-indicator">
                            <i class="fas fa-circle text-success mr-1" style="font-size: 8px;"></i> Live Search Ready
                        </span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
                        <div class="btn-group" role="group" aria-label="Export Students">
                            <a href="{{ route('admin.students.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                                id="export-excel-btn" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </a>
                            <a href="{{ route('admin.students.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                                id="export-csv-btn" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-file-csv mr-1"></i> CSV
                            </a>
                            <a href="{{ route('admin.students.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                                id="export-pdf-btn" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </a>
                        </div>
                        <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus mr-1"></i> Add Student
                        </a>
                    </div>
                </div>

                <div class="card-body border-bottom bg-light py-3">
                    <form id="students-filter-form" method="GET" action="{{ route('admin.students.index') }}">
                        <div class="form-row align-items-end">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="student-search-input" class="small text-muted mb-1">Real-Time Search</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="student-search-input" class="form-control" name="search"
                                        placeholder="Name, email, phone, reg #, course..."
                                        value="{{ $filters['search'] ?? '' }}" autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="button" id="clear-search-btn" class="btn btn-outline-secondary"
                                            title="Clear search" style="{{ empty($filters['search']) ? 'display:none;' : '' }}">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if (auth()->user()->isMultiBranchAdmin())
                                <div class="col-lg-2 col-md-4 mb-2">
                                    <label for="filter-branch" class="small text-muted mb-1">Branch</label>
                                    <select id="filter-branch" name="branch_id" class="form-control form-control-sm js-student-filter">
                                        <option value="">All Branches</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ (string) ($effectiveBranch ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-course" class="small text-muted mb-1">Enrolled Course</label>
                                <select id="filter-course" name="course_id" class="form-control form-control-sm js-student-filter">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" {{ (string) ($filters['course_id'] ?? '') === (string) $course->id ? 'selected' : '' }}>
                                            {{ \Illuminate\Support\Str::limit($course->title, 36) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-enrollment" class="small text-muted mb-1">Enrollment Status</label>
                                <select id="filter-enrollment" name="enrollment_status" class="form-control form-control-sm js-student-filter">
                                    <option value="">All Students</option>
                                    <option value="enrolled" {{ ($filters['enrollment_status'] ?? '') === 'enrolled' ? 'selected' : '' }}>Enrolled Only</option>
                                    <option value="active" {{ ($filters['enrollment_status'] ?? '') === 'active' ? 'selected' : '' }}>Active Courses</option>
                                    <option value="completed" {{ ($filters['enrollment_status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed Courses</option>
                                    <option value="unenrolled" {{ ($filters['enrollment_status'] ?? '') === 'unenrolled' ? 'selected' : '' }}>Not Enrolled Yet</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-account" class="small text-muted mb-1">Account Status</label>
                                <select id="filter-account" name="account_status" class="form-control form-control-sm js-student-filter">
                                    <option value="">All Accounts</option>
                                    <option value="active" {{ ($filters['account_status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="pending" {{ ($filters['account_status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="blocked" {{ ($filters['account_status'] ?? '') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                            </div>

                            <div class="col-lg-1 col-md-4 mb-2">
                                <label for="filter-per-page" class="small text-muted mb-1">Per Page</label>
                                <select id="filter-per-page" name="per_page" class="form-control form-control-sm js-student-filter">
                                    @foreach ($perPageOptions as $option)
                                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row align-items-end mt-1">
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-sort" class="small text-muted mb-1">Sort Order</label>
                                <select id="filter-sort" name="sort" class="form-control form-control-sm js-student-filter">
                                    <option value="latest" {{ ($filters['sort'] ?? 'latest') === 'latest' ? 'selected' : '' }}>Newest Joined</option>
                                    <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest Joined</option>
                                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name (A &ndash; Z)</option>
                                    <option value="most_enrolled" {{ ($filters['sort'] ?? '') === 'most_enrolled' ? 'selected' : '' }}>Most Enrolled</option>
                                    <option value="highest_spent" {{ ($filters['sort'] ?? '') === 'highest_spent' ? 'selected' : '' }}>Highest Tuition Spent</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-joined-from" class="small text-muted mb-1">Joined From</label>
                                <input type="date" id="filter-joined-from" name="joined_from"
                                    value="{{ $filters['joined_from'] ?? '' }}"
                                    class="form-control form-control-sm js-student-filter">
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="filter-joined-to" class="small text-muted mb-1">Joined To</label>
                                <input type="date" id="filter-joined-to" name="joined_to"
                                    value="{{ $filters['joined_to'] ?? '' }}"
                                    class="form-control form-control-sm js-student-filter">
                            </div>
                            <div class="col-lg-6 col-md-12 mb-2 text-md-right">
                                <button type="submit" class="btn btn-sm btn-primary mr-1">
                                    <i class="fas fa-filter mr-1"></i> Apply Filters
                                </button>
                                <a href="{{ route('admin.students.index') }}" id="reset-filters-btn" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-undo mr-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body" id="students-table-container">
                    @include('admin.students._table')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@php
    $studentsIndexUrl = route('admin.students.index');
    $studentsExportUrl = route('admin.students.export');
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('students-filter-form');
    const searchInput = document.getElementById('student-search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const tableContainer = document.getElementById('students-table-container');
    const indicator = document.getElementById('students-live-indicator');
    const excelBtn = document.getElementById('export-excel-btn');
    const csvBtn = document.getElementById('export-csv-btn');
    const pdfBtn = document.getElementById('export-pdf-btn');

    const indexUrl = @json($studentsIndexUrl);
    const exportUrl = @json($studentsExportUrl);

    if (!form || !tableContainer) return;

    let debounceTimer = null;
    let activeController = null;

    function buildParams(extraParams) {
        const params = new URLSearchParams();
        const formData = new FormData(form);
        for (const [key, val] of formData.entries()) {
            const trimmed = String(val).trim();
            if (trimmed !== '') {
                params.set(key, trimmed);
            }
        }
        if (extraParams) {
            for (const [k, v] of Object.entries(extraParams)) {
                if (v !== null && v !== undefined && String(v) !== '') {
                    params.set(k, String(v));
                }
            }
        }
        return params;
    }

    function updateExportLinks(params) {
        const exportPairs = [
            [excelBtn, 'excel'],
            [csvBtn, 'csv'],
            [pdfBtn, 'pdf'],
        ];
        exportPairs.forEach(([el, fmt]) => {
            if (!el) return;
            const p = new URLSearchParams(params.toString());
            p.delete('page');
            p.delete('ajax');
            p.set('format', fmt);
            el.setAttribute('href', exportUrl + '?' + p.toString());
        });
    }

    function setLoadingState(loading) {
        if (!indicator) return;
        if (loading) {
            indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-primary mr-1"></i> Syncing...';
        } else {
            indicator.innerHTML = '<i class="fas fa-circle text-success mr-1" style="font-size: 8px;"></i> Live Search Ready';
        }
    }

    async function fetchStudents(pageOverride) {
        const params = buildParams(pageOverride ? { page: pageOverride } : null);
        updateExportLinks(params);

        const browserQuery = params.toString();
        const cleanUrl = browserQuery ? `${indexUrl}?${browserQuery}` : indexUrl;
        if (window.history && typeof window.history.replaceState === 'function') {
            window.history.replaceState(null, '', cleanUrl);
        }

        const fetchParams = new URLSearchParams(params.toString());
        fetchParams.set('ajax', '1');

        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();
        setLoadingState(true);

        try {
            const response = await fetch(`${indexUrl}?${fetchParams.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeController.signal,
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success) return;

            tableContainer.innerHTML = data.html;
            if (data.summary) {
                const kpiMatching = document.getElementById('kpi-matching');
                const kpiActive = document.getElementById('kpi-active');
                const kpiEnrolled = document.getElementById('kpi-enrolled');
                const kpiSpent = document.getElementById('kpi-spent');
                if (kpiMatching) kpiMatching.textContent = Number(data.summary.total_matching || 0).toLocaleString();
                if (kpiActive) kpiActive.textContent = Number(data.summary.active_count || 0).toLocaleString();
                if (kpiEnrolled) kpiEnrolled.textContent = Number(data.summary.enrolled_count || 0).toLocaleString();
                if (kpiSpent && data.summary.total_spent_formatted !== undefined) {
                    kpiSpent.textContent = data.summary.total_spent_formatted;
                }
            }
        } catch (err) {
            if (err && err.name === 'AbortError') return;
        } finally {
            setLoadingState(false);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (clearSearchBtn) {
                clearSearchBtn.style.display = this.value.trim() ? '' : 'none';
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchStudents(1), 250);
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            this.style.display = 'none';
            clearTimeout(debounceTimer);
            fetchStudents(1);
        });
    }

    form.querySelectorAll('.js-student-filter').forEach((el) => {
        el.addEventListener('change', () => fetchStudents(1));
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounceTimer);
        fetchStudents(1);
    });

    tableContainer.addEventListener('click', function (e) {
        const link = e.target.closest('.pagination a.page-link');
        if (!link || e.metaKey || e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        try {
            const urlObj = new URL(link.href, window.location.origin);
            const targetPage = urlObj.searchParams.get('page') || 1;
            fetchStudents(targetPage);
        } catch (err) {
            window.location.href = link.href;
        }
    });
});
</script>
@endpush
