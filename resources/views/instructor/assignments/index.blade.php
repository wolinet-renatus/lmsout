@extends('layouts.instructor')

@section('title', 'Assignments')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Assignments</h2>
        <p class="text-muted small mb-0">Review student submissions, grade work, and track pending feedback.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $summary['total_assignments'] ?? 0 }}</div>
                <div class="text-muted small">Assignments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $summary['total_submissions'] ?? 0 }}</div>
                <div class="text-muted small">Submissions</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $summary['pending_submissions'] ?? 0 }}</div>
                <div class="text-muted small">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $summary['graded_submissions'] ?? 0 }}</div>
                <div class="text-muted small">Graded</div>
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
                id="assignmentSearchInput"
                class="form-control ps-5 pe-5"
                placeholder="Search assignments or courses..."
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
            <select id="assignmentCourseSelect" class="form-select form-select-sm" style="width: auto; max-width: 220px;" aria-label="Filter by course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((int) ($selectedCourseId ?? 0) === (int) $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>

            <select id="assignmentSortSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Sort assignments">
                <option value="latest" @selected(($currentSort ?? 'latest') === 'latest')>Newest First</option>
                <option value="oldest" @selected(($currentSort ?? 'latest') === 'oldest')>Oldest First</option>
                <option value="title_asc" @selected(($currentSort ?? 'latest') === 'title_asc')>Title (A-Z)</option>
                <option value="title_desc" @selected(($currentSort ?? 'latest') === 'title_desc')>Title (Z-A)</option>
                <option value="pending_desc" @selected(($currentSort ?? 'latest') === 'pending_desc')>Most Pending</option>
            </select>

            <div class="student-per-page-selector d-flex align-items-center gap-1">
                <select id="assignmentPerPageSelect" class="form-select form-select-sm student-per-page-select" aria-label="Assignments per page">
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
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'pending' ? 'active' : '' }}" data-status="pending">
            Needs Review <span class="badge bg-light text-dark ms-1" id="countPending">{{ $statusCounts['pending'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'graded' ? 'active' : '' }}" data-status="graded">
            Has Graded <span class="badge bg-light text-dark ms-1" id="countGraded">{{ $statusCounts['graded'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentStatus ?? 'all') === 'no_submissions' ? 'active' : '' }}" data-status="no_submissions">
            No Submissions <span class="badge bg-light text-dark ms-1" id="countNoSubmissions">{{ $statusCounts['no_submissions'] ?? 0 }}</span>
        </button>
    </div>
</div>

<div id="instructorAssignmentsContainer" class="student-courses-container view-list">
    @include('instructor.partials.assignments-list')
</div>

<div id="instructorAssignmentsPaginationBar" class="student-courses-pagination-bar mt-3 {{ $assignments->total() === 0 ? 'd-none' : '' }}">
    <div class="student-pagination-meta text-muted small">
        Showing <span id="paginationFrom">{{ $assignments->firstItem() ?? 0 }}</span> to <span id="paginationTo">{{ $assignments->lastItem() ?? 0 }}</span> of <span id="paginationTotal">{{ $assignments->total() }}</span> assignments
    </div>
    <div id="paginationNavLinks">
        {!! $assignments->links() !!}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'out_instructor_assignments_view';
    const baseUrl = @json(route('instructor.assignments.index'));

    const container = document.getElementById('instructorAssignmentsContainer');
    const searchInput = document.getElementById('assignmentSearchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const courseSelect = document.getElementById('assignmentCourseSelect');
    const sortSelect = document.getElementById('assignmentSortSelect');
    const perPageSelect = document.getElementById('assignmentPerPageSelect');
    const viewListBtn = document.getElementById('viewModeList');
    const viewGridBtn = document.getElementById('viewModeGrid');
    const filterPills = document.querySelectorAll('.student-filter-pill');
    const paginationBar = document.getElementById('instructorAssignmentsPaginationBar');
    const paginationFrom = document.getElementById('paginationFrom');
    const paginationTo = document.getElementById('paginationTo');
    const paginationTotal = document.getElementById('paginationTotal');
    const paginationNavLinks = document.getElementById('paginationNavLinks');

    let currentState = {
        course_id: @json($selectedCourseId ? (string) $selectedCourseId : ''),
        status: @json($currentStatus ?? 'all'),
        search: @json($currentSearch ?? ''),
        sort: @json($currentSort ?? 'latest'),
        per_page: Number(@json((int) ($currentPerPage ?? 3))),
        page: Number(@json((int) $assignments->currentPage()))
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
        if (state.course_id) params.set('course_id', state.course_id);
        if (state.status && state.status !== 'all') params.set('status', state.status);
        if (state.search && state.search.trim() !== '') params.set('search', state.search.trim());
        if (state.sort && state.sort !== 'latest') params.set('sort', state.sort);
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
            countPending: counts.pending ?? 0,
            countGraded: counts.graded ?? 0,
            countNoSubmissions: counts.no_submissions ?? 0
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.textContent = map[id];
        });
    }

    function fetchAssignments(pushHistory = true) {
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
            if (!r.ok) throw new Error('Failed to load assignments');
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
            fetchAssignments(true);
        }, 300);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            toggleClearButton();
            currentState.search = '';
            currentState.page = 1;
            fetchAssignments(true);
            searchInput.focus();
        });
    }

    courseSelect.addEventListener('change', function () {
        currentState.course_id = courseSelect.value || '';
        currentState.page = 1;
        fetchAssignments(true);
    });

    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            const nextStatus = pill.dataset.status || 'all';
            if (currentState.status === nextStatus) return;
            currentState.status = nextStatus;
            currentState.page = 1;
            updateFilterPillsUI(nextStatus);
            fetchAssignments(true);
        });
    });

    sortSelect.addEventListener('change', function () {
        currentState.sort = sortSelect.value || 'latest';
        currentState.page = 1;
        fetchAssignments(true);
    });

    perPageSelect.addEventListener('change', function () {
        currentState.per_page = Math.max(3, Number(perPageSelect.value) || 3);
        currentState.page = 1;
        fetchAssignments(true);
    });

    paginationNavLinks.addEventListener('click', function (e) {
        const link = e.target.closest('a.page-link');
        if (!link || !link.href) return;
        e.preventDefault();
        try {
            const targetUrl = new URL(link.href);
            currentState.page = Number(targetUrl.searchParams.get('page') || 1);
            fetchAssignments(true);
        } catch (err) {}
    });

    container.addEventListener('click', function (e) {
        const resetBtn = e.target.closest('#emptyStateResetBtn');
        if (!resetBtn) return;
        currentState.course_id = '';
        currentState.status = 'all';
        currentState.search = '';
        currentState.sort = 'latest';
        currentState.page = 1;
        courseSelect.value = '';
        searchInput.value = '';
        sortSelect.value = 'latest';
        toggleClearButton();
        updateFilterPillsUI('all');
        fetchAssignments(true);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state) {
            currentState = { ...e.state };
        } else {
            const params = new URLSearchParams(window.location.search);
            currentState = {
                course_id: params.get('course_id') || '',
                status: params.get('status') || 'all',
                search: params.get('search') || '',
                sort: params.get('sort') || 'latest',
                per_page: Math.max(3, Number(params.get('per_page') || 3)),
                page: Math.max(1, Number(params.get('page') || 1))
            };
        }
        courseSelect.value = currentState.course_id || '';
        searchInput.value = currentState.search || '';
        sortSelect.value = currentState.sort || 'latest';
        perPageSelect.value = String(currentState.per_page || 3);
        toggleClearButton();
        updateFilterPillsUI(currentState.status || 'all');
        fetchAssignments(false);
    });
});
</script>
@endpush
