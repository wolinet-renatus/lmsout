@extends('layouts.app')

@section('title', 'Verify Invoice ' . $invoice->invoice_number)

@section('content')
<section class="section-padding" style="padding:70px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm text-center p-4">
                    <span class="badge bg-success mx-auto mb-3 px-3 py-2">Verified OUT LMS Invoice</span>
                    <h4>{{ $invoice->invoice_number }}</h4>
                    <p class="text-muted mb-3">Control Number: <strong>{{ $invoice->control_number ?? '-' }}</strong></p>
                    <table class="table table-sm text-start">
                        <tr><th>Student</th><td>{{ $invoice->user?->name ?? '-' }}</td></tr>
                        <tr><th>Course</th><td>{{ $invoice->course?->title ?? '-' }}</td></tr>
                        <tr><th>Branch</th><td>{{ $invoice->branch?->name ?? '-' }}</td></tr>
                        <tr><th>Net Amount</th><td>{{ number_format($invoice->net_amount, 2) }}</td></tr>
                        <tr><th>Paid</th><td>{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                        <tr><th>Status</th><td><strong>{{ ucfirst($invoice->payment_status) }}</strong></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
