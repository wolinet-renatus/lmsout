@include('admin.dashboards.partials.scope-header')

<div class="stats-grid-v2">
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info-v2">
            <span class="label">Branch Collected Revenue</span>
            <span class="value">{{ currency_format($analytics['kpis']['collected_amount']) }}</span>
            <span class="trend up">{{ number_format($analytics['kpis']['paid_invoices_count']) }} paid invoices</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-primary"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-info-v2">
            <span class="label">Outstanding Receivables</span>
            <span class="value">{{ currency_format($analytics['kpis']['outstanding_balance']) }}</span>
            <span class="trend">{{ number_format($analytics['kpis']['partial_invoices_count']) }} partial &bull; {{ number_format($analytics['kpis']['unpaid_invoices_count']) }} unpaid</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-violet"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info-v2">
            <span class="label">Overdue Installments</span>
            <span class="value">{{ currency_format($analytics['kpis']['overdue_amount']) }}</span>
            <span class="trend down">{{ number_format($analytics['kpis']['overdue_count']) }} schedules overdue</span>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-icon-v2 bg-secondary"><i class="fas fa-receipt"></i></div>
        <div class="stat-info-v2">
            <span class="label">Bank Slips &amp; Waivers</span>
            <span class="value">{{ number_format($analytics['kpis']['pending_verifications_count']) }} Pending</span>
            <span class="trend">Waivers: {{ currency_format($analytics['kpis']['discount_waivers']) }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card-v2 h-100">
            <div class="header"><h3>Center Collection Trend (12 Months)</h3></div>
            <div class="chart-container" style="position: relative; height:280px; width:100%">
                <canvas id="accountantChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-hourglass-half mr-1 text-warning"></i> Pending Slip Verifications</h4>
                <a href="{{ route('admin.payments.offline-pending') }}" class="btn btn-sm btn-outline-warning">Verify</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($analytics['pending_verifications'] as $p)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $p->applicant?->full_name ?? $p->user?->name ?? 'Learner' }}</strong>
                                <div class="small text-muted">{{ $p->invoice?->invoice_number ?? '-' }} &bull; {{ $p->receipt_number ?? $p->payment_method }}</div>
                            </div>
                            <span class="badge badge-warning">{{ currency_format($p->amount) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-3">No pending verifications in this center.</li>
                    @endforelse
                </ul>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['pending_verifications']])
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-clock mr-1 text-danger"></i> Overdue Installments Queue</h4>
                <a href="{{ route('admin.campus.installments.index', ['filter' => 'overdue']) }}" class="btn btn-sm btn-outline-danger">Follow Up</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Student</th><th>Invoice</th><th>Due Date</th><th>Due Balance</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['overdue_schedules'] as $sch)
                            <tr>
                                <td>{{ $sch->invoice?->applicant?->full_name ?? '-' }}</td>
                                <td><code>{{ $sch->invoice?->invoice_number ?? '-' }}</code></td>
                                <td class="text-danger">{{ optional($sch->due_date)->format('d M Y') }}</td>
                                <td class="font-weight-bold">{{ currency_format(max(0, $sch->amount_due - $sch->amount_paid)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No overdue installments in this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.dashboards.partials.card-pagination', ['paginator' => $analytics['overdue_schedules']])
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-barcode mr-1 text-info"></i> Recent GePG Invoices &amp; Control Nos</h4>
                <a href="{{ route('admin.campus.invoices.index') }}" class="btn btn-sm btn-outline-primary">All Invoices</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Control No</th><th>Applicant</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($analytics['recent_invoices'] as $inv)
                            <tr>
                                <td><a href="{{ route('admin.campus.invoices.show', $inv) }}"><code>{{ $inv->invoice_number }}</code></a></td>
                                <td><code>{{ $inv->control_number ?? '-' }}</code></td>
                                <td>{{ $inv->applicant?->full_name ?? '-' }}</td>
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
</div>

@push('scripts')
    <script src="{{ asset('assets/admin/modules/chart.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = @json($analytics['collection_chart']);
            const ctx = document.getElementById('accountantChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chart.labels,
                    datasets: [{ label: 'Collected Revenue', data: chart.revenue, backgroundColor: '#013ba6' }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
@endpush
