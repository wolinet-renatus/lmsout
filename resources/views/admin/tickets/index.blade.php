@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Support Tickets</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-ticket-alt"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Total Tickets</h4></div>
                    <div class="card-body">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-envelope-open-text"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Open</h4></div>
                    <div class="card-body">{{ $stats['open'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning"><i class="fas fa-spinner"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>In Progress</h4></div>
                    <div class="card-body">{{ $stats['in_progress'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="card-wrap">
                    <div class="card-header"><h4>Urgent Open</h4></div>
                    <div class="card-body">{{ $stats['urgent'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Student &amp; Instructor Support Tickets</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="mb-4">
                <div class="form-row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Ticket #, subject, user...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="role" class="form-control">
                            <option value="">All Senders</option>
                            <option value="student" {{ $role === 'student' ? 'selected' : '' }}>Students</option>
                            <option value="instructor" {{ $role === 'instructor' ? 'selected' : '' }}>Instructors</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="priority" class="form-control">
                            <option value="">All Priorities</option>
                            <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                            <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="answered" {{ $status === 'answered' ? 'selected' : '' }}>Answered</option>
                            <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex">
                        <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject &amp; Department</th>
                            <th>Sender</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Last Reply</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td><code>{{ $ticket->ticket_number }}</code></td>
                                <td>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-weight-bold text-dark">
                                        {{ $ticket->subject }}
                                    </a>
                                    <div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $ticket->department) }} &bull; {{ $ticket->messages_count }} msg(s)</div>
                                </td>
                                <td>
                                    <strong>{{ $ticket->user?->name ?? 'User' }}</strong>
                                    <span class="badge badge-light text-uppercase ml-1">{{ $ticket->sender_role }}</span>
                                    <div class="text-muted small">{{ $ticket->user?->email }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $ticket->priority_badge }} text-uppercase">{{ $ticket->priority }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $ticket->status_badge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                </td>
                                <td>{{ optional($ticket->last_reply_at)->diffForHumans() }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-reply"></i> View / Reply
                                    </a>
                                    <form action="{{ route('admin.tickets.destroy', $ticket) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete ticket {{ $ticket->ticket_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No support tickets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tickets->links('vendor.pagination.stisla-default') }}
        </div>
    </div>
@endsection

