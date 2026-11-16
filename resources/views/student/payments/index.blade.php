@extends('layouts.student')

@section('title', __('student.payments_title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-1 fw-semibold">
                <i class="fa-solid fa-credit-card me-1"></i> {{ __('student.payments_title') }}
            </span>
            <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3 py-1 d-inline-flex align-items-center gap-2">
                <span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                Live Sync: <span id="paymentsLiveTimestamp">{{ now()->format('H:i:s') }}</span>
            </span>
        </div>
        <h1 class="h3 fw-bold mb-1" style="color:#0f172a;">{{ __('student.payment_history_title') }}</h1>
        <p class="text-muted mb-0">{{ __('student.payments_subtitle') }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="refreshPaymentsLiveBtn" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-rotate me-1"></i> Sync Status Now
        </button>
        <a href="{{ route('student.campus.invoices.index') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-file-invoice me-1"></i> Campus Invoices
        </a>
    </div>
</div>

<!-- Real-Time Payment Summary Cards -->
<div class="out-stat-grid mb-4">
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-blue">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <div class="out-stat-value" id="pay-kpi-total">{{ $summary['total_payments'] }}</div>
            <div class="out-stat-label">{{ __('student.total_payments') }}</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="out-stat-value text-success" id="pay-kpi-completed">{{ $summary['completed_payments'] }}</div>
            <div class="out-stat-label">{{ __('student.completed_status') }}</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-amber">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div>
            <div class="out-stat-value text-warning" id="pay-kpi-pending">{{ $summary['pending_payments'] }}</div>
            <div class="out-stat-label">{{ __('student.pending_status') }}</div>
        </div>
    </div>
    <div class="out-stat-card">
        <div class="out-stat-icon-wrap out-stat-icon-indigo">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div>
            <div class="out-stat-value" id="pay-kpi-amount">{{ currency_format($summary['total_amount']) }}</div>
            <div class="out-stat-label">{{ __('student.total_paid') }}</div>
        </div>
    </div>
</div>

<!-- Filter & Search Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('student.payments') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="student-payment-method" class="form-label small text-muted mb-1">{{ __('student.method_label') }}</label>
                <select id="student-payment-method" name="method" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($methodOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($methodFilter ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="student-payment-status" class="form-label small text-muted mb-1">{{ __('student.status_label') }}</label>
                <select id="student-payment-status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($statusFilter ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="student-payment-search" class="form-label small text-muted mb-1">{{ __('student.search_label') }}</label>
                <input type="search" id="student-payment-search" name="search" class="form-control form-control-sm"
                    value="{{ $searchTerm }}" placeholder="{{ __('student.search_payments_placeholder') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="fa-solid fa-search me-1"></i> {{ __('student.apply_filters') }}
                </button>
                @if ($methodFilter || $statusFilter || $searchTerm)
                    <a href="{{ route('student.payments') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('student.reset_filters') }}">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Payments Ledger Table -->
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;">
        <h5 class="mb-0 fw-bold" style="color:#0f172a;">Payment Transactions</h5>
        <span class="small text-muted">Showing {{ $payments->count() }} of {{ $payments->total() }} records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('student.course_column') }}</th>
                        <th>{{ __('student.method_column') }}</th>
                        <th>{{ __('student.status_column') }}</th>
                        <th>Verification</th>
                        <th>{{ __('student.amount_column') }}</th>
                        <th>{{ __('student.transaction_column') }}</th>
                        <th>{{ __('student.date_column') }}</th>
                        <th class="text-end">{{ __('student.receipt_column') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $statusBadge = match ($payment->status) {
                                \App\Models\Payment::STATUS_COMPLETED => 'bg-success',
                                \App\Models\Payment::STATUS_PENDING => 'bg-warning text-dark',
                                \App\Models\Payment::STATUS_FAILED, \App\Models\Payment::STATUS_REFUNDED => 'bg-danger',
                                default => 'bg-secondary',
                            };
                            $vStatus = strtolower((string) ($payment->verification_status ?: 'verified'));
                            $vBadge = match ($vStatus) {
                                'verified' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                default => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                            };
                        @endphp
                        <tr data-payment-row="{{ $payment->id }}">
                            <td>
                                <strong class="d-block" style="color:#0f172a;">{{ $payment->course_title }}</strong>
                                <div class="mt-1 d-flex flex-wrap align-items-center gap-2 small text-muted" data-pay-invoice-meta="{{ $payment->id }}">
                                    @if ($payment->invoice_number)
                                        <span>
                                            Invoice:
                                            @if ($payment->invoice_print_url)
                                                <a href="{{ $payment->invoice_print_url }}" target="_blank" class="text-decoration-none"><code>{{ $payment->invoice_number }}</code></a>
                                            @else
                                                <code>{{ $payment->invoice_number }}</code>
                                            @endif
                                        </span>
                                    @endif
                                    @if ($payment->control_number)
                                        <span>Control #: <code>{{ $payment->control_number }}</code></span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $payment->method_label }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge }}" data-pay-status="{{ $payment->id }}">{{ $payment->status_label }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $vBadge }}" data-pay-verification="{{ $payment->id }}">{{ $payment->verification_label }}</span>
                            </td>
                            <td>
                                <strong data-pay-amount="{{ $payment->id }}">{{ currency_format($payment->amount) }}</strong>
                                @if ($payment->discount_amount > 0)
                                    <div class="small text-success">
                                        <i class="fa-solid fa-tag"></i> -{{ currency_format($payment->discount_amount) }}
                                        @if ($payment->coupon)
                                            <span class="badge bg-light text-dark border ms-1">{{ $payment->coupon->code }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <code data-pay-txn="{{ $payment->id }}">{{ $payment->transaction_id ?? '—' }}</code>
                            </td>
                            <td class="small text-muted">{{ $payment->created_at_formatted ?? '—' }}</td>
                            <td class="text-end">
                                @if ($payment->receipt_action_url)
                                    <a href="{{ $payment->receipt_action_url }}" target="_blank" rel="noopener"
                                        class="btn btn-xs btn-outline-primary" data-pay-receipt-link="{{ $payment->id }}">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Official Receipt
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-receipt fa-2x mb-2 opacity-50 d-block"></i>
                                <strong class="d-block">{{ __('student.no_payments_title') }}</strong>
                                <span class="small">{{ __('student.no_payments_desc') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($payments->hasPages())
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="small text-muted">
                {{ __('student.showing_label') }} {{ $payments->firstItem() }} {{ __('student.to_label') }} {{ $payments->lastItem() }} {{ __('student.of_label') }}
                {{ $payments->total() }} {{ __('student.payments_count') }}
            </div>
            <div>
                {{ $payments->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@php
    $liveEndpoint = route('student.payments.live', array_filter([
        'method' => $methodFilter,
        'status' => $statusFilter,
        'search' => $searchTerm !== '' ? $searchTerm : null,
    ]));
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoint = @json($liveEndpoint);
    const syncBtn = document.getElementById('refreshPaymentsLiveBtn');
    const syncTimeEl = document.getElementById('paymentsLiveTimestamp');

    const statusBadgeClass = {
        completed: 'badge bg-success',
        pending: 'badge bg-warning text-dark',
        failed: 'badge bg-danger',
        refunded: 'badge bg-danger'
    };
    const verificationBadgeClass = {
        verified: 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25',
        rejected: 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
        pending: 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25'
    };

    async function pollPaymentsStatus() {
        try {
            const res = await fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            if (syncTimeEl) syncTimeEl.textContent = data.synced_at;
            if (data.summary) {
                const kpis = {
                    'pay-kpi-total': data.summary.total_payments,
                    'pay-kpi-completed': data.summary.completed_payments,
                    'pay-kpi-pending': data.summary.pending_payments,
                    'pay-kpi-amount': data.summary.total_amount_formatted
                };
                Object.entries(kpis).forEach(([id, val]) => {
                    const el = document.getElementById(id);
                    if (el && val !== undefined) el.textContent = val;
                });
            }

            (data.payments || []).forEach(p => {
                const st = document.querySelector(`[data-pay-status="${p.id}"]`);
                if (st) {
                    st.className = statusBadgeClass[p.status] || 'badge bg-secondary';
                    st.textContent = p.status_label;
                }
                const vf = document.querySelector(`[data-pay-verification="${p.id}"]`);
                if (vf) {
                    vf.className = verificationBadgeClass[p.verification_status] || verificationBadgeClass.pending;
                    vf.textContent = p.verification_label;
                }
                const amt = document.querySelector(`[data-pay-amount="${p.id}"]`);
                if (amt) amt.textContent = p.amount_formatted;
                const txn = document.querySelector(`[data-pay-txn="${p.id}"]`);
                if (txn) txn.textContent = p.transaction_id || '—';
                const invMeta = document.querySelector(`[data-pay-invoice-meta="${p.id}"]`);
                if (invMeta) {
                    invMeta.replaceChildren();
                    if (p.invoice_number) {
                        const span = document.createElement('span');
                        span.append('Invoice: ');
                        const code = document.createElement('code');
                        code.textContent = p.invoice_number;
                        if (p.invoice_print_url) {
                            const link = document.createElement('a');
                            link.href = p.invoice_print_url;
                            link.target = '_blank';
                            link.className = 'text-decoration-none';
                            link.appendChild(code);
                            span.appendChild(link);
                        } else {
                            span.appendChild(code);
                        }
                        invMeta.appendChild(span);
                    }
                    if (p.control_number) {
                        const ctrlSpan = document.createElement('span');
                        ctrlSpan.append('Control #: ');
                        const ctrlCode = document.createElement('code');
                        ctrlCode.textContent = p.control_number;
                        ctrlSpan.appendChild(ctrlCode);
                        invMeta.appendChild(ctrlSpan);
                    }
                }
            });
        } catch (err) {
            // Silent retry on transient error
        }
    }

    if (syncBtn) syncBtn.addEventListener('click', pollPaymentsStatus);
    setInterval(() => {
        if (!document.hidden) pollPaymentsStatus();
    }, 10000);
});
</script>
@endpush
