@extends('layouts.admin')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Support Tickets</a></div>
        <div class="breadcrumb-item">{{ $ticket->ticket_number }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">{{ $ticket->subject }}</h4>
                        <span class="text-muted small"><code>{{ $ticket->ticket_number }}</code> &bull; Opened {{ $ticket->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="badge badge-{{ $ticket->priority_badge }} text-uppercase mr-1">{{ $ticket->priority }}</span>
                        <span class="badge badge-{{ $ticket->status_badge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 550px; overflow-y: auto; background: #f8fafc;">
                    @foreach ($ticket->messages as $msg)
                        <div class="p-3 mb-3 rounded border {{ $msg->is_admin_reply ? 'bg-white border-primary ml-4' : 'bg-white mr-4' }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ $msg->user?->name ?? 'User' }}</strong>
                                    @if ($msg->is_admin_reply)
                                        <span class="badge badge-primary ml-1">Admin Support</span>
                                    @else
                                        <span class="badge badge-secondary text-uppercase ml-1">{{ $ticket->sender_role }}</span>
                                    @endif
                                </div>
                                <span class="text-muted small">{{ $msg->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div style="white-space: pre-line; color: #1e293b;">{{ $msg->message }}</div>

                            @if ($msg->attachments->count())
                                <div class="mt-2 pt-2 border-top">
                                    <span class="small font-weight-bold text-muted d-block mb-1"><i class="fas fa-paperclip"></i> Attachments:</span>
                                    @foreach ($msg->attachments as $att)
                                        <a href="{{ $att->url }}" target="_blank" class="btn btn-sm btn-light border mr-1 mb-1">
                                            <i class="fas fa-file-download mr-1 text-primary"></i> {{ $att->original_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Post Admin Reply</h4>
                </div>
                <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Message <span class="text-danger">*</span></label>
                            <textarea name="message" rows="5" class="form-control" required placeholder="Write your response to {{ $ticket->user?->name }}..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Attachments (Optional)</label>
                                <input type="file" name="attachments[]" class="form-control" multiple>
                                <small class="text-muted">Up to 5 files (Images, PDF, ZIP, DOC - max 10MB each).</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Update Ticket Status After Reply</label>
                                <select name="status" class="form-control">
                                    <option value="answered" selected>Answered</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send Reply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Ticket Details &amp; Priority</h4>
                </div>
                <form action="{{ route('admin.tickets.status', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="mb-2"><strong>Requester:</strong> {{ $ticket->user?->name }} ({{ ucfirst($ticket->sender_role) }})</p>
                        <p class="mb-2"><strong>Email:</strong> {{ $ticket->user?->email }}</p>
                        <p class="mb-3"><strong>Department:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', $ticket->department) }}</span></p>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="answered" {{ $ticket->status === 'answered' ? 'selected' : '' }}>Answered</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label>Priority Level</label>
                            <select name="priority" class="form-control">
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-outline-primary btn-block">Update Status / Priority</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

