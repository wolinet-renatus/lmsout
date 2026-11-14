@extends('layouts.instructor')

@section('title', 'Open New Support Ticket')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="mb-2">
            <a href="{{ route('instructor.tickets.index') }}" class="btn btn-light btn-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i>Back to Tickets
            </a>
        </div>
        <h2 class="h4 fw-bold mb-1">Open New Support Ticket</h2>
        <p class="text-muted small mb-0">Describe your question or issue and attach any relevant files for our support team.</p>
    </div>
</div>

<form action="{{ route('instructor.tickets.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white border rounded-3 p-3 p-md-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                    <span>Ticket Details</span>
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="subject"
                        class="form-control form-control-sm"
                        value="{{ old('subject') }}"
                        required
                        placeholder="Brief summary of your question or issue"
                    >
                    @error('subject')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="form-label small fw-semibold">Message Details <span class="text-danger">*</span></label>
                    <textarea
                        name="message"
                        rows="6"
                        class="form-control form-control-sm"
                        required
                        placeholder="Describe your request in detail..."
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-primary"></i>
                    <span>Routing &amp; Attachments</span>
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
                    <select name="department" class="form-select form-select-sm" required>
                        <option value="general" @selected(old('department') === 'general')>General Inquiry</option>
                        <option value="technical" @selected(old('department') === 'technical')>Technical Support</option>
                        <option value="billing" @selected(old('department') === 'billing')>Billing &amp; Payments</option>
                        <option value="course_review" @selected(old('department') === 'course_review')>Course Review &amp; Curriculum</option>
                        <option value="payout" @selected(old('department') === 'payout')>Payouts &amp; Commissions</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Priority <span class="text-danger">*</span></label>
                    <select name="priority" class="form-select form-select-sm" required>
                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                        <option value="high" @selected(old('priority') === 'high')>High</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small fw-semibold">Attachments (Optional)</label>
                    <input type="file" name="attachments[]" class="form-control form-control-sm" multiple>
                    <div class="form-text">Screenshots, receipts, or PDFs (max 10MB per file).</div>
                </div>
            </div>

            <div class="bg-white border rounded-3 p-3 p-md-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-sm py-2">
                        <i class="fa-solid fa-paper-plane me-1"></i>Submit Support Ticket
                    </button>
                    <a href="{{ route('instructor.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
