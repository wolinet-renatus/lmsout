@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-building"></i></div>
        <div class="stat-info-v2">
            <span class="label">Regional Centers</span>
            <span class="value">{{ number_format($analytics['kpis']['branches_count']) }}</span>
            <span class="trend up">Active SCMS branches</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info-v2">
            <span class="label">Active Classes</span>
            <span class="value">{{ number_format($analytics['kpis']['active_classes_count']) }}</span>
            <span class="trend">{{ number_format($analytics['kpis']['ongoing_f2f_count']) }} ongoing learners</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-award"></i></div>
        <div class="stat-info-v2">
            <span class="label">Certificates Ready / Issued</span>
            <span class="value">{{ number_format($analytics['kpis']['ready_certs_count']) }} / {{ number_format($analytics['kpis']['issued_certs_count']) }}</span>
            <span class="trend up">Campus clearance status</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-coins"></i></div>
        <div class="stat-info-v2">
            <span class="label">Center Collections</span>
            <span class="value">{{ currency_format($analytics['kpis']['total_invoiced_paid']) }}</span>
            <span class="trend">Balance: {{ currency_format($analytics['kpis']['total_invoiced_balance']) }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-sitemap mr-1 text-primary"></i> Regional Center Performance</h4>
                <a href="{{ route('admin.campus.analytics.branches') }}" class="btn btn-sm btn-outline-primary">Full Analytics</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr><th>Regional Center</th><th>Applicants</th><th>Invoices</th><th>Paid</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($analytics['branch_performance'] as $row)
                            <tr>
                                <td><strong>{{ $row->name }}</strong></td>
                                <td>{{ number_format($row->applicants_count) }}</td>
                                <td>{{ number_format($row->invoices_count) }}</td>
                                <td class="text-success font-weight-bold">{{ currency_format($row->total_paid) }}</td>
                                <td class="text-danger">{{ currency_format($row->total_balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No branch records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['branch_performance']])
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-certificate mr-1 text-success"></i> Ready &amp; Issued Certificates</h4>
                <a href="{{ route('admin.campus.certificates.index') }}" class="btn btn-sm btn-outline-success">Manage</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Student</th><th>Course</th><th>Center</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($analytics['ready_certificates'] as $cert)
                            <tr>
                                <td>{{ $cert->applicant?->full_name ?? $cert->user?->name ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($cert->course?->title ?? '-', 26) }}</td>
                                <td>{{ $cert->branch?->name ?? '-' }}</td>
                                <td><span class="badge badge-{{ $cert->certificate_status === 'issued' ? 'success' : 'warning' }}">{{ ucfirst($cert->certificate_status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No ready certificates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['ready_certificates']])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-calendar-alt mr-1 text-info"></i> Active Scheduled Classes</h4>
                <a href="{{ route('admin.campus.timetable.index') }}" class="btn btn-sm btn-outline-primary">Timetable</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Course</th><th>Center</th><th>Venue</th><th>Tutor</th><th>Start</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($analytics['active_classes'] as $cls)
                            <tr>
                                <td>{{ \Illuminate\Support\Str::limit($cls->course?->title ?? '-', 28) }}</td>
                                <td>{{ $cls->branch?->name ?? '-' }}</td>
                                <td>{{ $cls->venue?->name ?? '-' }}</td>
                                <td>{{ $cls->instructor?->name ?? '-' }}</td>
                                <td>{{ optional($cls->start_date)->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No active classes scheduled.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['active_classes']])
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-user-check mr-1 text-warning"></i> Pending Instructor Approvals ({{ $analytics['kpis']['pending_instructors_count'] }})</h4>
                <a href="{{ route('admin.instructors.pending') }}" class="btn btn-sm btn-outline-warning">Review</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($analytics['pending_instructors'] as $inst)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $inst->name }}</strong>
                                <div class="small text-muted">{{ $inst->email }} &bull; {{ $inst->specialization ?? 'Applicant Tutor' }}</div>
                            </div>
                            <span class="badge badge-warning">Pending</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-3">All instructor applications reviewed.</li>
                    @endforelse
                </ul>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['pending_instructors']])
        </div>
    </div>
</div>
