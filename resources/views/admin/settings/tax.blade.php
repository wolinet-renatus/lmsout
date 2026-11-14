@extends('layouts.admin')

@section('title', 'VAT, Tax & GST Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item">VAT, Tax & GST</div>
    </div>
@endsection

@section('main-content')
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">VAT, Tax & GST Settings</h2>
                    <p class="section-lead mb-0 mt-2">
                        Configure global tax rates, VAT/GST labels, tax registration number, and checkout calculation.
                    </p>
                </div>
                <div class="mb-3 mb-md-0">
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Tax Configuration</h4>
                    </div>
                    <form action="{{ route('admin.settings.tax.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <div class="control-label">Tax Calculation Status</div>
                                <label class="custom-switch mt-2 pl-0">
                                    <input type="checkbox" name="enabled" value="1" class="custom-switch-input"
                                        {{ old('enabled', $taxSettings['enabled'] ?? false) ? 'checked' : '' }}>
                                    <span class="custom-switch-indicator"></span>
                                    <span class="custom-switch-description">Enable VAT / Tax / GST calculation at checkout</span>
                                </label>
                                <small class="form-text text-muted">When enabled, the configured tax percentage will be added to course purchases at checkout.</small>
                            </div>

                            <div class="form-group">
                                <label for="name">Tax Label / Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $taxSettings['name'] ?? 'VAT') }}"
                                    placeholder="e.g. VAT, GST, Sales Tax" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Displayed on checkout summary, student invoices, and payment receipts (e.g., VAT, GST, Tax).</small>
                            </div>

                            <div class="form-group">
                                <label for="rate">Tax Rate (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" name="rate" id="rate"
                                        class="form-control @error('rate') is-invalid @enderror"
                                        value="{{ old('rate', $taxSettings['rate'] ?? 0) }}"
                                        placeholder="0.00" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Percentage of tax to apply on total checkout subtotal (e.g. 5.00 for 5%, 15.00 for 15%).</small>
                            </div>

                            <div class="form-group">
                                <label for="number">Tax Registration / GSTIN / VAT Number</label>
                                <input type="text" name="number" id="number"
                                    class="form-control @error('number') is-invalid @enderror"
                                    value="{{ old('number', $taxSettings['number'] ?? '') }}"
                                    placeholder="e.g. VAT-12345678, GSTIN09AAACH2234M1Z">
                                @error('number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Optional business tax identification number to display on invoices and receipts.</small>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Tax Information</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            OUT supports compliant tax collection for VAT, GST, or local Sales Tax.
                        </p>
                        <ul class="pl-3 text-muted">
                            <li>Tax is calculated dynamically during student checkout.</li>
                            <li>Payments record subtotal, applied tax amount, and rate for audit trails.</li>
                            <li>Free courses ($0) bypass tax computation automatically.</li>
                            <li>Applies across Web frontend and mobile APIs.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

