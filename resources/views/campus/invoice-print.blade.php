<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} | {{ site_name() }}</title>
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    @include('campus.partials.brand-edge-theme')
</head>
<body class="lmsout-doc-body">
@php
    $brandPrimary = $brandPrimary ?? branding_settings('primary_color', '#013ba6');
    $brandSecondary = $brandSecondary ?? branding_settings('secondary_color', '#228ddd');
    $siteName = $siteName ?? site_name();
    $primaryLogoUrl = $primaryLogoUrl ?? branding_asset(
        ['primary_logo_path', 'logos.primary', 'dark_logo_path', 'logos.secondary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
    $secondaryLogoUrl = $secondaryLogoUrl ?? branding_asset(
        ['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
    $status = strtolower((string) ($invoice->payment_status ?? 'unpaid'));
    $statusBadgeClass = $status === 'paid'
        ? 'bg-success'
        : ($status === 'partial' ? 'bg-warning text-dark' : 'bg-danger');
@endphp

<div class="inv-sheet lmsout-doc-sheet">
    <div class="lmsout-doc-edge-top"></div>

    <div class="lmsout-doc-inner">
        <div class="lmsout-doc-watermark" aria-hidden="true">
            <img src="{{ $primaryLogoUrl }}" alt="">
        </div>

        <div class="lmsout-doc-content">
            <div class="lmsout-doc-header">
                <div class="lmsout-doc-side-left">
                    <div class="lmsout-doc-logo-frame">
                        <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }} Logo" class="lmsout-invoice-logo-left">
                    </div>
                    <div>
                        <h3 class="inv-brand lmsout-doc-brand-name">{{ $siteName }}</h3>
                        <p class="text-muted mb-1 small">{{ $invoice->branch?->name ?? 'Main Campus' }}{{ $invoice->branch?->phone ? ' • ' . $invoice->branch->phone : '' }}</p>
                        <small class="text-muted">Issued: {{ optional($invoice->issued_at ?? $invoice->created_at)->format('M d, Y') }}</small>
                    </div>
                </div>

                <div class="lmsout-doc-side-right">
                    <div>
                        <span class="lmsout-doc-type-badge">TUITION INVOICE</span>
                        <div class="fw-bold fs-5">{{ $invoice->invoice_number }}</div>
                        <div class="lmsout-doc-control-pill">
                            Control No: {{ $invoice->control_number ?? 'Pending' }}
                        </div>
                        <div class="mt-1">
                            <span class="badge {{ $statusBadgeClass }}">{{ strtoupper($status) }}</span>
                        </div>
                    </div>
                    <div class="lmsout-doc-logo-frame lmsout-doc-logo-frame--right">
                        <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }} Official Seal" class="lmsout-invoice-logo-right">
                    </div>
                </div>
            </div>

            <div class="row mb-4 align-items-center">
                <div class="col-7">
                    <div class="lmsout-doc-section-label mb-1">Billed To</div>
                    <h5 class="mb-1 fw-bold">{{ $invoice->user?->name ?? $invoice->applicant?->full_name }}</h5>
                    @if ($invoice->applicant?->home_address || $invoice->user?->address)
                        <div class="small text-muted">{{ $invoice->applicant?->home_address ?? $invoice->user?->address }}</div>
                    @endif
                    <div class="small text-muted">
                        {{ $invoice->user?->email ?? $invoice->applicant?->email }}
                        @if ($invoice->applicant?->phone || $invoice->user?->phone)
                            &bull; {{ $invoice->applicant?->phone ?? $invoice->user?->phone }}
                        @endif
                    </div>
                </div>
                <div class="col-5 text-end">
                    @if (!empty($qr))
                        <div class="d-inline-block p-2 bg-white rounded border" style="border-color: rgba(1,59,166,0.22) !important;">
                            <img src="{{ $qr }}" alt="Verify Invoice QR" style="width:104px;height:104px;display:block;">
                        </div>
                        <div class="small text-muted mt-1">Scan to verify invoice</div>
                    @endif
                </div>
            </div>

            <table class="table table-bordered mb-4 lmsout-doc-table">
                <thead>
                    <tr>
                        <th>Course / Program</th>
                        <th>Cohort</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $invoice->course?->title ?? 'Short Course' }}</strong>
                            <div class="small text-muted">Plan: {{ ucfirst($invoice->installment_plan) }} &bull; Venue: {{ $invoice->enrollment?->venue?->name ?? 'TBA' }}</div>
                        </td>
                        <td>
                            {{ ucfirst((string) ($invoice->enrollment?->session ?? '-')) }}<br>
                            <small class="text-muted">{{ optional($invoice->enrollment?->start_date)->format('M d, Y') }}</small>
                        </td>
                        <td class="text-end fw-semibold">{{ currency_format((float) $invoice->total_amount) }}</td>
                    </tr>
                    @if ($invoice->discount_amount > 0)
                        <tr>
                            <td colspan="2" class="text-end">Discount / Staff Waiver</td>
                            <td class="text-end text-success">-{{ currency_format((float) $invoice->discount_amount) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="text-end fw-bold">Net Payable</td>
                        <td class="text-end fw-bold">{{ currency_format((float) $invoice->net_amount) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end">Total Paid</td>
                        <td class="text-end text-success fw-semibold">{{ currency_format((float) $invoice->paid_amount) }}</td>
                    </tr>
                    <tr class="lmsout-doc-balance-row">
                        <td colspan="2" class="text-end fw-bold">Balance Due</td>
                        <td class="text-end fw-bold fs-6">{{ currency_format((float) $invoice->balance) }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($invoice->paymentSchedules->isNotEmpty())
                <h6 class="fw-bold mb-2" style="color: {{ $brandPrimary }};">Installment Schedule</h6>
                <table class="table table-sm table-striped table-bordered mb-4">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Installment</th>
                            <th>Due Date</th>
                            <th class="text-end">Amount Due</th>
                            <th class="text-end">Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->paymentSchedules as $s)
                            <tr>
                                <td>{{ $s->installment_number }}</td>
                                <td>{{ $s->installment_label }}</td>
                                <td>{{ optional($s->due_date)->format('M d, Y') }}</td>
                                <td class="text-end">{{ currency_format((float) $s->amount_due) }}</td>
                                <td class="text-end">{{ currency_format((float) $s->amount_paid) }}</td>
                                <td>{{ ucfirst($s->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="lmsout-doc-footer-strip">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                    <span><strong>{{ $siteName }}</strong> &bull; Official Tuition Invoice</span>
                </div>
                <div class="text-center small">
                    {{ $invoice->branch?->name ?? 'Main Campus' }} &bull; Verify: {{ $invoice->invoice_number }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small">{{ optional($invoice->issued_at ?? $invoice->created_at)->format('d M Y') }}</span>
                    <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center no-print mt-4 pt-3 border-top">
                <button onclick="window.print()" class="btn btn-primary" style="background: {{ $brandPrimary }}; border-color: {{ $brandPrimary }};">
                    Print / Save PDF
                </button>
                <a href="javascript:window.close()" class="btn btn-outline-secondary">Close</a>
            </div>
        </div>
    </div>

    <div class="lmsout-doc-edge-bottom"></div>
</div>
</body>
</html>
