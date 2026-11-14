@php
    // Reusable Component Safety: only render when invoked from the portal layout shell
    if (empty($fromLayout)) {
        return;
    }

    $user = $user ?? auth()->user();

    $inLearningGroup = request()->routeIs([
        'student.my-courses*',
        'student.live-classes.*',
        'student.assignments*',
        'student.quiz-attempts*',
        'student.certificates*',
    ]);

    $inCampusGroup = request()->routeIs([
        'student.campus.timetable',
        'student.campus.attendance*',
        'student.community.*',
    ]);

    $inBillingGroup = request()->routeIs([
        'student.campus.invoices*',
        'student.payments*',
    ]);

    $inSupportGroup = request()->routeIs([
        'student.messages.*',
        'student.ai-chat.*',
        'student.notifications.*',
        'student.tickets.*',
        'student.help.*',
    ]);
@endphp

<aside class="out-portal-sidebar" id="outPortalSidebar">
    <ul class="out-sidebar-nav">
        <li class="out-nav-item">
            <a href="{{ route('student.dashboard') }}" class="out-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- My Learning --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inLearningGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#studentLearningMenu"
                aria-expanded="{{ $inLearningGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-book-open"></i>
                <span>My Learning</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inLearningGroup ? 'show' : '' }}" id="studentLearningMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('student.my-courses') }}" class="out-nav-link {{ request()->routeIs('student.my-courses*') ? 'active' : '' }}">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>My Courses</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.live-classes.index') }}" class="out-nav-link {{ request()->routeIs('student.live-classes.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-video"></i>
                            <span>Live Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.assignments') }}" class="out-nav-link {{ request()->routeIs('student.assignments*') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span>Assignments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.quiz-attempts') }}" class="out-nav-link {{ request()->routeIs('student.quiz-attempts*') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Quiz Attempts</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.certificates') }}" class="out-nav-link {{ request()->routeIs('student.certificates*') ? 'active' : '' }}">
                            <i class="fa-solid fa-certificate"></i>
                            <span>Certificates</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Campus & Timetable --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inCampusGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#studentCampusMenu"
                aria-expanded="{{ $inCampusGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-building-columns"></i>
                <span>Campus Life</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inCampusGroup ? 'show' : '' }}" id="studentCampusMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('student.campus.timetable') }}" class="out-nav-link {{ request()->routeIs('student.campus.timetable') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Campus Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.campus.attendance') }}" class="out-nav-link {{ request()->routeIs('student.campus.attendance*') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>Campus Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.community.index') }}" class="out-nav-link {{ request()->routeIs('student.community.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Campus Community</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Billing & GePG --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inBillingGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#studentBillingMenu"
                aria-expanded="{{ $inBillingGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Billing &amp; Fees</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inBillingGroup ? 'show' : '' }}" id="studentBillingMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('student.campus.invoices.index') }}" class="out-nav-link {{ request()->routeIs('student.campus.invoices*') ? 'active' : '' }}">
                            <i class="fa-solid fa-barcode"></i>
                            <span>Invoices &amp; Control Nos</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.payments') }}" class="out-nav-link {{ request()->routeIs('student.payments*') ? 'active' : '' }}">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Payment History</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Tools & Support --}}
        <li class="out-nav-item">
            <button type="button"
                class="out-nav-link out-nav-toggle {{ $inSupportGroup ? 'group-active' : '' }}"
                data-bs-toggle="collapse"
                data-bs-target="#studentSupportMenu"
                aria-expanded="{{ $inSupportGroup ? 'true' : 'false' }}">
                <i class="fa-solid fa-headset"></i>
                <span>Support &amp; Tools</span>
                <i class="fa-solid fa-chevron-right out-nav-chevron"></i>
            </button>
            <div class="collapse {{ $inSupportGroup ? 'show' : '' }}" id="studentSupportMenu">
                <ul class="out-nav-submenu">
                    <li>
                        <a href="{{ route('student.messages.index') }}" class="out-nav-link {{ request()->routeIs('student.messages.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-envelope"></i>
                            <span>Messages</span>
                            <span class="badge bg-danger rounded-pill out-nav-badge student-chat-badge" style="display: none;"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.ai-chat.index') }}" class="out-nav-link {{ request()->routeIs('student.ai-chat.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-robot"></i>
                            <span>AI Study Assistant</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.notifications.index') }}" class="out-nav-link {{ request()->routeIs('student.notifications.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.tickets.index') }}" class="out-nav-link {{ request()->routeIs('student.tickets.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-life-ring"></i>
                            <span>Support Desk</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('student.help.index') }}" class="out-nav-link {{ request()->routeIs('student.help.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Online Help</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</aside>
