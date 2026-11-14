@extends('layouts.student')

@section('title', 'Campus Invoices & Control Numbers')

@section('content')
<div class="out-page-header mb-4">
    <h1 class="out-page-title">Campus Invoices &amp; Control Numbers</h1>
    <p class="out-page-sub text-muted">View your GePG Control Numbers, installment due dates, upload bank/cash receipts, or pay online.</p>
</div>

@forelse ($invoices as $inv)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0">{{ $inv->course?->title ?? 'Short Course' }}</h5>
                <small class="text-muted">{{ $inv->invoice_number }} &bull; {{ $inv->branch?->name ?? 'Campus' }} &bull; Plan: {{ ucfirst($inv->installment_plan) }}</small>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                @if (empty($inv->control_number) && $inv->balance > 0)
                    <form action="{{ route('student.campus.invoices.control-number', $inv->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-hashtag me-1"></i> Generate Control #
                        </button>
                    </form>
                @endif
                <a href="{{ route('courses.apply.print', $inv->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-print me-1"></i> Print Invoice
                </a>
                @if ($inv->balance > 0 && $inv->course?->allow_online_f2f_payment)
                    <a href="{{ route('courses.checkout', ['id' => $inv->course_id, 'invoice_id' => $inv->id]) }}" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-credit-card me-1"></i> Pay Installment Online
                    </a>
                @endif
                @if ((float) $inv->paid_amount <= 0 && $inv->enrollment)
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
                <div class="col-md-3"><small class="text-muted d-block">Control Number</small><strong class="text-primary fs-6">{{ $inv->control_number ?? '-' }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Net Fee</small><strong>{{ number_format($inv->net_amount, 2) }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Paid</small><strong class="text-success">{{ number_format($inv->paid_amount, 2) }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Balance</small><strong class="text-danger">{{ number_format($inv->balance, 2) }}</strong></div>
            </div>

            @if ($inv->paymentSchedules->isNotEmpty())
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Installment</th><th>Due Date</th><th>Amount Due</th><th>Paid</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($inv->paymentSchedules as $s)
                                <tr>
                                    <td>{{ $s->installment_label }}</td>
                                    <td>{{ optional($s->due_date)->format('M d, Y') }}</td>
                                    <td>{{ number_format($s->amount_due, 2) }}</td>
                                    <td>{{ number_format($s->amount_paid, 2) }}</td>
                                    <td><span class="badge bg-{{ $s->status === 'paid' ? 'success' : ($s->status === 'overdue' ? 'danger' : 'warning text-dark') }}">{{ ucfirst($s->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($inv->payments->isNotEmpty())
                <h6 class="fw-bold small text-uppercase text-muted">Submitted Receipts / Payments</h6>
                <ul class="list-group list-group-flush mb-3">
                    @foreach ($inv->payments as $p)
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center small">
                            <span>{{ $p->receipt_number ?? '-' }} ({{ ucwords(str_replace('_', ' ', $p->payment_method)) }}) &mdash; <strong>{{ number_format($p->amount, 2) }}</strong></span>
                            <span>
                                <a href="{{ route('student.campus.invoices.receipt.show', $p) }}" target="_blank" class="btn btn-xs btn-outline-primary me-2">Receipt</a>
                                <span class="badge bg-{{ $p->verification_status === 'verified' ? 'success' : ($p->verification_status === 'rejected' ? 'danger' : 'warning text-dark') }}">{{ ucfirst($p->verification_status ?? 'verified') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($inv->balance > 0)
                <form action="{{ route('student.campus.invoices.receipt', $inv) }}" method="POST" enctype="multipart/form-data" class="p-3 rounded bg-light border">
                    @csrf
                    <h6 class="fw-bold mb-2">Upload Bank / Control Number Receipt</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Amount Paid</label>
                            <input type="number" step="0.01" min="1" max="{{ $inv->balance }}" name="amount" class="form-control form-control-sm" value="{{ $inv->balance }}" required>
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
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">You have no campus / installment invoices yet.</div></div>
@endforelse
@endsection
