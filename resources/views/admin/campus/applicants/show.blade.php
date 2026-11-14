@extends('layouts.admin')

@section('title', 'Applicant Details')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.applicants.index') }}">Applicants</a></div>
        <div class="breadcrumb-item active">{{ $applicant->full_name }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h4>1. Applicant Information</h4></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Full Name</th><td>{{ $applicant->full_name }}</td></tr>
                        <tr><th>Phone</th><td>{{ $applicant->phone }}</td></tr>
                        <tr><th>Email</th><td>{{ $applicant->email ?? '-' }}</td></tr>
                        <tr><th>Home Address</th><td>{{ $applicant->home_address ?? $applicant->address ?? '-' }}</td></tr>
                        <tr><th>Nationality</th><td>{{ $applicant->country?->display_name ?? '-' }}</td></tr>
                        <tr><th>Office / Occupation</th><td>{{ $applicant->office_of_work ?? '-' }} / {{ $applicant->occupation ?? '-' }}</td></tr>
                        <tr><th>Sponsor</th><td>{{ $applicant->name_of_sponsor ?? '-' }} ({{ $applicant->sponsor_phone ?? '-' }})</td></tr>
                        <tr><th>Education Level</th><td>{{ getEducationLevelLabel($applicant->education_level) }}</td></tr>
                        <tr><th>Staff Student</th><td>{{ $applicant->is_staff_student ? 'Yes (50% rule)' : 'No' }}
                            @if ($applicant->parent_staff_id_path)
                                &middot; <a href="{{ asset('storage/' . $applicant->parent_staff_id_path) }}" target="_blank">View ID</a>
                            @endif
                        </td></tr>
                        <tr><th>Discount</th><td>{{ $applicant->has_discount ? ($applicant->discount_reason . ' (' . $applicant->discount_percentage . '%)') : 'None' }}
                            @if ($applicant->discount_attachment_path)
                                &middot; <a href="{{ asset('storage/' . $applicant->discount_attachment_path) }}" target="_blank">Attachment</a>
                            @endif
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h4>2. Enrollments &amp; Invoices</h4></div>
                <div class="card-body">
                    @foreach ($applicant->enrollments as $e)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $e->course?->title ?? 'Course' }}</strong>
                                <span class="badge badge-info">{{ ucfirst($e->status) }}</span>
                            </div>
                            <small class="text-muted">Branch: {{ $e->branch?->name ?? '-' }} &bull; Venue: {{ $e->venue?->name ?? 'TBA' }} &bull; Session: {{ ucfirst((string) $e->session) }} &bull; {{ optional($e->start_date)->format('M d, Y') }} to {{ optional($e->end_date)->format('M d, Y') }}</small>
                        </div>
                    @endforeach
                    @foreach ($applicant->invoices as $inv)
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div>
                                <strong>{{ $inv->invoice_number }}</strong> <small class="text-muted">CN: {{ $inv->control_number ?? '-' }}</small><br>
                                <small>Total: {{ number_format($inv->net_amount, 2) }} | Paid: {{ number_format($inv->paid_amount, 2) }} | Balance: {{ number_format($inv->balance, 2) }}</small>
                            </div>
                            <a href="{{ route('admin.campus.invoices.show', $inv) }}" class="btn btn-sm btn-primary">Manage Invoice</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
