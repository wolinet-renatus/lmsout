@extends('layouts.student')

@section('content')
    <!-- Student Enrolled Courses Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                Learning Center
            </span>
            <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 24px;">{{ __('student.enrolled_courses') }}</h2>
            <p class="text-muted mb-0" style="font-size: 13.5px;">{{ __('student.enrolled_courses_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('courses') }}" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold" style="font-size: 13px;">
                <i class="fa-solid fa-compass me-1"></i> Explore More Courses
            </a>
        </div>
    </div>

    <!-- Progressive Disclosure Real-Time Filter & Search Toolbar -->
    <div class="student-courses-toolbar">
        <!-- Live Real-Time Search Input -->
        <div class="student-courses-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text"
                   id="courseSearchInput"
                   value="{{ $currentSearch }}"
                   placeholder="Search courses or instructors..."
                   autocomplete="off">
            <button type="button" class="student-search-clear" id="searchClearBtn" title="Clear search" style="{{ $currentSearch ? 'display: block;' : '' }}">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>
            <i class="fa-solid fa-spinner fa-spin student-search-spinner" id="searchSpinner"></i>
        </div>

        <!-- Status Filter Tabs -->
        <div class="student-filter-pills" id="courseStatusFilters">
            <button type="button" class="student-filter-pill {{ $currentStatus === 'all' ? 'active' : '' }}" data-filter="all">
                All (<span id="countAll">{{ $statusCounts['all'] }}</span>)
            </button>
            <button type="button" class="student-filter-pill {{ $currentStatus === 'in_progress' ? 'active' : '' }}" data-filter="in_progress">
                In Progress (<span id="countInProgress">{{ $statusCounts['in_progress'] }}</span>)
            </button>
            <button type="button" class="student-filter-pill {{ $currentStatus === 'completed' ? 'active' : '' }}" data-filter="completed">
                Completed (<span id="countCompleted">{{ $statusCounts['completed'] }}</span>)
            </button>
        </div>

        <!-- Sort & Per-Page Controls -->
        <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
            <select class="student-per-page-select" id="courseSortSelect" title="Sort courses">
                <option value="latest" {{ $currentSort === 'latest' ? 'selected' : '' }}>Newest Enrolled</option>
                <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Oldest Enrolled</option>
                <option value="title_asc" {{ $currentSort === 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                <option value="title_desc" {{ $currentSort === 'title_desc' ? 'selected' : '' }}>Title (Z-A)</option>
            </select>

            <div class="student-per-page-selector">
                <span class="d-none d-sm-inline">Show:</span>
                <select class="student-per-page-select" id="coursePerPageSelect" title="Items per page">
                    <option value="3" {{ $currentPerPage == 3 ? 'selected' : '' }}>3 / page</option>
                    <option value="6" {{ $currentPerPage == 6 ? 'selected' : '' }}>6 / page</option>
                    <option value="12" {{ $currentPerPage == 12 ? 'selected' : '' }}>12 / page</option>
                </select>
            </div>

            <!-- View Mode Switcher (List vs Grid) -->
            <div class="student-view-toggle">
                <button type="button" class="student-view-btn active" id="viewModeList" title="List View">
                    <i class="fa-solid fa-list"></i>
                    <span class="d-none d-md-inline">List</span>
                </button>
                <button type="button" class="student-view-btn" id="viewModeGrid" title="Grid View">
                    <i class="fa-solid fa-grip"></i>
                    <span class="d-none d-md-inline">Grid</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Courses Container (List View by Default) -->
    <div class="student-courses-container view-list" id="studentCoursesContainer">
        @include('student.partials.my-courses-list', ['enrollments' => $enrollments])
    </div>

    <!-- Pagination & Results Count Bar -->
    <div class="student-courses-pagination-bar" id="studentCoursesPaginationBar" style="{{ $enrollments->total() > 0 ? '' : 'display: none;' }}">
        <div class="student-pagination-meta" id="paginationMetaText">
            Showing <strong id="metaFrom">{{ $enrollments->firstItem() ?? 0 }}</strong> to <strong id="metaTo">{{ $enrollments->lastItem() ?? 0 }}</strong> of <strong id="metaTotal">{{ $enrollments->total() }}</strong> courses
        </div>
        <div id="paginationNavLinks">
            {{ $enrollments->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('courseSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const filterButtons = document.querySelectorAll('#courseStatusFilters .student-filter-pill');
    const sortSelect = document.getElementById('courseSortSelect');
    const perPageSelect = document.getElementById('coursePerPageSelect');
    const container = document.getElementById('studentCoursesContainer');
    const paginationBar = document.getElementById('studentCoursesPaginationBar');
    const paginationNav = document.getElementById('paginationNavLinks');
    const metaFrom = document.getElementById('metaFrom');
    const metaTo = document.getElementById('metaTo');
    const metaTotal = document.getElementById('metaTotal');
    const countAll = document.getElementById('countAll');
    const countInProgress = document.getElementById('countInProgress');
    const countCompleted = document.getElementById('countCompleted');

    const btnList = document.getElementById('viewModeList');
    const btnGrid = document.getElementById('viewModeGrid');

    let currentFilter = '{{ $currentStatus }}';
    let currentSearch = '{{ $currentSearch }}';
    let currentSort = '{{ $currentSort }}';
    let currentPerPage = {{ $currentPerPage }};
    let currentPage = {{ $enrollments->currentPage() }};
    let debounceTimer = null;

    // Fetch dynamic content from server via AJAX
    function fetchCourses(page = 1) {
        currentPage = page;
        container.classList.add('is-loading');
        if (searchSpinner) searchSpinner.style.display = 'block';
        if (searchClearBtn) searchClearBtn.style.display = currentSearch ? 'block' : 'none';

        const params = new URLSearchParams({
            page: currentPage,
            status: currentFilter,
            search: currentSearch,
            sort: currentSort,
            per_page: currentPerPage
        });

        // Update URL query string without reloading page
        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.pushState({ path: newUrl }, '', newUrl);

        fetch(newUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            container.innerHTML = data.html;
            container.classList.remove('is-loading');
            if (searchSpinner) searchSpinner.style.display = 'none';

            // Update Pagination Links
            if (paginationNav) {
                paginationNav.innerHTML = data.pagination;
                bindPaginationEvents();
            }

            // Update Meta Count text
            if (metaFrom) metaFrom.textContent = data.from;
            if (metaTo) metaTo.textContent = data.to;
            if (metaTotal) metaTotal.textContent = data.total;

            if (paginationBar) {
                paginationBar.style.display = data.total > 0 ? 'flex' : 'none';
            }

            // Update Status Tab Counts
            if (data.statusCounts) {
                if (countAll) countAll.textContent = data.statusCounts.all;
                if (countInProgress) countInProgress.textContent = data.statusCounts.in_progress;
                if (countCompleted) countCompleted.textContent = data.statusCounts.completed;
            }

            // Re-bind empty state reset button if rendered
            const emptyResetBtn = document.getElementById('emptyStateResetBtn');
            if (emptyResetBtn) {
                emptyResetBtn.addEventListener('click', resetAllFilters);
            }
        })
        .catch(error => {
            console.error('Error fetching courses:', error);
            container.classList.remove('is-loading');
            if (searchSpinner) searchSpinner.style.display = 'none';
        });
    }

    // Intercept pagination clicks for seamless AJAX navigation
    function bindPaginationEvents() {
        if (!paginationNav) return;
        const pageLinks = paginationNav.querySelectorAll('a.page-link');
        pageLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href) {
                    try {
                        const urlObj = new URL(href, window.location.origin);
                        const page = urlObj.searchParams.get('page') || 1;
                        fetchCourses(page);
                        // Smooth scroll to container top
                        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch (err) {
                        fetchCourses(1);
                    }
                }
            });
        });
    }

    bindPaginationEvents();

    // Real-Time Search with Debounce (300ms)
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            currentSearch = e.target.value.trim();
            if (searchClearBtn) {
                searchClearBtn.style.display = currentSearch ? 'block' : 'none';
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchCourses(1);
            }, 300);
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            currentSearch = '';
            searchClearBtn.style.display = 'none';
            fetchCourses(1);
        });
    }

    // Status Filter Tabs
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            fetchCourses(1);
        });
    });

    // Sort Dropdown
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            currentSort = this.value;
            fetchCourses(1);
        });
    }

    // Per Page Dropdown (At least 3)
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            currentPerPage = parseInt(this.value) || 3;
            fetchCourses(1);
        });
    }

    // Reset filters helper
    function resetAllFilters() {
        if (searchInput) searchInput.value = '';
        currentSearch = '';
        if (searchClearBtn) searchClearBtn.style.display = 'none';

        filterButtons.forEach(b => b.classList.remove('active'));
        if (filterButtons[0]) filterButtons[0].classList.add('active');
        currentFilter = 'all';

        if (sortSelect) sortSelect.value = 'latest';
        currentSort = 'latest';

        fetchCourses(1);
    }

    // View Mode Switching (List vs Grid) with LocalStorage persistence
    function setViewMode(mode) {
        if (mode === 'grid') {
            container.classList.remove('view-list');
            container.classList.add('view-grid');
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        } else {
            container.classList.remove('view-grid');
            container.classList.add('view-list');
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        }
        try {
            localStorage.setItem('out_student_courses_view', mode);
        } catch (e) {}
    }

    if (btnList && btnGrid) {
        btnList.addEventListener('click', () => setViewMode('list'));
        btnGrid.addEventListener('click', () => setViewMode('grid'));

        try {
            const savedMode = localStorage.getItem('out_student_courses_view');
            if (savedMode === 'grid') {
                setViewMode('grid');
            }
        } catch (e) {}
    }
});
</script>
@endpush
