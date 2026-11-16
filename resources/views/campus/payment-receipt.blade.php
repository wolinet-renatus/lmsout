<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payment Receipt {{ $payment->receipt_number ?? '#' . $payment->id }} | {{ site_name() }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/bootstrap/css/bootstrap.min.css') }}">
    @include('campus.partials.brand-edge-theme')
</head>
<body class="lmsout-doc-body">
@php
    $brandPrimary = $brandPrimary ?? branding_settings('primary_color', '#013ba6');
    $siteName = $siteName ?? site_name();
    $primaryLogoUrl = $primaryLogoUrl ?? branding_asset(
        ['primary_logo_path', 'logos.primary', 'dark_logo_path', 'logos.secondary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
    $secondaryLogoUrl = $secondaryLogoUrl ?? branding_asset(
        ['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
@endphp

<div class="receipt-box lmsout-doc-sheet">
    <div class="lmsout-doc-edge-top"></div>

    <div class="lmsout-doc-inner">
        <div class="lmsout-doc-watermark" aria-hidden="true">
            <img src="{{ $primaryLogoUrl }}" alt="">
        </div>

        <div class="lmsout-doc-content">
            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                <span class="lmsout-doc-section-label">Official Payment Receipt</span>
                <div>
                    <button onclick="window.print()" class="btn btn-primary btn-sm" style="background: {{ $brandPrimary }}; border-color: {{ $brandPrimary }};">Print / Save PDF</button>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
                </div>
            </div>

            <div class="lmsout-doc-header">
                <div class="lmsout-doc-side-left">
                    <div class="lmsout-doc-logo-frame">
                        <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }} Logo" class="lmsout-invoice-logo-left">
                    </div>
                    <div>
                        <h4 class="lmsout-doc-brand-name mb-1">{{ $siteName }}</h4>
                        <div class="small text-muted">The Open University of Tanzania — Official Tuition Receipt</div>
                    </div>
                </div>

                <div class="lmsout-doc-side-right">
                    <div>
                        <span class="lmsout-doc-type-badge">PAYMENT RECEIPT</span>
                        <div><strong>Receipt #:</strong> <code>{{ $payment->receipt_number ?? ('RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT)) }}</code></div>
                        <div class="small text-muted">Date: {{ optional($payment->payment_date ?? $payment->created_at)->format('d M Y H:i') }}</div>
                    </div>
                    <div class="lmsout-doc-logo-frame lmsout-doc-logo-frame--right">
                        <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }} Official Seal" class="lmsout-invoice-logo-right">
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div><strong>Student:</strong> {{ $payment->applicant?->full_name ?? $payment->user?->name ?? $payment->invoice?->user?->name ?? $payment->enrollment?->user?->name ?? '-' }}</div>
                    <div><strong>Course:</strong> {{ $payment->invoice?->course?->title ?? $payment->enrollment?->course?->title ?? $payment->bundle?->title ?? '-' }}</div>
                    <div><strong>Center / Branch:</strong> {{ $payment->invoice?->branch?->name ?? $payment->enrollment?->branch?->name ?? 'Main Centre' }}</div>
                </div>
                <div class="col-md-6 text-md-right">
                    <div><strong>Invoice #:</strong> <code>{{ $payment->invoice?->invoice_number ?? '-' }}</code></div>
                    <div><strong>Control #:</strong> <code>{{ $payment->invoice?->control_number ?? '-' }}</code></div>
                    <div><strong>Method:</strong> {{ ucwords(str_replace('_', ' ', $payment->payment_method ?? 'offline')) }}</div>
                </div>
            </div>

            <table class="table table-bordered lmsout-doc-table">
                <thead>
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
                <div class="mt-3 p-3 bg-light rounded d-flex flex-wrap justify-content-between border" style="border-left: 4px solid {{ $brandPrimary }} !important;">
                    <span><strong>Total Invoice Net:</strong> {{ currency_format((float) $payment->invoice->net_amount) }}</span>
                    <span><strong>Total Paid To Date:</strong> {{ currency_format((float) $payment->invoice->paid_amount) }}</span>
                    <span><strong>Remaining Balance:</strong> {{ currency_format((float) $payment->invoice->balance) }}</span>
                </div>
            @endif

            <div class="lmsout-doc-footer-strip">
                <div class="d-flex align-items-center" style="gap:8px;">
                    <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                    <span><strong>{{ $siteName }}</strong> &bull; Official Tuition Receipt</span>
                </div>
                <div class="small">
                    Invoice: {{ $payment->invoice?->invoice_number ?? '-' }}
                </div>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <span class="small">{{ optional($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</span>
                    <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                </div>
            </div>
        </div>
    </div>

    <div class="lmsout-doc-edge-bottom"></div>
</div>
</body>
</html>
