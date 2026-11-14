@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                    <div>
                        <span class="badge bg-{{ $ticket->priority_badge }} text-uppercase me-1">{{ $ticket->priority }}</span>
                        <span class="badge bg-{{ $ticket->status_badge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        <h3 class="mt-2 mb-0">{{ $ticket->subject }}</h3>
                        <small class="text-muted">Ticket <code>{{ $ticket->ticket_number }}</code> &bull; Department: {{ ucfirst(str_replace('_', ' ', $ticket->department)) }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        @if($ticket->status !== 'closed')
                            <form action="{{ route('instructor.tickets.close', $ticket) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Close Ticket</button>
                            </form>
                        @endif
                        <a href="{{ route('instructor.tickets.index') }}" class="btn btn-outline-secondary btn-sm">Back to Tickets</a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                @endif

                <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                    @foreach($ticket->messages as $msg)
                        <div style="padding: 16px; border-radius: 10px; margin-bottom: 16px; border: 1px solid {{ $msg->is_admin_reply ? '#bfdbfe' : '#e2e8f0' }}; background: {{ $msg->is_admin_reply ? '#eff6ff' : '#f8fafc' }};">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>
                                    {{ $msg->user?->name ?? 'User' }}
                                    @if($msg->is_admin_reply)
                                        <span class="badge bg-primary ms-1">Support Team</span>
                                    @endif
                                </strong>
                                <span class="text-muted small">{{ $msg->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div style="white-space: pre-line; color: #1e293b;">{{ $msg->message }}</div>
                            @if($msg->attachments->count())
                                <div class="mt-2 pt-2 border-top">
                                    @foreach($msg->attachments as $att)
                                        <a href="{{ $att->url }}" target="_blank" class="btn btn-sm btn-light border me-1">
                                            <i class="fas fa-paperclip"></i> {{ $att->original_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <form action="{{ route('instructor.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    @csrf
                    <h5 class="mb-3">Post a Reply</h5>
                    <div class="mb-3">
                        <textarea name="message" rows="4" class="form-control" required placeholder="Type your reply..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="small font-weight-bold">Attach Files (Optional)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                    </div>
                    <button type="submit" class="theme-btn style-2" style="padding: 10px 24px; border-radius: 6px;">Send Reply</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
