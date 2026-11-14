@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info-v2">
            <span class="label">Total Applicants (All Centers)</span>
            <span class="value">{{ number_format($analytics['kpis']['total_applicants']) }}</span>
            <span class="trend up">{{ number_format($analytics['kpis']['this_month_applicants']) }} this month</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-bullseye"></i></div>
        <div class="stat-info-v2">
            <span class="label">Intake Conversion Rate</span>
            <span class="value">{{ $analytics['kpis']['conversion_rate'] }}%</span>
            <span class="trend">{{ number_format($analytics['kpis']['converted_applicants']) }} converted to approved enrollment</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-book-open"></i></div>
        <div class="stat-info-v2">
            <span class="label">Catalog &amp; Bundles</span>
            <span class="value">{{ number_format($analytics['kpis']['published_courses']) }} Courses</span>
            <span class="trend up">{{ number_format($analytics['kpis']['active_bundles']) }} active bundles</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-tags"></i></div>
        <div class="stat-info-v2">
            <span class="label">Campaign Coupons</span>
            <span class="value">{{ number_format($analytics['kpis']['coupon_redemptions']) }} Uses</span>
            <span class="trend">{{ number_format($analytics['kpis']['active_coupons']) }} active &bull; {{ number_format($analytics['kpis']['discount_waivers']) }} fee waivers</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card-v2 h-100">
            <div class="header">
                <h3>12-Month Intake &amp; Enrollment Funnel</h3>
            </div>
            <div class="chart-container" style="position: relative; height:290px; width:100%">
                <canvas id="marketingIntakeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-map-marker-alt mr-1 text-primary"></i> Top Centers by Intake</h4>
                <a href="{{ route('admin.reports.applicants') }}" class="btn btn-sm btn-outline-primary">Report</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Center</th><th class="text-right">Applicants</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['branch_demand'] as $b)
                            <tr>
                                <td>{{ $b->name }}</td>
                                <td class="text-right"><span class="badge badge-primary">{{ number_format($b->applicants_count) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">No center demand data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['branch_demand']])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-fire mr-1 text-danger"></i> Top Demanded Short Courses (F2F vs Online)</h4>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-sm btn-outline-primary">Catalog</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Course</th><th>Mode</th><th>F2F</th><th>Online</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['course_demand'] as $c)
                            <tr>
                                <td>{{ \Illuminate\Support\Str::limit($c->title, 34) }}</td>
                                <td><span class="badge badge-light text-capitalize">{{ str_replace('_', ' ', $c->delivery_mode ?? 'online') }}</span></td>
                                <td>{{ number_format($c->f2f_count) }}</td>
                                <td>{{ number_format($c->online_count) }}</td>
                                <td><strong>{{ number_format($c->enrollments_count) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No courses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['course_demand']])
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users mr-1 text-info"></i> Recent Applicant Leads</h4>
                <a href="{{ route('admin.campus.applicants.index') }}" class="btn btn-sm btn-outline-primary">All Applicants</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($analytics['recent_leads'] as $lead)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $lead->full_name }}</strong>
                                <div class="small text-muted">{{ $lead->branch?->name ?? '-' }} &bull; {{ $lead->enrollments->first()?->course?->title ?? 'Inquiry' }}</div>
                            </div>
                            <span class="badge badge-light">{{ ucfirst($lead->source ?? 'admin') }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-3">No recent applicants.</li>
                    @endforelse
                </ul>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['recent_leads']])
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/admin/modules/chart.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($analytics['intake_chart']);
            const ctx = document.getElementById('marketingIntakeChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chart.labels,
                    datasets: [
                        { label: 'Applicants', data: chart.applicants, backgroundColor: '#013ba6' },
                        { label: 'Enrollments', data: chart.enrollments, backgroundColor: '#228ddd' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
@endpush
