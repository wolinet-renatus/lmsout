@extends('layouts.instructor')

@section('title', 'Course Forums & Discussions')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Course Forums</h2>
        <p class="text-muted small mb-0">Answer student questions, pin important threads, and post announcements.</p>
    </div>
    <div>
        <button class="btn btn-primary btn-sm px-3" type="button" data-bs-toggle="collapse" data-bs-target="#newAnnouncementCollapse" aria-expanded="false" aria-controls="newAnnouncementCollapse">
            <i class="fa-solid fa-bullhorn me-1"></i>Post Announcement
        </button>
    </div>
</div>

<!-- Post Announcement Collapse Form -->
<div class="collapse mb-4" id="newAnnouncementCollapse">
    <div class="bg-white border rounded-3 p-3 p-md-4">
        <h6 class="fw-bold mb-3">Create a Forum Thread / Announcement</h6>
        <form id="new-announcement-form" method="POST" action="">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="announcement-course-id" class="form-label small fw-semibold">Select Course <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="announcement-course-id" required>
                        <option value="" disabled selected>-- Select Course --</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="announcement-title" class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="announcement-title" name="title" required placeholder="Announcement title">
                </div>
                <div class="col-12">
                    <label for="announcement-content" class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-sm" id="announcement-content" name="content" rows="3" required placeholder="Write the announcement description..."></textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_announcement" id="check_is_announcement" value="1" checked>
                        <label class="form-check-label small" for="check_is_announcement">Flag as Announcement</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="check_is_pinned" value="1">
                        <label class="form-check-label small" for="check_is_pinned">Pin to Top</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Post Thread</button>
                    <button type="button" class="btn btn-light btn-sm border" data-bs-toggle="collapse" data-bs-target="#newAnnouncementCollapse">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="student-courses-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 w-100">
        <div class="student-courses-search position-relative flex-grow-1" style="min-width: 240px; max-width: 360px;">
            <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input
                type="text"
                id="discussionSearchInput"
                class="form-control ps-5 pe-5"
                placeholder="Search discussions..."
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
            <select id="discussionCourseSelect" class="form-select form-select-sm" style="width: auto; max-width: 220px;" aria-label="Filter by course">
                <option value="">All Courses</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" @selected((string) ($selectedCourseId ?? '') === (string) $c->id)>{{ $c->title }}</option>
                @endforeach
            </select>

            <select id="discussionSortSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Sort discussions">
                <option value="latest" @selected(($currentSort ?? 'latest') === 'latest')>Newest First</option>
                <option value="oldest" @selected(($currentSort ?? 'latest') === 'oldest')>Oldest First</option>
                <option value="replies_desc" @selected(($currentSort ?? 'latest') === 'replies_desc')>Most Replies</option>
            </select>

            <div class="student-per-page-selector d-flex align-items-center gap-1">
                <select id="discussionPerPageSelect" class="form-select form-select-sm student-per-page-select" aria-label="Discussions per page">
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
        <button type="button" class="student-filter-pill {{ ($currentFilter ?? 'all') === 'all' ? 'active' : '' }}" data-filter="all">
            All <span class="badge bg-light text-dark ms-1" id="countAll">{{ $statusCounts['all'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentFilter ?? 'all') === 'announcements' ? 'active' : '' }}" data-filter="announcements">
            Announcements <span class="badge bg-light text-dark ms-1" id="countAnnouncements">{{ $statusCounts['announcements'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($currentFilter ?? 'all') === 'pinned' ? 'active' : '' }}" data-filter="pinned">
            Pinned <span class="badge bg-light text-dark ms-1" id="countPinned">{{ $statusCounts['pinned'] ?? 0 }}</span>
        </button>
    </div>
</div>

<div id="instructorDiscussionsContainer" class="student-courses-container view-list">
    @include('instructor.partials.discussions-list')
</div>

<div id="instructorDiscussionsPaginationBar" class="student-courses-pagination-bar mt-3 {{ $discussions->total() === 0 ? 'd-none' : '' }}">
    <div class="student-pagination-meta text-muted small">
        Showing <span id="paginationFrom">{{ $discussions->firstItem() ?? 0 }}</span> to <span id="paginationTo">{{ $discussions->lastItem() ?? 0 }}</span> of <span id="paginationTotal">{{ $discussions->total() }}</span> discussions
    </div>
    <div id="paginationNavLinks">
        {!! $discussions->links() !!}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic announcement form action
    const announcementCourseSelect = document.getElementById('announcement-course-id');
    const announcementForm = document.getElementById('new-announcement-form');
    function updateFormAction() {
        if (!announcementCourseSelect || !announcementForm) return;
        const val = announcementCourseSelect.value;
        announcementForm.action = val
            ? @json(route('instructor.discussions.store', ['courseId' => ':courseId'])).replace(':courseId', val)
            : '';
    }
    if (announcementCourseSelect) {
        announcementCourseSelect.addEventListener('change', updateFormAction);
        updateFormAction();
    }

    const STORAGE_KEY = 'out_instructor_discussions_view';
    const baseUrl = @json(route('instructor.discussions.index'));

    const container = document.getElementById('instructorDiscussionsContainer');
    const searchInput = document.getElementById('discussionSearchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const courseSelect = document.getElementById('discussionCourseSelect');
    const sortSelect = document.getElementById('discussionSortSelect');
    const perPageSelect = document.getElementById('discussionPerPageSelect');
    const viewListBtn = document.getElementById('viewModeList');
    const viewGridBtn = document.getElementById('viewModeGrid');
    const filterPills = document.querySelectorAll('.student-filter-pill');
    const paginationBar = document.getElementById('instructorDiscussionsPaginationBar');
    const paginationFrom = document.getElementById('paginationFrom');
    const paginationTo = document.getElementById('paginationTo');
    const paginationTotal = document.getElementById('paginationTotal');
    const paginationNavLinks = document.getElementById('paginationNavLinks');

    let currentState = {
        course_id: @json($selectedCourseId ? (string) $selectedCourseId : ''),
        filter: @json($currentFilter ?? 'all'),
        search: @json($currentSearch ?? ''),
        sort: @json($currentSort ?? 'latest'),
        per_page: Number(@json((int) ($currentPerPage ?? 3))),
        page: Number(@json((int) $discussions->currentPage()))
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
        if (state.filter && state.filter !== 'all') params.set('filter', state.filter);
        if (state.search && state.search.trim() !== '') params.set('search', state.search.trim());
        if (state.sort && state.sort !== 'latest') params.set('sort', state.sort);
        if (state.per_page) params.set('per_page', String(state.per_page));
        if (state.page && state.page > 1) params.set('page', String(state.page));
        const qs = params.toString();
        return qs ? (baseUrl + '?' + qs) : baseUrl;
    }

    function updateFilterPillsUI(filter) {
        filterPills.forEach(function (pill) {
            pill.classList.toggle('active', pill.dataset.filter === filter);
        });
    }

    function updateStatusCounts(counts) {
        if (!counts) return;
        const map = {
            countAll: counts.all ?? 0,
            countAnnouncements: counts.announcements ?? 0,
            countPinned: counts.pinned ?? 0
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.textContent = map[id];
        });
    }

    function fetchDiscussions(pushHistory = true) {
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
            if (!r.ok) throw new Error('Failed to load discussions');
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
            fetchDiscussions(true);
        }, 300);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            toggleClearButton();
            currentState.search = '';
            currentState.page = 1;
            fetchDiscussions(true);
            searchInput.focus();
        });
    }

    courseSelect.addEventListener('change', function () {
        currentState.course_id = courseSelect.value || '';
        currentState.page = 1;
        fetchDiscussions(true);
    });

    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            const nextFilter = pill.dataset.filter || 'all';
            if (currentState.filter === nextFilter) return;
            currentState.filter = nextFilter;
            currentState.page = 1;
            updateFilterPillsUI(nextFilter);
            fetchDiscussions(true);
        });
    });

    sortSelect.addEventListener('change', function () {
        currentState.sort = sortSelect.value || 'latest';
        currentState.page = 1;
        fetchDiscussions(true);
    });

    perPageSelect.addEventListener('change', function () {
        currentState.per_page = Math.max(3, Number(perPageSelect.value) || 3);
        currentState.page = 1;
        fetchDiscussions(true);
    });

    paginationNavLinks.addEventListener('click', function (e) {
        const link = e.target.closest('a.page-link');
        if (!link || !link.href) return;
        e.preventDefault();
        try {
            const targetUrl = new URL(link.href);
            currentState.page = Number(targetUrl.searchParams.get('page') || 1);
            fetchDiscussions(true);
        } catch (err) {}
    });

    container.addEventListener('click', function (e) {
        const resetBtn = e.target.closest('#emptyStateResetBtn');
        if (!resetBtn) return;
        currentState.course_id = '';
        currentState.filter = 'all';
        currentState.search = '';
        currentState.sort = 'latest';
        currentState.page = 1;
        courseSelect.value = '';
        searchInput.value = '';
        sortSelect.value = 'latest';
        toggleClearButton();
        updateFilterPillsUI('all');
        fetchDiscussions(true);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state) {
            currentState = { ...e.state };
        } else {
            const params = new URLSearchParams(window.location.search);
            currentState = {
                course_id: params.get('course_id') || '',
                filter: params.get('filter') || 'all',
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
        updateFilterPillsUI(currentState.filter || 'all');
        fetchDiscussions(false);
    });
});
</script>
@endpush
