@extends('layouts.instructor')

@section('title', 'Enrolled Students')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Enrolled Students</h2>
        <p class="text-muted small mb-0">Track student enrollments, course completion, and learning progress.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['total_students'] ?? 0 }}</div>
                <div class="text-muted small">Total Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-book-open-reader"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['total_enrollments'] ?? 0 }}</div>
                <div class="text-muted small">Enrollments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['avg_progress'] ?? 0 }}%</div>
                <div class="text-muted small">Avg Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ currency_format($stats['total_revenue'] ?? 0) }}</div>
                <div class="text-muted small">Total Earnings</div>
            </div>
        </div>
    </div>
</div>

<div class="student-courses-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 w-100">
        <div class="student-courses-search position-relative flex-grow-1" style="min-width: 240px; max-width: 360px;">
            <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input
                type="text"
                id="studentSearchInput"
                class="form-control ps-5 pe-5"
                placeholder="Search students by name or email..."
                value="{{ $currentSearch ?? '' }}"
                autocomplete="off"
            >
            <button
                type="button"
                id="searchClearBtn"
                class="student-search-clear {{ empty($currentSearch) ? 'd-none' : '' }}"
                aria-label="Clear search"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span id="searchSpinner" class="student-search-spinner d-none" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            </span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <select id="studentCourseSelect" class="form-select form-select-sm" style="width: auto; max-width: 220px;" aria-label="Filter by course">
                <option value="all" @selected(($selectedCourse ?? 'all') === 'all')>All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) ($selectedCourse ?? 'all') === (string) $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>

            <select id="studentSortSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Sort students">
                <option value="newest" @selected(($currentSort ?? 'newest') === 'newest')>Newest Enrolled</option>
                <option value="oldest" @selected(($currentSort ?? 'newest') === 'oldest')>Oldest Enrolled</option>
                <option value="name_asc" @selected(($currentSort ?? 'newest') === 'name_asc')>Name (A-Z)</option>
                <option value="progress_desc" @selected(($currentSort ?? 'newest') === 'progress_desc')>Highest Progress</option>
            </select>

            <div class="student-per-page-selector d-flex align-items-center gap-1">
                <select id="studentPerPageSelect" class="form-select form-select-sm student-per-page-select" aria-label="Students per page">
                    <option value="3" @selected((int) ($currentPerPage ?? 3) === 3)>3 / page</option>
                    <option value="6" @selected((int) ($currentPerPage ?? 3) === 6)>6 / page</option>
                    <option value="12" @selected((int) ($currentPerPage ?? 3) === 12)>12 / page</option>
                </select>
            </div>

            <div class="student-view-toggle" role="group" aria-label="View mode">
                <button type="button" id="viewModeList" class="student-view-btn active" data-view="list" title="List view">
                    <i class="fa-solid fa-list"></i>
                </button>
                <button type="button" id="viewModeGrid" class="student-view-btn" data-view="grid" title="Grid view">
                    <i class="fa-solid fa-grip"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="student-filter-pills mt-2">
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'all' ? 'active' : '' }}" data-status="all">
            All <span class="badge bg-light text-dark ms-1" id="countAll">{{ $statusCounts['all'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'in_progress' ? 'active' : '' }}" data-status="in_progress">
            In Progress <span class="badge bg-light text-dark ms-1" id="countInProgress">{{ $statusCounts['in_progress'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'completed' ? 'active' : '' }}" data-status="completed">
            Completed <span class="badge bg-light text-dark ms-1" id="countCompleted">{{ $statusCounts['completed'] ?? 0 }}</span>
        </button>
    </div>
</div>

<div id="instructorStudentsContainer" class="student-courses-container view-list">
    @include('instructor.partials.students-list')
</div>

<div id="instructorStudentsPaginationBar" class="student-courses-pagination-bar mt-3 {{ $students->total() === 0 ? 'd-none' : '' }}">
    <div class="student-pagination-meta text-muted small">
        Showing <span id="paginationFrom">{{ $students->firstItem() ?? 0 }}</span> to <span id="paginationTo">{{ $students->lastItem() ?? 0 }}</span> of <span id="paginationTotal">{{ $students->total() }}</span> students
    </div>
    <div id="paginationNavLinks">
        {!! $students->links() !!}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'out_instructor_students_view';
    const baseUrl = @json(route('instructor.students'));

    const container = document.getElementById('instructorStudentsContainer');
    const searchInput = document.getElementById('studentSearchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const courseSelect = document.getElementById('studentCourseSelect');
    const sortSelect = document.getElementById('studentSortSelect');
    const perPageSelect = document.getElementById('studentPerPageSelect');
    const viewListBtn = document.getElementById('viewModeList');
    const viewGridBtn = document.getElementById('viewModeGrid');
    const filterPills = document.querySelectorAll('.student-filter-pill');
    const paginationBar = document.getElementById('instructorStudentsPaginationBar');
    const paginationFrom = document.getElementById('paginationFrom');
    const paginationTo = document.getElementById('paginationTo');
    const paginationTotal = document.getElementById('paginationTotal');
    const paginationNavLinks = document.getElementById('paginationNavLinks');

    let currentState = {
        course: @json((string) ($selectedCourse ?? 'all')),
        status: @json($currentStatus ?? 'all'),
        search: @json($currentSearch ?? ''),
        sort: @json($currentSort ?? 'newest'),
        per_page: Number(@json((int) ($currentPerPage ?? 3))),
        page: Number(@json((int) $students->currentPage()))
    };

    let debounceTimer = null;
    let activeController = null;

    function applyViewMode(mode) {
        const normalized = mode === 'grid' ? 'grid' : 'list';
        container.classList.remove('view-list', 'view-grid');
        container.classList.add('view-' + normalized);
        viewListBtn.classList.toggle('active', normalized === 'list');
        viewGridBtn.classList.toggle('active', normalized === 'grid');
        try { localStorage.setItem(STORAGE_KEY, normalized); } catch (e) {}
    }

    try { applyViewMode(localStorage.getItem(STORAGE_KEY) || 'list'); } catch (e) { applyViewMode('list'); }
    viewListBtn.addEventListener('click', function () { applyViewMode('list'); });
    viewGridBtn.addEventListener('click', function () { applyViewMode('grid'); });

    function toggleClearButton() {
        if (clearBtn) clearBtn.classList.toggle('d-none', searchInput.value.trim() === '');
    }

    function buildQueryUrl(state) {
        const params = new URLSearchParams();
        if (state.course && state.course !== 'all') params.set('course', state.course);
        if (state.status && state.status !== 'all') params.set('status', state.status);
        if (state.search && state.search.trim() !== '') params.set('search', state.search.trim());
        if (state.sort && state.sort !== 'newest') params.set('sort', state.sort);
        if (state.per_page) params.set('per_page', String(state.per_page));
        if (state.page && state.page > 1) params.set('page', String(state.page));
        const qs = params.toString();
        return qs ? (baseUrl + '?' + qs) : baseUrl;
    }

    function updateFilterPillsUI(status) {
        filterPills.forEach(function (pill) {
            pill.classList.toggle('active', pill.dataset.status === status);
        });
    }

    function updateStatusCounts(counts) {
        if (!counts) return;
        const map = {
            countAll: counts.all ?? 0,
            countInProgress: counts.in_progress ?? 0,
            countCompleted: counts.completed ?? 0
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.textContent = map[id];
        });
    }

    function fetchStudents(pushHistory = true) {
        const url = buildQueryUrl(currentState);
        if (activeController) activeController.abort();
        activeController = new AbortController();

        container.classList.add('is-loading');
        if (searchSpinner) searchSpinner.classList.remove('d-none');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            signal: activeController.signal
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Failed to load students');
            return r.json();
        })
        .then(function (data) {
            container.innerHTML = data.html || '';
            updateStatusCounts(data.statusCounts);

            if (Number(data.total || 0) === 0) {
                paginationBar.classList.add('d-none');
            } else {
                paginationBar.classList.remove('d-none');
                paginationFrom.textContent = data.from ?? 0;
                paginationTo.textContent = data.to ?? 0;
                paginationTotal.textContent = data.total ?? 0;
            }
            paginationNavLinks.innerHTML = data.pagination || '';
            if (pushHistory) window.history.pushState({ ...currentState }, '', url);
        })
        .catch(function (err) {
            if (err.name !== 'AbortError') console.error(err);
        })
        .finally(function () {
            container.classList.remove('is-loading');
            if (searchSpinner) searchSpinner.classList.add('d-none');
        });
    }

    searchInput.addEventListener('input', function () {
        toggleClearButton();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            currentState.search = searchInput.value.trim();
            currentState.page = 1;
            fetchStudents(true);
        }, 300);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            toggleClearButton();
            currentState.search = '';
            currentState.page = 1;
            fetchStudents(true);
            searchInput.focus();
        });
    }

    courseSelect.addEventListener('change', function () {
        currentState.course = courseSelect.value || 'all';
        currentState.page = 1;
        fetchStudents(true);
    });

    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            const nextStatus = pill.dataset.status || 'all';
            if (currentState.status === nextStatus) return;
            currentState.status = nextStatus;
            currentState.page = 1;
            updateFilterPillsUI(nextStatus);
            fetchStudents(true);
        });
    });

    sortSelect.addEventListener('change', function () {
        currentState.sort = sortSelect.value || 'newest';
        currentState.page = 1;
        fetchStudents(true);
    });

    perPageSelect.addEventListener('change', function () {
        currentState.per_page = Math.max(3, Number(perPageSelect.value) || 3);
        currentState.page = 1;
        fetchStudents(true);
    });

    paginationNavLinks.addEventListener('click', function (e) {
        const link = e.target.closest('a.page-link');
        if (!link || !link.href) return;
        e.preventDefault();
        try {
            const targetUrl = new URL(link.href);
            currentState.page = Number(targetUrl.searchParams.get('page') || 1);
            fetchStudents(true);
        } catch (err) {}
    });

    container.addEventListener('click', function (e) {
        const resetBtn = e.target.closest('#emptyStateResetBtn');
        if (!resetBtn) return;
        currentState.course = 'all';
        currentState.status = 'all';
        currentState.search = '';
        currentState.sort = 'newest';
        currentState.page = 1;
        courseSelect.value = 'all';
        searchInput.value = '';
        sortSelect.value = 'newest';
        toggleClearButton();
        updateFilterPillsUI('all');
        fetchStudents(true);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state) {
            currentState = { ...e.state };
        } else {
            const params = new URLSearchParams(window.location.search);
            currentState = {
                course: params.get('course') || 'all',
                status: params.get('status') || 'all',
                search: params.get('search') || '',
                sort: params.get('sort') || 'newest',
                per_page: Math.max(3, Number(params.get('per_page') || 3)),
                page: Math.max(1, Number(params.get('page') || 1))
            };
        }
        courseSelect.value = currentState.course || 'all';
        searchInput.value = currentState.search || '';
        sortSelect.value = currentState.sort || 'newest';
        perPageSelect.value = String(currentState.per_page || 3);
        toggleClearButton();
        updateFilterPillsUI(currentState.status || 'all');
        fetchStudents(false);
    });
});
</script>
@endpush
