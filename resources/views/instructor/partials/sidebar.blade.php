@php
    // Reusable Component Safety: only render when invoked from the portal layout shell
    if (empty($fromLayout)) {
        return;
    }

    $user = $user ?? auth()->user();

    $inCoursesGroup = request()->routeIs([
        'instructor.courses*',
        'instructor.bundles.*',
        'instructor.coupons.*',
    ]);

    $inTeachingGroup = request()->routeIs([
        'instructor.live-classes.*',
        'instructor.campus.*',
        'instructor.assignments*',
        'instructor.quizzes*',
        'instructor.students*',
    ]);

    $inCommGroup = request()->routeIs([
        'instructor.messages.*',
        'instructor.discussions.*',
        'instructor.tickets.*',
        'instructor.help.*',
    ]);

    $inFinanceGroup = request()->routeIs([
        'instructor.analytics',
        'instructor.reviews',
        'instructor.earnings',
        'instructor.withdrawals',
    ]);
@endphp

<aside class="out-portal-sidebar" id="outPortalSidebar">
    <ul class="out-sidebar-nav">
        <li class="out-nav-item">
            <a href="{{ route('instructor.dashboard') }}" class="out-nav-link {{ request()->routeIs('instructor.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- Courses & Catalog --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inCoursesGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#instructorCoursesMenu"
                aria-expanded="{{ $inCoursesGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-book-open"></i>
                <span>Courses &amp; Catalog</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inCoursesGroup ? 'show' : '' }}" id="instructorCoursesMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('instructor.courses') }}" class="out-nav-link {{ request()->routeIs('instructor.courses*') ? 'active' : '' }}">
                            <i class="fa-solid fa-book-bookmark"></i>
                            <span>My Courses</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.bundles.index') }}" class="out-nav-link {{ request()->routeIs('instructor.bundles.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Course Bundles</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.coupons.index') }}" class="out-nav-link {{ request()->routeIs('instructor.coupons.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-tags"></i>
                            <span>Discount Coupons</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Teaching & Learners --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inTeachingGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#instructorTeachingMenu"
                aria-expanded="{{ $inTeachingGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Teaching &amp; Classes</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inTeachingGroup ? 'show' : '' }}" id="instructorTeachingMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('instructor.live-classes.index') }}" class="out-nav-link {{ request()->routeIs('instructor.live-classes.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-video"></i>
                            <span>Live Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.campus.index') }}" class="out-nav-link {{ request()->routeIs('instructor.campus.*') && request('scope') !== 'branch' ? 'active' : '' }}">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>Campus Classes &amp; Attendance</span>
                        </a>
                    </li>
                    @if ($user && $user->branch_id)
                        <li>
                            <a href="{{ route('instructor.campus.index', ['scope' => 'branch']) }}" class="out-nav-link {{ request()->routeIs('instructor.campus.index') && request('scope') === 'branch' ? 'active' : '' }}">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span>Branch Timetable</span>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('instructor.assignments.index') }}" class="out-nav-link {{ request()->routeIs('instructor.assignments*') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span>Assignments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.quizzes.index') }}" class="out-nav-link {{ request()->routeIs('instructor.quizzes*') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Quiz Attempts</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.students') }}" class="out-nav-link {{ request()->routeIs('instructor.students*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Enrolled Students</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Communication & Support --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inCommGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#instructorCommMenu"
                aria-expanded="{{ $inCommGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-comments"></i>
                <span>Communication</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inCommGroup ? 'show' : '' }}" id="instructorCommMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('instructor.messages.index') }}" class="out-nav-link {{ request()->routeIs('instructor.messages.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-envelope"></i>
                            <span>Messages</span>
                            <span class="badge bg-danger rounded-pill out-nav-badge instructor-chat-badge" style="display: none;"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.discussions.index') }}" class="out-nav-link {{ request()->routeIs('instructor.discussions.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-comment-dots"></i>
                            <span>Course Forums</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.tickets.index') }}" class="out-nav-link {{ request()->routeIs('instructor.tickets.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-headset"></i>
                            <span>Support Tickets</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.help.index') }}" class="out-nav-link {{ request()->routeIs('instructor.help.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Online Help</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Reports & Finance --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inFinanceGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#instructorReportsMenu"
                aria-expanded="{{ $inFinanceGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>Reports &amp; Payouts</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inFinanceGroup ? 'show' : '' }}" id="instructorReportsMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('instructor.analytics') }}" class="out-nav-link {{ request()->routeIs('instructor.analytics') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.reviews') }}" class="out-nav-link {{ request()->routeIs('instructor.reviews') ? 'active' : '' }}">
                            <i class="fa-solid fa-star"></i>
                            <span>Reviews &amp; Ratings</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('instructor.earnings') }}" class="out-nav-link {{ request()->routeIs('instructor.earnings') || request()->routeIs('instructor.withdrawals') ? 'active' : '' }}">
                            <i class="fa-solid fa-wallet"></i>
                            <span>Earnings &amp; Payouts</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</aside>
