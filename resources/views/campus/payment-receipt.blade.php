<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payment Receipt {{ $payment->receipt_number ?? '#' . $payment->id }} | {{ site_name() }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body { background: #f4f7fb; color: #1f2937; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .receipt-box { max-width: 820px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(1,59,166,.08); padding: 36px; border-top: 6px solid #013BA6; }
        @media print { body { background: #fff; } .receipt-box { box-shadow: none; margin: 0; border-radius: 0; } .d-print-none { display: none !important; } }
    </style>
</head>
<body>
<div class="receipt-box">
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <span class="text-muted small">Official Payment Receipt</span>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print / Save PDF</button>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
        <div>
            <h4 class="mb-1" style="color:#013BA6;font-weight:700;">{{ site_name() }}</h4>
            <div class="small text-muted">The Open University of Tanzania — Official Tuition Receipt</div>
        </div>
        <div class="text-right">
            <div><strong>Receipt #:</strong> <code>{{ $payment->receipt_number ?? ('RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT)) }}</code></div>
            <div class="small text-muted">Date: {{ optional($payment->payment_date ?? $payment->created_at)->format('d M Y H:i') }}</div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div><strong>Student:</strong> {{ $payment->applicant?->full_name ?? $payment->user?->name ?? $payment->invoice?->user?->name ?? '-' }}</div>
            <div><strong>Course:</strong> {{ $payment->invoice?->course?->title ?? '-' }}</div>
            <div><strong>Center / Branch:</strong> {{ $payment->invoice?->branch?->name ?? 'Main Centre' }}</div>
        </div>
        <div class="col-md-6 text-md-right">
            <div><strong>Invoice #:</strong> <code>{{ $payment->invoice?->invoice_number ?? '-' }}</code></div>
            <div><strong>Control #:</strong> <code>{{ $payment->invoice?->control_number ?? '-' }}</code></div>
            <div><strong>Method:</strong> {{ ucwords(str_replace('_', ' ', $payment->payment_method ?? 'offline')) }}</div>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="bg-light">
            <tr>
                <th>Transaction Reference</th>
                <th>Verification Status</th>
                <th>Notes</th>
                <th class="text-right">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>{{ $payment->transaction_id ?: ($payment->receipt_number ?: $payment->id) }}</code></td>
                <td><span class="badge badge-{{ $payment->verification_status === 'verified' ? 'success' : 'warning' }}">{{ ucfirst($payment->verification_status ?? $payment->status) }}</span></td>
                <td>{{ $payment->notes ?: 'Tuition Installment' }}</td>
                <td class="text-right font-weight-bold">{{ currency_format((float) $payment->amount) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($payment->invoice)
        <div class="mt-3 p-3 bg-light rounded d-flex justify-content-between">
            <span><strong>Total Invoice Net:</strong> {{ currency_format((float) $payment->invoice->net_amount) }}</span>
            <span><strong>Total Paid To Date:</strong> {{ currency_format((float) $payment->invoice->paid_amount) }}</span>
            <span><strong>Remaining Balance:</strong> {{ currency_format((float) $payment->invoice->balance) }}</span>
        </div>
    @endif
</div>
</body>
</html>
