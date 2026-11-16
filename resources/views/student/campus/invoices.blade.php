@extends('layouts.student')

@section('title', 'Campus Invoices & Control Numbers')

@section('content')
@php
    $liveDotStyle = 'width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-1 fw-semibold">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Campus Billing &amp; GePG
            </span>
            <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3 py-1 d-inline-flex align-items-center gap-2" id="invoicesLiveBadge">
                <span style="{{ $liveDotStyle }}"></span>
                Live Sync: <span id="invoicesLiveTimestamp">{{ now()->format('H:i:s') }}</span>
            </span>
        </div>
        <h1 class="h3 fw-bold mb-1" style="color:#0f172a;">Campus Invoices &amp; Control Numbers</h1>
        <p class="text-muted mb-0">Track GePG Control Numbers, installment schedules, payment proofs, and real-time verification status.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="refreshInvoicesLiveBtn" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-rotate me-1"></i> Sync Status Now
        </button>
        <a href="{{ route('student.payments') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-receipt me-1"></i> Full Payment Ledger
        </a>
    </div>
</div>

<!-- Real-Time Summary KPI Strip -->
<div class="out-stat-grid mb-4">
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-blue">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div>
            <div class="out-stat-value" id="inv-kpi-total">{{ $summary['total_count'] }}</div>
            <div class="out-stat-label">Total Invoices (<span id="inv-kpi-paid-count">{{ $summary['paid_count'] }}</span> Paid)</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-indigo">
            <i class="fa-solid fa-sack-dollar"></i>
        </div>
        <div>
            <div class="out-stat-value" id="inv-kpi-billed">{{ currency_format($summary['total_billed']) }}</div>
            <div class="out-stat-label">Total Net Billed</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="out-stat-value text-success" id="inv-kpi-paid">{{ currency_format($summary['total_paid']) }}</div>
            <div class="out-stat-label">Verified / Paid (<span id="inv-kpi-pending-review">{{ $summary['pending_verifications'] }}</span> Under Review)</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-amber">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div>
            <div class="out-stat-value text-danger" id="inv-kpi-balance">{{ currency_format($summary['outstanding_balance']) }}</div>
            <div class="out-stat-label">Outstanding Balance (<span id="inv-kpi-open-count">{{ $summary['open_count'] }}</span> Open)</div>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('student.campus.invoices.index') }}" class="row g-2 align-items-center" id="invoicesFilterForm">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Invoice Statuses</option>
                    <option value="unpaid" {{ $statusFilter === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Partial Paid</option>
                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid in Full</option>
                    <option value="overdue" {{ $statusFilter === 'overdue' ? 'selected' : '' }}>Overdue Installments</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="search" name="search" value="{{ $searchTerm }}" class="form-control form-control-sm" placeholder="Search by Invoice #, GePG Control #, Course, or Campus...">
            </div>
            <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1 flex-md-grow-0">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if ($statusFilter !== 'all' || $searchTerm !== '')
                    <a href="{{ route('student.campus.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

@forelse ($invoices as $inv)
    @php
        $net = (float) $inv->net_amount;
        $paid = (float) $inv->paid_amount;
        $balance = (float) $inv->balance;
        $progress = $net > 0 ? (int) min(100, round(($paid / $net) * 100)) : ($inv->payment_status === 'paid' ? 100 : 0);
        $invStatus = strtolower((string) ($inv->payment_status ?: ($balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'))));
        $statusBadgeClass = match ($invStatus) {
            'paid' => 'bg-success',
            'partial' => 'bg-info text-dark',
            default => 'bg-danger',
        };
        $accentBorderColor = $invStatus === 'paid' ? '#10b981' : ($invStatus === 'partial' ? '#228ddd' : '#013ba6');
        $venueName = $inv->enrollment?->venue?->name ?? $inv->enrollment?->courseAssignment?->venue?->name ?? 'Main Campus';
    @endphp
    <div class="card border-0 shadow-sm mb-4 lmsout-invoice-card" data-invoice-id="{{ $inv->id }}" style="border-radius:14px; border-top: 4px solid {{ $accentBorderColor }} !important;">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 border-bottom" style="border-radius:14px 14px 0 0;">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="mb-0 fw-bold" style="color:#0f172a;">{{ $inv->course?->title ?? 'Short Course' }}</h5>
                    <span class="badge {{ $statusBadgeClass }} inv-status-badge" data-invoice-status-badge="{{ $inv->id }}">{{ ucfirst($invStatus) }}</span>
                </div>
                <small class="text-muted">
                    <span class="fw-semibold text-dark">{{ $inv->invoice_number }}</span>
                    &bull; {{ $inv->branch?->name ?? 'Main Campus' }} ({{ $venueName }})
                    &bull; Plan: {{ ucfirst($inv->installment_plan) }}
                    @if ($inv->issued_at || $inv->created_at)
                        &bull; Issued {{ optional($inv->issued_at ?? $inv->created_at)->format('M d, Y') }}
                    @endif
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                @if (empty($inv->control_number) && $balance > 0)
                    <form action="{{ route('student.campus.invoices.control-number', $inv->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-hashtag me-1"></i> Generate Control #
                        </button>
                    </form>
                @endif
                <a href="{{ route('courses.apply.print', $inv->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-print me-1"></i> Print Invoice (QR)
                </a>
                @if ($balance > 0 && $inv->course?->allow_online_f2f_payment)
                    <a href="{{ route('courses.checkout', ['id' => $inv->course_id, 'invoice_id' => $inv->id]) }}" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-credit-card me-1"></i> Pay Installment Online
                    </a>
                @endif
                @if ($paid <= 0 && $inv->enrollment)
                    <form action="{{ route('student.campus.enrollments.destroy', $inv->enrollment_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this unpaid campus enrollment?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-times me-1"></i> Cancel Unpaid
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3 col-6">
                    <div class="p-2 px-3 rounded bg-light border h-100">
                        <small class="text-muted d-block">GePG Control Number</small>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <strong class="text-primary fs-6" data-invoice-control="{{ $inv->id }}">{{ $inv->control_number ?? ($invStatus === 'paid' ? 'Direct / Settled' : 'Pending') }}</strong>
                            @if ($inv->control_number)
                                <button type="button" class="btn btn-link btn-sm p-0 text-secondary" onclick="navigator.clipboard?.writeText('{{ $inv->control_number }}')" title="Copy Control Number">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2 px-3 rounded bg-light border h-100">
                        <small class="text-muted d-block">Net Fee</small>
                        <strong class="fs-6" data-invoice-net="{{ $inv->id }}">{{ currency_format($net) }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2 px-3 rounded bg-light border h-100">
                        <small class="text-muted d-block">Paid to Date</small>
                        <strong class="text-success fs-6" data-invoice-paid="{{ $inv->id }}">{{ currency_format($paid) }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-2 px-3 rounded bg-light border h-100">
                        <small class="text-muted d-block">Remaining Balance</small>
                        <strong class="{{ $balance > 0 ? 'text-danger' : 'text-success' }} fs-6" data-invoice-balance="{{ $inv->id }}">{{ currency_format($balance) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Live Payment Completion Progress -->
            <div class="mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Settlement Progress</span>
                    <span class="fw-semibold" data-invoice-progress-label="{{ $inv->id }}">{{ $progress }}% settled</span>
                </div>
                <div class="progress" style="height: 8px; border-radius: 999px; background:#e2e8f0;">
                    <div class="progress-bar {{ $progress >= 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar"
                        data-invoice-progress-bar="{{ $inv->id }}"
                        style="width: {{ $progress }}%; border-radius:999px;"
                        aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            @if ($inv->paymentSchedules->isNotEmpty())
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Installment Schedule</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Installment</th>
                                <th>Due Date</th>
                                <th class="text-end">Amount Due</th>
                                <th class="text-end">Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inv->paymentSchedules as $s)
                                @php
                                    $schedStatus = strtolower((string) $s->status);
                                    $schedBadge = match ($schedStatus) {
                                        'paid' => 'bg-success',
                                        'overdue' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $s->installment_label }}</td>
                                    <td>{{ optional($s->due_date)->format('M d, Y') ?? 'TBA' }}</td>
                                    <td class="text-end">{{ currency_format((float) $s->amount_due) }}</td>
                                    <td class="text-end">{{ currency_format((float) $s->amount_paid) }}</td>
                                    <td>
                                        <span class="badge {{ $schedBadge }}" data-schedule-status="{{ $s->id }}">{{ ucfirst($schedStatus) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($inv->payments->isNotEmpty())
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Submitted Receipts &amp; Payments</h6>
                <div class="list-group list-group-flush mb-3 border rounded">
                    @foreach ($inv->payments as $p)
                        @php
                            $vStatus = strtolower((string) ($p->verification_status ?: 'verified'));
                            $vBadge = match ($vStatus) {
                                'verified' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark',
                            };
                        @endphp
                        <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                            <div>
                                <span class="fw-semibold">{{ $p->receipt_number ?: ($p->pesapal_confirmation_code ?: ('TXN-' . $p->id)) }}</span>
                                <span class="text-muted">&bull; {{ ucwords(str_replace('_', ' ', (string) $p->payment_method)) }}</span>
                                &mdash; <strong>{{ currency_format((float) $p->amount) }}</strong>
                                @if ($p->payment_date || $p->created_at)
                                    <span class="text-muted">({{ optional($p->payment_date ?? $p->created_at)->format('d M Y') }})</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('student.campus.invoices.receipt.show', $p) }}" target="_blank" class="btn btn-xs btn-outline-primary">
                                    <i class="fa-solid fa-file-lines me-1"></i> Official Receipt
                                </a>
                                <span class="badge {{ $vBadge }}" data-payment-verification="{{ $p->id }}">{{ ucfirst($vStatus) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($balance > 0)
                <form action="{{ route('student.campus.invoices.receipt', $inv) }}" method="POST" enctype="multipart/form-data" class="p-3 rounded bg-light border">
                    @csrf
                    <h6 class="fw-bold mb-2">Upload Bank / Control Number Receipt</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Amount Paid</label>
                            <input type="number" step="0.01" min="1" max="{{ $balance }}" name="amount" class="form-control form-control-sm" value="{{ $balance }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Receipt Number</label>
                            <input type="text" name="receipt_number" class="form-control form-control-sm" placeholder="e.g. OUT-9910" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Receipt File (PDF/Image)</label>
                            <input type="file" name="receipt" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Submit Proof</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body text-center text-muted py-5">
            <i class="fa-solid fa-file-invoice fa-2x mb-2 opacity-50"></i>
            <p class="mb-0">No campus or installment invoices matched your filter criteria.</p>
        </div>
    </div>
@endforelse
@endsection

@push('scripts')
@php
    $liveEndpoint = route('student.campus.invoices.live', array_filter([
        'status' => $statusFilter !== 'all' ? $statusFilter : null,
        'search' => $searchTerm !== '' ? $searchTerm : null,
    ]));
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoint = @json($liveEndpoint);
    const syncBtn = document.getElementById('refreshInvoicesLiveBtn');
    const syncTimeEl = document.getElementById('invoicesLiveTimestamp');
    const statusBadgeMap = {
        paid: ['bg-success'],
        partial: ['bg-info', 'text-dark'],
        unpaid: ['bg-danger'],
        overdue: ['bg-danger'],
        verified: ['bg-success'],
        rejected: ['bg-danger'],
        pending: ['bg-warning', 'text-dark']
    };

    function applyBadge(el, key, label) {
        if (!el) return;
        const nextClasses = statusBadgeMap[key] || ['bg-secondary'];
        el.className = 'badge ' + nextClasses.join(' ');
        if (el.textContent.trim() !== label) {
            el.textContent = label;
            el.style.transition = 'transform 0.2s ease';
            el.style.transform = 'scale(1.06)';
            setTimeout(() => { el.style.transform = 'scale(1)'; }, 260);
        }
    }

    async function pollInvoicesStatus() {
        try {
            const res = await fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            if (syncTimeEl) syncTimeEl.textContent = data.synced_at;
            if (data.summary) {
                const map = {
                    'inv-kpi-total': data.summary.total_count,
                    'inv-kpi-paid-count': data.summary.paid_count,
                    'inv-kpi-billed': data.summary.total_billed_formatted,
                    'inv-kpi-paid': data.summary.total_paid_formatted,
                    'inv-kpi-pending-review': data.summary.pending_verifications,
                    'inv-kpi-balance': data.summary.outstanding_balance_formatted,
                    'inv-kpi-open-count': data.summary.open_count
                };
                Object.entries(map).forEach(([id, val]) => {
                    const node = document.getElementById(id);
                    if (node && val !== undefined) node.textContent = val;
                });
            }

            (data.invoices || []).forEach(inv => {
                applyBadge(document.querySelector(`[data-invoice-status-badge="${inv.id}"]`), inv.payment_status, inv.payment_status_label);
                const ctrlEl = document.querySelector(`[data-invoice-control="${inv.id}"]`);
                if (ctrlEl) ctrlEl.textContent = inv.control_number || (inv.payment_status === 'paid' ? 'Direct / Settled' : 'Pending');
                const paidEl = document.querySelector(`[data-invoice-paid="${inv.id}"]`);
                if (paidEl) paidEl.textContent = inv.paid_formatted;
                const balEl = document.querySelector(`[data-invoice-balance="${inv.id}"]`);
                if (balEl) balEl.textContent = inv.balance_formatted;
                const barEl = document.querySelector(`[data-invoice-progress-bar="${inv.id}"]`);
                if (barEl) barEl.style.width = inv.progress_percent + '%';
                const barLbl = document.querySelector(`[data-invoice-progress-label="${inv.id}"]`);
                if (barLbl) barLbl.textContent = inv.progress_percent + '% settled';

                (inv.schedules || []).forEach(s => {
                    applyBadge(document.querySelector(`[data-schedule-status="${s.id}"]`), s.status, s.status_label);
                });
                (inv.payments || []).forEach(p => {
                    applyBadge(document.querySelector(`[data-payment-verification="${p.id}"]`), p.verification_status, p.verification_label);
                });
            });
        } catch (err) {
            // Silent retry on transient network error
        }
    }

    if (syncBtn) {
        syncBtn.addEventListener('click', pollInvoicesStatus);
    }

    setInterval(() => {
        if (!document.hidden) {
            pollInvoicesStatus();
        }
    }, 10000);
});
</script>
@endpush
