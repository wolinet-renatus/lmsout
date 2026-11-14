@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info-v2">
            <span class="label">Active Center Classes</span>
            <span class="value">{{ number_format($analytics['kpis']['active_classes']) }}</span>
            <span class="trend">{{ number_format($analytics['kpis']['completed_classes']) }} completed cohorts</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-door-open"></i></div>
        <div class="stat-info-v2">
            <span class="label">Center Venues &amp; Learners</span>
            <span class="value">{{ number_format($analytics['kpis']['venues_count']) }} Venues</span>
            <span class="trend up">{{ number_format($analytics['kpis']['branch_enrollments']) }} center enrollments</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-user-check"></i></div>
        <div class="stat-info-v2">
            <span class="label">Center Attendance Rate</span>
            <span class="value">{{ $analytics['kpis']['attendance_rate'] }}%</span>
            <span class="trend">{{ number_format($analytics['kpis']['attendance_records_count']) }} check-in records</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-certificate"></i></div>
        <div class="stat-info-v2">
            <span class="label">Certificates Ready / Issued</span>
            <span class="value">{{ number_format($analytics['kpis']['ready_certificates']) }} / {{ number_format($analytics['kpis']['issued_certificates']) }}</span>
            <span class="trend">Center academic clearance</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-calendar-alt mr-1 text-primary"></i> Center Timetable &amp; Cohorts</h4>
                <a href="{{ route('admin.campus.timetable.index') }}" class="btn btn-sm btn-outline-primary">Manage Schedule</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Course</th><th>Venue</th><th>Tutor</th><th>Session</th><th>Enrolled</th><th>Start</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['active_classes'] as $cls)
                            <tr>
                                <td><strong>{{ \Illuminate\Support\Str::limit($cls->course?->title ?? '-', 28) }}</strong></td>
                                <td>{{ $cls->venue?->name ?? '-' }}</td>
                                <td>{{ $cls->instructor?->name ?? '-' }}</td>
                                <td><span class="badge badge-light text-capitalize">{{ $cls->session ?? 'Day' }}</span></td>
                                <td>{{ $cls->enrollments_count }} / {{ $cls->max_capacity ?? ($cls->venue?->capacity ?? '-') }}</td>
                                <td>{{ optional($cls->start_date)->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No active cohorts in this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['active_classes']])
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-building mr-1 text-info"></i> Venue Capacity</h4>
                <a href="{{ route('admin.campus.venues.index') }}" class="btn btn-sm btn-outline-primary">Venues</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Venue</th><th>Seats</th><th>Active</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['venues'] as $v)
                            <tr>
                                <td>{{ $v->name }}</td>
                                <td>{{ $v->capacity }}</td>
                                <td><span class="badge badge-primary">{{ $v->active_classes_count }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No venues assigned to this branch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['venues']])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-award mr-1 text-success"></i> Certificate Clearance Queue</h4>
                <a href="{{ route('admin.campus.certificates.index') }}" class="btn btn-sm btn-outline-success">Clearances</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Learner</th><th>Course</th><th>Attendance</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['ready_clearances'] as $enr)
                            <tr>
                                <td>{{ $enr->applicant?->full_name ?? $enr->user?->name ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($enr->course?->title ?? '-', 24) }}</td>
                                <td>{{ number_format((float) $enr->attendance_percentage, 1) }}%</td>
                                <td><span class="badge badge-{{ $enr->certificate_status === 'ready' ? 'success' : 'secondary' }}">{{ ucfirst($enr->certificate_status ?? 'pending') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No pending or ready clearances in this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['ready_clearances']])
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-clipboard-check mr-1 text-primary"></i> Recent Attendance Check-Ins</h4>
                <a href="{{ route('admin.campus.attendance.index') }}" class="btn btn-sm btn-outline-primary">Mark Attendance</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Student</th><th>Course</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['recent_attendance'] as $att)
                            <tr>
                                <td>{{ $att->user?->name ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($att->course?->title ?? '-', 24) }}</td>
                                <td>{{ optional($att->attendance_date)->format('d M Y') ?? '-' }}</td>
                                <td><span class="badge badge-{{ $att->status === 'present' ? 'success' : ($att->status === 'late' ? 'warning' : 'danger') }}">{{ ucfirst($att->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No attendance logs in this center yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['recent_attendance']])
        </div>
    </div>
</div>
