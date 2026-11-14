@extends('layouts.student')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('student.partials.sidebar')

            <div class="dashboard-main">
                <div class="dashboard-header d-flex justify-content-between align-items-center" style="margin-bottom: 24px;">
                    <div class="section-title">
                        <h6>Support Desk</h6>
                        <h2>Open New Support Ticket</h2>
                    </div>
                    <a href="{{ route('student.tickets.index') }}" class="btn btn-outline-secondary">Back to Tickets</a>
                </div>

                <form action="{{ route('student.tickets.store') }}" method="POST" enctype="multipart/form-data" style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    @csrf
                    <div class="mb-3">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px;">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required placeholder="Brief summary of your question or issue">
                        @error('subject') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label style="font-weight: 600; display: block; margin-bottom: 6px;">Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-control" required>
                                <option value="general">General Inquiry</option>
                                <option value="technical">Technical Support</option>
                                <option value="billing">Billing &amp; Payments</option>
                                <option value="course_review">Course &amp; Certificate Support</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label style="font-weight: 600; display: block; margin-bottom: 6px;">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-control" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px;">Message Details <span class="text-danger">*</span></label>
                        <textarea name="message" rows="6" class="form-control" required placeholder="Describe your request in detail...">{{ old('message') }}</textarea>
                        @error('message') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px;">Attachments (Optional)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <small class="text-muted">Upload screenshots, receipts, or PDFs (max 10MB per file).</small>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="theme-btn style-2" style="padding: 10px 24px; border-radius: 6px;">Submit Support Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

