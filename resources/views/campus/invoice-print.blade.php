<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }} | {{ site_name() }}</title>
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    <style>
        body { background:#f4f7fc; color:#1c2536; padding:30px 15px; font-family: system-ui, -apple-system, sans-serif; }
        .inv-sheet { max-width:860px; margin:auto; background:#fff; border-radius:12px; padding:36px; box-shadow:0 8px 30px rgba(1,59,166,.08); }
        .inv-brand { color:#013BA6; font-weight:800; }
        @media print { body { background:#fff; padding:0; } .no-print { display:none !important; } .inv-sheet { box-shadow:none; } }
    </style>
</head>
<body>
<div class="inv-sheet">
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
        <div>
            <h3 class="inv-brand mb-1">{{ site_name() }}</h3>
            <p class="text-muted mb-0">{{ $invoice->branch?->name ?? 'Main Campus' }} &bull; {{ $invoice->branch?->phone ?? '' }}</p>
            <small class="text-muted">Issued: {{ optional($invoice->issued_at ?? $invoice->created_at)->format('M d, Y') }}</small>
        </div>
        <div class="text-end">
            <h4 class="mb-1">TUITION INVOICE</h4>
            <div class="fw-bold">{{ $invoice->invoice_number }}</div>
            <div>Control No: <strong class="text-primary">{{ $invoice->control_number ?? 'Pending' }}</strong></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-7">
            <small class="text-uppercase text-muted fw-bold">Billed To</small>
            <h5 class="mb-1">{{ $invoice->user?->name ?? $invoice->applicant?->full_name }}</h5>
            <div>{{ $invoice->applicant?->home_address ?? $invoice->user?->address ?? '' }}</div>
            <div>{{ $invoice->user?->email ?? $invoice->applicant?->email }} &bull; {{ $invoice->applicant?->phone ?? $invoice->user?->phone }}</div>
        </div>
        <div class="col-5 text-end">
            @if (!empty($qr))
                <img src="{{ $qr }}" alt="Verify Invoice QR" style="width:110px;height:110px;">
                <div class="small text-muted">Scan to verify invoice</div>
            @endif
        </div>
    </div>

    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr><th>Course / Program</th><th>Cohort</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $invoice->course?->title ?? 'Short Course' }}</strong>
                    <div class="small text-muted">Plan: {{ ucfirst($invoice->installment_plan) }} &bull; Venue: {{ $invoice->enrollment?->venue?->name ?? 'TBA' }}</div>
                </td>
                <td>{{ ucfirst((string) ($invoice->enrollment?->session ?? '-')) }}<br><small>{{ optional($invoice->enrollment?->start_date)->format('M d, Y') }}</small></td>
                <td class="text-end">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            @if ($invoice->discount_amount > 0)
                <tr><td colspan="2" class="text-end">Discount / Staff Waiver</td><td class="text-end text-success">-{{ number_format($invoice->discount_amount, 2) }}</td></tr>
            @endif
            <tr><td colspan="2" class="text-end fw-bold">Net Payable</td><td class="text-end fw-bold">{{ number_format($invoice->net_amount, 2) }}</td></tr>
            <tr><td colspan="2" class="text-end">Total Paid</td><td class="text-end text-success">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
            <tr class="table-primary"><td colspan="2" class="text-end fw-bold">Balance Due</td><td class="text-end fw-bold">{{ number_format($invoice->balance, 2) }}</td></tr>
        </tbody>
    </table>

    @if ($invoice->paymentSchedules->isNotEmpty())
        <h6 class="fw-bold">Installment Schedule</h6>
        <table class="table table-sm table-striped mb-4">
            <thead><tr><th>#</th><th>Installment</th><th>Due Date</th><th>Amount Due</th><th>Paid</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($invoice->paymentSchedules as $s)
                    <tr>
                        <td>{{ $s->installment_number }}</td>
                        <td>{{ $s->installment_label }}</td>
                        <td>{{ optional($s->due_date)->format('M d, Y') }}</td>
                        <td>{{ number_format($s->amount_due, 2) }}</td>
                        <td>{{ number_format($s->amount_paid, 2) }}</td>
                        <td>{{ ucfirst($s->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="d-flex justify-content-between align-items-center no-print mt-4 pt-3 border-top">
        <button onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
        <a href="javascript:window.close()" class="btn btn-outline-secondary">Close</a>
    </div>
</div>
</body>
</html>
