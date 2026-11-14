@forelse($tickets as $ticket)
    <div class="student-course-row" data-ticket-id="{{ $ticket->id }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-primary-subtle text-primary">
            <i class="fa-solid fa-headset fa-2x"></i>
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill bg-{{ $ticket->status_badge ?? 'secondary' }} text-uppercase" style="font-size: 11px;">
                    {{ str_replace('_', ' ', $ticket->status) }}
                </span>
                <span class="badge rounded-pill bg-{{ $ticket->priority_badge ?? 'info' }} text-uppercase" style="font-size: 11px;">
                    {{ $ticket->priority }}
                </span>
                <code class="small text-muted">#{{ $ticket->ticket_number }}</code>
            </div>

            <h5 class="student-course-title mb-2">
                <a href="{{ route('instructor.tickets.show', $ticket) }}" class="text-decoration-none text-dark">
                    {{ $ticket->subject }}
                </a>
            </h5>

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal text-capitalize">
                    <i class="fa-solid fa-building text-primary me-1"></i>{{ str_replace('_', ' ', $ticket->department) }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-comments text-info me-1"></i>{{ (int) $ticket->messages_count }} {{ (int) $ticket->messages_count === 1 ? 'Message' : 'Messages' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-clock text-secondary me-1"></i>Updated {{ optional($ticket->last_reply_at)->diffForHumans() ?? 'Recently' }}
                </span>
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('instructor.tickets.show', $ticket) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-eye me-1"></i>View Ticket
            </a>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-headset fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentStatus ?? 'all') !== 'all' || ($currentDepartment ?? 'all') !== 'all')
            <h5 class="fw-semibold mb-1">No tickets match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query, department, or status filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">No Support Tickets Yet</h5>
            <p class="text-muted small mb-3">Need assistance from the platform admin team? Open a support ticket anytime.</p>
            <a href="{{ route('instructor.tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Open New Ticket
            </a>
        @endif
    </div>
@endforelse

