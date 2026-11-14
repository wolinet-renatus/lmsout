@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info-v2">
            <span class="label">Center Applicants</span>
            <span class="value">{{ number_format($analytics['kpis']['applicants_count']) }}</span>
            <span class="trend">{{ number_format($analytics['kpis']['enrollments_count']) }} branch enrollments</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-coins"></i></div>
        <div class="stat-info-v2">
            <span class="label">Branch Revenue Collected</span>
            <span class="value">{{ currency_format($analytics['kpis']['collected_amount']) }}</span>
            <span class="trend">Outstanding: {{ currency_format($analytics['kpis']['outstanding_balance']) }}</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info-v2">
            <span class="label">Active Classes &amp; Venues</span>
            <span class="value">{{ number_format($analytics['kpis']['active_classes']) }} Classes</span>
            <span class="trend">{{ number_format($analytics['kpis']['venues_count']) }} center venues</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-certificate"></i></div>
        <div class="stat-info-v2">
            <span class="label">Ready Certs &amp; Overdue</span>
            <span class="value">{{ number_format($analytics['kpis']['ready_certificates']) }} Certs</span>
            <span class="trend down">{{ number_format($analytics['kpis']['overdue_installments']) }} overdue schedules</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card-v2 h-100">
            <div class="header"><h3>Branch Collection &amp; Enrollment Trend (12 Months)</h3></div>
            <div class="chart-container" style="position: relative; height:280px; width:100%">
                <canvas id="branchManagerChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-door-open mr-1 text-primary"></i> Branch Venues</h4>
                <a href="{{ route('admin.campus.venues.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Venue</th><th>Cap.</th><th>Classes</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['venues'] as $v)
                            <tr>
                                <td>{{ $v->name }}</td>
                                <td>{{ $v->capacity }}</td>
                                <td><span class="badge badge-info">{{ $v->active_classes_count }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No venues in this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['venues']])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-file-invoice-dollar mr-1 text-success"></i> Recent Center Invoices</h4>
                <a href="{{ route('admin.campus.invoices.index') }}" class="btn btn-sm btn-outline-primary">View Invoices</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Applicant</th><th>Course</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['recent_invoices'] as $inv)
                            <tr>
                                <td><a href="{{ route('admin.campus.invoices.show', $inv) }}"><code>{{ $inv->invoice_number }}</code></a></td>
                                <td>{{ $inv->applicant?->full_name ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($inv->course?->title ?? '-', 24) }}</td>
                                <td>{{ currency_format($inv->balance) }}</td>
                                <td><span class="badge badge-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">{{ strtoupper($inv->payment_status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No invoices in this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['recent_invoices']])
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-user-check mr-1 text-info"></i> Recent Branch Applicants</h4>
                <a href="{{ route('admin.campus.applicants.index') }}" class="btn btn-sm btn-outline-primary">Intake Desk</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($analytics['recent_applicants'] as $app)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('admin.campus.applicants.show', $app) }}"><strong>{{ $app->full_name }}</strong></a>
                                <div class="small text-muted">{{ $app->phone }} &bull; {{ $app->enrollments->first()?->course?->title ?? '-' }}</div>
                            </div>
                            <span class="badge badge-light">{{ optional($app->created_at)->format('d M') }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-3">No recent applicants in this branch.</li>
                    @endforelse
                </ul>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['recent_applicants']])
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/admin/modules/chart.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($analytics['chart']);
            const ctx = document.getElementById('branchManagerChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chart.labels,
                    datasets: [{
                        label: 'Center Revenue',
                        data: chart.revenue,
                        borderColor: '#013ba6',
                        backgroundColor: 'rgba(1, 59, 166, 0.08)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
@endpush
