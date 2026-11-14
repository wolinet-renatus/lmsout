@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info-v2">
            <span class="label">Center Applicants</span>
            <span class="value">{{ number_format($analytics['kpis']['applicants_count']) }}</span>
            <span class="trend up">{{ number_format($analytics['kpis']['this_month_applicants']) }} registered this month</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-info-v2">
            <span class="label">Open Support Tickets</span>
            <span class="value">{{ number_format($analytics['kpis']['open_tickets_count']) }}</span>
            <span class="trend">Branch support desk queue</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-clipboard-check"></i></div>
        <div class="stat-info-v2">
            <span class="label">Today Check-Ins</span>
            <span class="value">{{ number_format($analytics['kpis']['today_attendance']) }}</span>
            <span class="trend">{{ number_format($analytics['kpis']['active_classes']) }} active center classes</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-certificate"></i></div>
        <div class="stat-info-v2">
            <span class="label">Certificates Ready</span>
            <span class="value">{{ number_format($analytics['kpis']['ready_certificates']) }}</span>
            <span class="trend">Awaiting student pickup/issue</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users mr-1 text-primary"></i> Recent Branch Applicants Desk</h4>
                <a href="{{ route('admin.campus.applicants.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i> New Applicant</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Applicant</th><th>Phone</th><th>Course</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['recent_applicants'] as $app)
                            <tr>
                                <td><a href="{{ route('admin.campus.applicants.show', $app) }}"><strong>{{ $app->full_name }}</strong></a></td>
                                <td>{{ $app->phone }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($app->enrollments->first()?->course?->title ?? '-', 24) }}</td>
                                <td>{{ optional($app->created_at)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No recent applicants in this branch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['recent_applicants']])
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-headset mr-1 text-warning"></i> Open Support Tickets Queue</h4>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-primary">Ticket Desk</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($analytics['open_tickets'] as $t)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $t->subject }}</strong>
                                <div class="small text-muted"><code>{{ $t->ticket_number }}</code> &bull; {{ $t->user?->name ?? '-' }}</div>
                            </div>
                            <span class="badge badge-{{ $t->priority_badge }}">{{ ucfirst($t->priority) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-3">No open support tickets in this center.</li>
                    @endforelse
                </ul>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['open_tickets']])
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-calendar-alt mr-1 text-info"></i> Active Center Classes</h4>
        <a href="{{ route('admin.campus.timetable.index') }}" class="btn btn-sm btn-outline-primary">Full Timetable</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Course</th><th>Venue</th><th>Tutor</th><th>Schedule</th><th>Session</th></tr></thead>
            <tbody>
                @forelse ($analytics['upcoming_classes'] as $cls)
                    <tr>
                        <td>{{ $cls->course?->title ?? '-' }}</td>
                        <td>{{ $cls->venue?->name ?? '-' }}</td>
                        <td>{{ $cls->instructor?->name ?? '-' }}</td>
                        <td>{{ $cls->schedule_days ?? '-' }} ({{ $cls->start_time ?? '-' }})</td>
                        <td><span class="badge badge-light text-capitalize">{{ $cls->session ?? 'Day' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No active classes scheduled in this branch.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['upcoming_classes']])
</div>
