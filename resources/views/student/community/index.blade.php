@extends('layouts.student')

@section('title', __('student.community_title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">{{ __('student.discussion_qa') }}</h2>
        <p class="text-muted small mb-0">{{ __('student.community_desc') }}</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#askQuestionModal">
            <i class="fa-solid fa-plus me-1"></i>{{ __('student.ask_question') }}
        </button>
    </div>
</div>

<div class="student-courses-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 w-100">
        <div class="student-courses-search position-relative flex-grow-1" style="min-width: 240px; max-width: 380px;">
            <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input
                type="text"
                id="communitySearchInput"
                class="form-control ps-5 pe-5"
                placeholder="{{ __('student.search_discussions') }}"
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
            <select id="communitySortSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Sort questions">
                <option value="latest" @selected(($currentSort ?? 'latest') === 'latest')>Newest First</option>
                <option value="oldest" @selected(($currentSort ?? 'latest') === 'oldest')>Oldest First</option>
                <option value="most_answered" @selected(($currentSort ?? 'latest') === 'most_answered')>Most Answered</option>
                <option value="most_voted" @selected(($currentSort ?? 'latest') === 'most_voted')>Most Voted</option>
            </select>

            <div class="student-per-page-selector d-flex align-items-center gap-1">
                <select id="communityPerPageSelect" class="form-select form-select-sm student-per-page-select" aria-label="Questions per page">
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
        <button type="button" class="student-filter-pill {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}" data-filter="all">
            {{ __('student.all_questions') }} <span class="badge bg-light text-dark ms-1" id="countAll">{{ $statusCounts['all'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($filter ?? 'all') === 'unanswered' ? 'active' : '' }}" data-filter="unanswered">
            {{ __('student.unanswered') }} <span class="badge bg-light text-dark ms-1" id="countUnanswered">{{ $statusCounts['unanswered'] ?? 0 }}</span>
        </button>
        <button type="button" class="student-filter-pill {{ ($filter ?? 'all') === 'my_questions' ? 'active' : '' }}" data-filter="my_questions">
            {{ __('student.my_questions') }} <span class="badge bg-light text-dark ms-1" id="countMyQuestions">{{ $statusCounts['my_questions'] ?? 0 }}</span>
        </button>
    </div>
</div>

<div id="studentCommunityContainer" class="student-courses-container view-list">
    @include('student.partials.community-list')
</div>

<div id="studentCommunityPaginationBar" class="student-courses-pagination-bar mt-3 {{ $questions->total() === 0 ? 'd-none' : '' }}">
    <div class="student-pagination-meta text-muted small">
        Showing <span id="paginationFrom">{{ $questions->firstItem() ?? 0 }}</span> to <span id="paginationTo">{{ $questions->lastItem() ?? 0 }}</span> of <span id="paginationTotal">{{ $questions->total() }}</span> questions
    </div>
    <div id="paginationNavLinks">
        {!! $questions->links() !!}
    </div>
</div>

@include('student.community.partials.ask-question-modal')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'out_student_community_view';
    const baseUrl = @json(route('student.community.index'));

    const container = document.getElementById('studentCommunityContainer');
    const searchInput = document.getElementById('communitySearchInput');
    const clearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const sortSelect = document.getElementById('communitySortSelect');
    const perPageSelect = document.getElementById('communityPerPageSelect');
    const viewListBtn = document.getElementById('viewModeList');
    const viewGridBtn = document.getElementById('viewModeGrid');
    const filterPills = document.querySelectorAll('.student-filter-pill');
    const paginationBar = document.getElementById('studentCommunityPaginationBar');
    const paginationFrom = document.getElementById('paginationFrom');
    const paginationTo = document.getElementById('paginationTo');
    const paginationTotal = document.getElementById('paginationTotal');
    const paginationNavLinks = document.getElementById('paginationNavLinks');

    let currentState = {
        filter: @json($filter ?? 'all'),
        search: @json($currentSearch ?? ''),
        sort: @json($currentSort ?? 'latest'),
        per_page: Number(@json((int) ($currentPerPage ?? 3))),
        page: Number(@json((int) $questions->currentPage()))
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
            countUnanswered: counts.unanswered ?? 0,
            countMyQuestions: counts.my_questions ?? 0
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.textContent = map[id];
        });
    }

    function fetchQuestions(pushHistory = true) {
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
            if (!r.ok) throw new Error('Failed to load questions');
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
            fetchQuestions(true);
        }, 300);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            toggleClearButton();
            currentState.search = '';
            currentState.page = 1;
            fetchQuestions(true);
            searchInput.focus();
        });
    }

    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            const nextFilter = pill.dataset.filter || 'all';
            if (currentState.filter === nextFilter) return;
            currentState.filter = nextFilter;
            currentState.page = 1;
            updateFilterPillsUI(nextFilter);
            fetchQuestions(true);
        });
    });

    sortSelect.addEventListener('change', function () {
        currentState.sort = sortSelect.value || 'latest';
        currentState.page = 1;
        fetchQuestions(true);
    });

    perPageSelect.addEventListener('change', function () {
        currentState.per_page = Math.max(3, Number(perPageSelect.value) || 3);
        currentState.page = 1;
        fetchQuestions(true);
    });

    paginationNavLinks.addEventListener('click', function (e) {
        const link = e.target.closest('a.page-link');
        if (!link || !link.href) return;
        e.preventDefault();
        try {
            const targetUrl = new URL(link.href);
            currentState.page = Number(targetUrl.searchParams.get('page') || 1);
            fetchQuestions(true);
        } catch (err) {}
    });

    container.addEventListener('click', function (e) {
        const resetBtn = e.target.closest('#emptyStateResetBtn');
        if (!resetBtn) return;
        currentState.filter = 'all';
        currentState.search = '';
        currentState.sort = 'latest';
        currentState.page = 1;
        searchInput.value = '';
        sortSelect.value = 'latest';
        toggleClearButton();
        updateFilterPillsUI('all');
        fetchQuestions(true);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state) {
            currentState = { ...e.state };
        } else {
            const params = new URLSearchParams(window.location.search);
            currentState = {
                filter: params.get('filter') || 'all',
                search: params.get('search') || '',
                sort: params.get('sort') || 'latest',
                per_page: Math.max(3, Number(params.get('per_page') || 3)),
                page: Math.max(1, Number(params.get('page') || 1))
            };
        }
        searchInput.value = currentState.search || '';
        sortSelect.value = currentState.sort || 'latest';
        perPageSelect.value = String(currentState.per_page || 3);
        toggleClearButton();
        updateFilterPillsUI(currentState.filter || 'all');
        fetchQuestions(false);
    });
});
</script>
@endpush
