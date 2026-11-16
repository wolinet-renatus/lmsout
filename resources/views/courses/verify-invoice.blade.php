@extends('layouts.app')

@section('title', 'Verify Invoice ' . $invoice->invoice_number)

@section('content')
@php
    $siteName = site_name();
    $primaryLogoUrl = branding_asset(
        ['primary_logo_path', 'logos.primary', 'dark_logo_path', 'logos.secondary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
    $secondaryLogoUrl = branding_asset(
        ['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
@endphp
@include('campus.partials.brand-edge-theme')
<section class="section-padding" style="padding:70px 0; background:#edf3fb;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="lmsout-doc-sheet">
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
                                        <h4 class="lmsout-doc-brand-name mb-1">{{ $siteName }}</h4>
                                        <span class="badge bg-success px-3 py-1">Verified Invoice</span>
                                    </div>
                                </div>
                                <div class="lmsout-doc-side-right">
                                    <div>
                                        <span class="lmsout-doc-type-badge">INVOICE VERIFICATION</span>
                                        <div class="fw-bold">{{ $invoice->invoice_number }}</div>
                                        <div class="lmsout-doc-control-pill">Control No: {{ $invoice->control_number ?? '-' }}</div>
                                    </div>
                                    <div class="lmsout-doc-logo-frame lmsout-doc-logo-frame--right">
                                        <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }} Official Seal" class="lmsout-invoice-logo-right">
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered table-sm text-start mb-3">
                                <tr><th style="width:32%;">Student</th><td>{{ $invoice->user?->name ?? '-' }}</td></tr>
                                <tr><th>Course</th><td>{{ $invoice->course?->title ?? '-' }}</td></tr>
                                <tr><th>Branch</th><td>{{ $invoice->branch?->name ?? '-' }}</td></tr>
                                <tr><th>Net Amount</th><td>{{ currency_format((float) $invoice->net_amount) }}</td></tr>
                                <tr><th>Paid</th><td>{{ currency_format((float) $invoice->paid_amount) }}</td></tr>
                                <tr><th>Balance</th><td>{{ currency_format((float) $invoice->balance) }}</td></tr>
                                <tr><th>Status</th><td><strong>{{ ucfirst($invoice->payment_status) }}</strong></td></tr>
                            </table>

                            <div class="lmsout-doc-footer-strip">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                                    <span><strong>{{ $siteName }}</strong></span>
                                </div>
                                <span class="small">Official Invoice Verification</span>
                                <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }}" class="lmsout-doc-footer-mini-logo">
                            </div>
                        </div>
                    </div>
                    <div class="lmsout-doc-edge-bottom"></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
