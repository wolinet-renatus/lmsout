@extends('layouts.student')

@section('title', 'Campus Class Timetable')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-1 fw-semibold">
                <i class="fa-solid fa-calendar-days me-1"></i> Campus Timetable
            </span>
            <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3 py-1 d-inline-flex align-items-center gap-2">
                <span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                Live Sync: <span id="timetableLiveTimestamp">{{ now()->format('H:i:s') }}</span>
            </span>
        </div>
        <h1 class="h3 fw-bold mb-1" style="color:#0f172a;">Campus Class Timetable &amp; Cohorts</h1>
        <p class="text-muted mb-0">Live view of your Face-to-Face / Hybrid schedules, assigned venues, tutors, and regional cohorts.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="refreshTimetableLiveBtn" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-rotate me-1"></i> Sync Status Now
        </button>
        <a href="{{ route('student.campus.attendance') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-user-check me-1"></i> Attendance &amp; Check-In
        </a>
    </div>
</div>

<!-- Real-Time Timetable Summary KPI Strip -->
<div class="out-stat-grid mb-4">
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-blue">
            <i class="fa-solid fa-book-open"></i>
        </div>
        <div>
            <div class="out-stat-value" id="tt-kpi-enrolled">{{ $summary['enrolled_total'] }}</div>
            <div class="out-stat-label">My Campus Classes</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-green">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <div>
            <div class="out-stat-value text-success" id="tt-kpi-active">{{ $summary['active_or_ongoing'] }}</div>
            <div class="out-stat-label">Ongoing / Upcoming</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-indigo">
            <i class="fa-solid fa-flag-checkered"></i>
        </div>
        <div>
            <div class="out-stat-value" id="tt-kpi-completed">{{ $summary['completed_count'] }}</div>
            <div class="out-stat-label">Completed Sessions</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-amber">
            <i class="fa-solid fa-building-columns"></i>
        </div>
        <div>
            <div class="out-stat-value" id="tt-kpi-regional">{{ $summary['regional_count'] }}</div>
            <div class="out-stat-label">Regional Cohorts</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('student.campus.timetable') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Session Statuses</option>
                    <option value="in_session" {{ $statusFilter === 'in_session' ? 'selected' : '' }}>In Session Now</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Ongoing Cohort</option>
                    <option value="upcoming" {{ $statusFilter === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending / Awaiting Payment</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="search" name="search" value="{{ $searchTerm }}" class="form-control form-control-sm" placeholder="Search course, campus branch, venue, or tutor...">
            </div>
            <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1 flex-md-grow-0">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if ($statusFilter !== 'all' || $searchTerm !== '')
                    <a href="{{ route('student.campus.timetable') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- My Enrolled Campus Classes -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;">
        <h5 class="mb-0 fw-bold" style="color:#0f172a;">My Enrolled Campus Classes</h5>
        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $enrollments->count() }} Classes</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Course</th>
                        <th>Branch &amp; Venue</th>
                        <th>Tutor</th>
                        <th>Session &amp; Dates</th>
                        <th>Payment / Attendance</th>
                        <th>Live Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $e)
                        <tr data-enrolled-tt-row="{{ $e->id }}">
                            <td>
                                <strong class="d-block" style="color:#0f172a;">{{ $e->course_title }}</strong>
                                <small class="text-muted">{{ $e->delivery_mode }}</small>
                            </td>
                            <td>
                                <span class="d-block fw-semibold" data-tt-enr-branch="{{ $e->id }}">{{ $e->branch_name }}</span>
                                <small class="text-muted" data-tt-enr-venue="{{ $e->id }}"><i class="fa-solid fa-location-dot me-1"></i>{{ $e->venue_name }}</small>
                            </td>
                            <td data-tt-enr-tutor="{{ $e->id }}">{{ $e->tutor_name }}</td>
                            <td class="small">
                                <span class="badge bg-light text-dark border mb-1">{{ $e->session_name }}</span>
                                @if ($e->time_window)
                                    <span class="badge bg-info bg-opacity-10 text-dark border mb-1">{{ $e->time_window }}</span>
                                @endif
                                @if ($e->days_label)
                                    <div class="text-muted">{{ $e->days_label }}</div>
                                @endif
                                <div class="text-muted">{{ $e->start_date_label }} &rarr; {{ $e->end_date_label }}</div>
                            </td>
                            <td class="small">
                                <div>Payment: <strong>{{ $e->payment_status }}</strong></div>
                                <div>Attendance: <strong>{{ $e->attendance_percentage }}</strong></div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $e->live_status_badge }}" data-tt-enr-status="{{ $e->id }}">{{ $e->live_status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-calendar-xmark fa-2x mb-2 opacity-50 d-block"></i>
                                You have no Face-to-Face or Hybrid enrollments matching this view.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Regional Cohorts -->
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;">
        <h5 class="mb-0 fw-bold" style="color:#0f172a;">Regional &amp; Cohort Timetable</h5>
        <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $branchTimetable->count() }} Cohorts</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Course</th>
                        <th>Branch &amp; Venue</th>
                        <th>Tutor</th>
                        <th>Session &amp; Time</th>
                        <th>Dates</th>
                        <th>Live Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branchTimetable as $row)
                        <tr data-regional-tt-row="{{ $row->id }}">
                            <td><strong>{{ $row->course_title }}</strong></td>
                            <td>
                                <span class="d-block fw-semibold">{{ $row->branch_name }}</span>
                                <small class="text-muted">{{ $row->venue_name }}</small>
                            </td>
                            <td>{{ $row->tutor_name }}</td>
                            <td class="small">
                                <span class="badge bg-light text-dark border">{{ $row->session_name }}</span>
                                <div class="text-muted mt-1">{{ $row->time_window }} &bull; {{ $row->days_label }}</div>
                            </td>
                            <td class="small">{{ $row->start_date_label }} &rarr; {{ $row->end_date_label }}</td>
                            <td>
                                <span class="badge bg-{{ $row->live_status_badge }}" data-tt-reg-status="{{ $row->id }}">{{ $row->live_status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No regional cohort schedules matched this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $liveEndpoint = route('student.campus.timetable.live', array_filter([
        'status' => $statusFilter !== 'all' ? $statusFilter : null,
        'search' => $searchTerm !== '' ? $searchTerm : null,
    ]));
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoint = @json($liveEndpoint);
    const syncBtn = document.getElementById('refreshTimetableLiveBtn');
    const syncTimeEl = document.getElementById('timetableLiveTimestamp');

    function updateStatusBadge(el, badgeColor, label) {
        if (!el) return;
        el.className = 'badge bg-' + badgeColor;
        if (el.textContent.trim() !== label) {
            el.textContent = label;
        }
    }

    async function pollTimetableStatus() {
        try {
            const res = await fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            if (syncTimeEl) syncTimeEl.textContent = data.synced_at;
            if (data.summary) {
                const map = {
                    'tt-kpi-enrolled': data.summary.enrolled_total,
                    'tt-kpi-active': data.summary.active_or_ongoing,
                    'tt-kpi-completed': data.summary.completed_count,
                    'tt-kpi-regional': data.summary.regional_count
                };
                Object.entries(map).forEach(([id, val]) => {
                    const node = document.getElementById(id);
                    if (node && val !== undefined) node.textContent = val;
                });
            }

            (data.enrollments || []).forEach(e => {
                updateStatusBadge(document.querySelector(`[data-tt-enr-status="${e.id}"]`), e.live_status_badge, e.live_status_label);
                const venueEl = document.querySelector(`[data-tt-enr-venue="${e.id}"]`);
                if (venueEl) venueEl.innerHTML = `<i class="fa-solid fa-location-dot me-1"></i>${e.venue_name}`;
                const tutorEl = document.querySelector(`[data-tt-enr-tutor="${e.id}"]`);
                if (tutorEl) tutorEl.textContent = e.tutor_name;
            });

            (data.branchTimetable || []).forEach(r => {
                updateStatusBadge(document.querySelector(`[data-tt-reg-status="${r.id}"]`), r.live_status_badge, r.live_status_label);
            });
        } catch (err) {
            // Silent retry on transient error
        }
    }

    if (syncBtn) syncBtn.addEventListener('click', pollTimetableStatus);
    setInterval(() => {
        if (!document.hidden) pollTimetableStatus();
    }, 10000);
});
</script>
@endpush
