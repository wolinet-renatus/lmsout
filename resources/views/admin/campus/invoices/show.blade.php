@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.invoices.index') }}">Invoices</a></div>
        <div class="breadcrumb-item active">{{ $invoice->invoice_number }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $invoice->invoice_number }}</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.campus.invoices.print', $invoice) }}" target="_blank" class="btn btn-info"><i class="fas fa-print"></i> Printable Invoice (QR)</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Student / Applicant</p>
                            <h6>{{ $invoice->user?->name ?? $invoice->applicant?->full_name }}</h6>
                            <small>{{ $invoice->user?->email ?? $invoice->applicant?->email }} &bull; {{ $invoice->applicant?->phone ?? $invoice->user?->phone }}</small>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <p class="mb-1 text-muted">GePG Control Number</p>
                            <h5 class="text-primary font-weight-bold mb-1">{{ $invoice->control_number ?? 'Not Issued' }}</h5>
                            @if ($invoice->payment_status !== 'paid')
                                <form action="{{ route('admin.campus.invoices.control-number', $invoice) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Regenerate Control No.</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>Course</th><td>{{ $invoice->course?->title ?? '-' }}</td>
                                <th>Branch / Venue</th><td>{{ $invoice->branch?->name ?? '-' }} / {{ $invoice->enrollment?->venue?->name ?? 'TBA' }}</td>
                            </tr>
                            <tr>
                                <th>Gross Fee</th><td>{{ number_format($invoice->total_amount, 2) }}</td>
                                <th>Discount</th><td>{{ number_format($invoice->discount_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Net Payable</th><td><strong>{{ number_format($invoice->net_amount, 2) }}</strong></td>
                                <th>Paid / Balance</th>
                                <td><span class="text-success">{{ number_format($invoice->paid_amount, 2) }}</span> / <strong class="text-danger">{{ number_format($invoice->balance, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>

                    <h6 class="text-primary font-weight-bold">Installment Schedule ({{ ucfirst($invoice->installment_plan) }})</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>#</th><th>Label</th><th>Due Date</th><th>Due</th><th>Paid</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($invoice->paymentSchedules as $s)
                                    <tr>
                                        <td>{{ $s->installment_number }}</td>
                                        <td>{{ $s->installment_label }}</td>
                                        <td>{{ optional($s->due_date)->format('M d, Y') }}</td>
                                        <td>{{ number_format($s->amount_due, 2) }}</td>
                                        <td>{{ number_format($s->amount_paid, 2) }}</td>
                                        <td><span class="badge badge-{{ $s->status === 'paid' ? 'success' : ($s->status === 'overdue' ? 'danger' : 'warning') }}">{{ ucfirst($s->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-primary font-weight-bold">Payment Transactions</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Date</th><th>Method</th><th>Receipt #</th><th>Amount</th><th>Verification</th><th class="text-right">Action</th></tr></thead>
                            <tbody>
                                @forelse ($invoice->payments as $p)
                                    <tr>
                                        <td>{{ $p->payment_date?->format('M d, Y') ?? $p->created_at->format('M d, Y') }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $p->payment_method)) }}</td>
                                        <td>
                                            {{ $p->receipt_number ?? '-' }}
                                            @if ($p->receipt_file)
                                                <br><a href="{{ asset('storage/' . $p->receipt_file) }}" target="_blank" class="small">View File</a>
                                            @endif
                                        </td>
                                        <td><strong>{{ number_format($p->amount, 2) }}</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $p->verification_status === 'verified' ? 'success' : ($p->verification_status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($p->verification_status ?? 'verified') }}
                                            </span>
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('admin.campus.invoices.receipt', $p) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Official Receipt">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            @if ($p->verification_status === 'pending')
                                                <form action="{{ route('admin.campus.invoices.payments.verify', $p) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                </form>
                                                <form action="{{ route('admin.campus.invoices.payments.verify', $p) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No payments recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if ($invoice->balance > 0)
                <div class="card">
                    <div class="card-header"><h4>Record Payment</h4></div>
                    <form action="{{ route('admin.campus.invoices.payments.store', $invoice) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Amount (Balance: {{ number_format($invoice->balance, 2) }}) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" max="{{ $invoice->balance }}" name="amount" class="form-control" value="{{ old('amount', $invoice->balance) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="control_number">Control Number (GePG)</option>
                                    <option value="bank_transfer">Bank Transfer / Deposit</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cash">Cash at Branch Desk</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Receipt Number</label>
                                    <input type="text" name="receipt_number" class="form-control" placeholder="e.g. OUT-001">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Receipt Upload (optional)</label>
                                <input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="form-group mb-0">
                                <label>Notes</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> Record &amp; Verify Payment</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>Invoice Fully Paid</h5>
                        <p class="mb-0">Paid on {{ optional($invoice->paid_at)->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
