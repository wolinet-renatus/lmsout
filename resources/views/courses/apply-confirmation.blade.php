@extends('layouts.app')

@section('title', 'Application & Invoice ' . $invoice->invoice_number)

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
<section class="section-padding" style="background:#f5f8fd; padding:60px 0 80px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="lmsout-doc-sheet">
                    <div class="lmsout-doc-edge-top"></div>
                    <div class="lmsout-doc-inner">
                        <div class="lmsout-doc-watermark" aria-hidden="true">
                            <img src="{{ $primaryLogoUrl }}" alt="">
                        </div>
                        <div class="lmsout-doc-content">
                        <div class="lmsout-doc-header mb-4">
                            <div class="lmsout-doc-side-left">
                                <div class="lmsout-doc-logo-frame">
                                    <img src="{{ $primaryLogoUrl }}" alt="{{ $siteName }} Logo" class="lmsout-invoice-logo-left">
                                </div>
                                <div>
                                    <span class="badge bg-success px-3 py-1 mb-2">Application Recorded</span>
                                    <h4 class="lmsout-doc-brand-name mb-1">{{ $invoice->course?->title }}</h4>
                                    <p class="text-muted mb-0 small">{{ $invoice->branch?->name ?? 'Campus Delivery' }}</p>
                                </div>
                            </div>
                            <div class="lmsout-doc-side-right">
                                <div class="lmsout-doc-logo-frame lmsout-doc-logo-frame--right">
                                    <img src="{{ $secondaryLogoUrl }}" alt="{{ $siteName }} Seal" class="lmsout-invoice-logo-right">
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded mb-4" style="background:#eef4ff; border:1px solid #cddffe;">
                            <div class="row text-center">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <small class="text-muted d-block">Invoice Number</small>
                                    <strong class="fs-5">{{ $invoice->invoice_number }}</strong>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <small class="text-muted d-block">GePG Control Number</small>
                                    <strong class="fs-5 text-primary">{{ $invoice->control_number }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Balance Due</small>
                                    <strong class="fs-5 text-danger">{{ currency_format($invoice->balance) }}</strong>
                                </div>
                            </div>
                        </div>

                        @if (session('generated_campus_password'))
                            <div class="alert alert-info">
                                Your Student Portal account was created for <strong>{{ $invoice->user?->email }}</strong>.<br>
                                Temporary password: <code>{{ session('generated_campus_password') }}</code>
                            </div>
                        @endif

                        @if ($invoice->paymentSchedules->isNotEmpty())
                            <h6 class="fw-bold mb-2">Installment Schedule ({{ ucfirst($invoice->installment_plan) }})</h6>
                            <table class="table table-sm table-bordered mb-4">
                                <thead><tr><th>Installment</th><th>Due Date</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach ($invoice->paymentSchedules as $s)
                                        <tr>
                                            <td>{{ $s->installment_label }}</td>
                                            <td>{{ optional($s->due_date)->format('M d, Y') }}</td>
                                            <td>{{ currency_format($s->amount_due) }}</td>
                                            <td>{{ ucfirst($s->status) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="{{ route('courses.apply.print', $invoice->id) }}" target="_blank" class="btn btn-outline-primary">
                                <i class="fa-solid fa-print me-1"></i> Print / Save Invoice (QR)
                            </a>
                            @if ($invoice->balance > 0 && $invoice->course?->allow_online_f2f_payment)
                                <a href="{{ route('courses.checkout', ['id' => $invoice->course_id, 'invoice_id' => $invoice->id]) }}" class="btn btn-success">
                                    <i class="fa-solid fa-credit-card me-1"></i> Pay Installment Online
                                </a>
                            @endif
                            @auth
                                <a href="{{ route('student.campus.invoices.index') }}" class="btn btn-primary">
                                    <i class="fa-solid fa-file-invoice me-1"></i> Open in Student Portal
                                </a>
                            @endauth
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
