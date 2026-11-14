@extends('layouts.admin')

@section('title', 'Generate Installment Schedule')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.installments.index') }}">Installments</a></div>
        <div class="breadcrumb-item active">Generate Plan</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-calendar-plus mr-2 text-primary"></i>Generate Installment Payment Schedule</h4>
            <a href="{{ route('admin.campus.installments.index') }}" class="btn btn-secondary">Back</a>
        </div>
        <form action="{{ route('admin.campus.installments.generate') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Invoice <span class="text-danger">*</span></label>
                        <select name="invoice_id" class="form-control @error('invoice_id') is-invalid @enderror" required>
                            <option value="">Select outstanding invoice...</option>
                            @foreach ($invoices as $inv)
                                <option value="{{ $inv->id }}" {{ (string) old('invoice_id', $invoice?->id) === (string) $inv->id ? 'selected' : '' }}>
                                    {{ $inv->invoice_number }} &mdash; {{ $inv->applicant?->full_name ?? $inv->user?->name ?? 'Learner' }} (Balance: {{ currency_format((float) $inv->balance) }})
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label>Frequency Template <span class="text-danger">*</span></label>
                        <select name="template" class="form-control" required>
                            <option value="monthly" {{ old('template', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="weekly" {{ old('template') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="biweekly" {{ old('template') === 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                            <option value="quarterly" {{ old('template') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Number of Installments <span class="text-danger">*</span></label>
                        <input type="number" name="installments" class="form-control" min="1" max="24" value="{{ old('installments', 4) }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>First Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Custom Scheduled Amount <span class="small text-muted">(Optional — defaults to remaining balance)</span></label>
                        <input type="number" step="0.01" name="custom_amount" class="form-control" value="{{ old('custom_amount', $invoice?->balance) }}">
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary"><i class="fas fa-cogs mr-1"></i> Generate Schedule</button>
            </div>
        </form>
    </div>
@endsection
