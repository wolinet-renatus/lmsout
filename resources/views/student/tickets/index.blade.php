@extends('layouts.student')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('student.partials.sidebar')

            <div class="dashboard-main">
                <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap" style="margin-bottom: 24px;">
                    <div class="section-title">
                        <h6>Help Desk</h6>
                        <h2>Support Tickets</h2>
                    </div>
                    <a href="{{ route('student.tickets.create') }}" class="theme-btn style-2" style="padding: 10px 20px; border-radius: 6px;">
                        <i class="fas fa-plus"></i> Open New Ticket
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mb-4">{{ session('success') }}</div>
                @endif

                <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                    @if($tickets->count())
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Subject</th>
                                        <th>Department</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Updated</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td><code>{{ $ticket->ticket_number }}</code></td>
                                            <td>
                                                <a href="{{ route('student.tickets.show', $ticket) }}" style="font-weight: 600; color: #0f172a;">
                                                    {{ $ticket->subject }}
                                                </a>
                                                <div style="font-size: 12px; color: #64748b;">{{ $ticket->messages_count }} message(s)</div>
                                            </td>
                                            <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $ticket->department) }}</td>
                                            <td><span class="badge bg-{{ $ticket->priority_badge }} text-uppercase">{{ $ticket->priority }}</span></td>
                                            <td><span class="badge bg-{{ $ticket->status_badge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span></td>
                                            <td>{{ optional($ticket->last_reply_at)->diffForHumans() }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('student.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">{{ $tickets->links() }}</div>
                    @else
                        <div style="padding: 48px; text-align: center; color: #64748b;">
                            <i class="fas fa-headset" style="font-size: 42px; color: #94a3b8; margin-bottom: 12px;"></i>
                            <h5>No Support Tickets Yet</h5>
                            <p>Need assistance from the platform admin team? Open a support ticket anytime.</p>
                            <a href="{{ route('student.tickets.create') }}" class="theme-btn style-2 mt-2" style="padding: 10px 20px; border-radius: 6px;">Create Ticket</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

