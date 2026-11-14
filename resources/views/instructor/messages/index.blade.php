@extends('layouts.instructor')

@section('title', 'Messages')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Messages</h2>
        <p class="text-muted small mb-0">Communicate directly with your students and manage message requests.</p>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link btn-sm px-3 py-2 {{ $tab === 'inbox' ? 'active' : 'bg-white border text-dark' }}"
           href="{{ route('instructor.messages.index') }}">
            <i class="fa-solid fa-inbox me-1"></i>Inbox
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link btn-sm px-3 py-2 {{ $tab === 'requests' ? 'active' : 'bg-white border text-dark' }}"
           href="{{ route('instructor.messages.requests') }}">
            <i class="fa-solid fa-user-clock me-1"></i>Requests
            @if(isset($requestCount) && $requestCount > 0)
                <span class="badge bg-danger ms-1">{{ $requestCount }}</span>
            @endif
        </a>
    </li>
</ul>

<!-- Conversations List -->
<div class="student-courses-container view-list">
    @forelse($conversations as $conv)
        <div class="student-course-row message-conv-item" data-conversation-id="{{ $conv->id }}">
            <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-primary-subtle text-primary">
                @if($conv->student && $conv->student->profile_photo)
                    <img src="{{ asset('storage/' . $conv->student->profile_photo) }}" alt="{{ $conv->student->name }}" loading="lazy" style="object-fit: cover;">
                @else
                    <span class="fw-bold fs-4">{{ strtoupper(substr(optional($conv->student)->name ?? 'S', 0, 1)) }}</span>
                @endif
            </div>

            <div class="student-course-info">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    @if(isset($conv->unread_count) && $conv->unread_count > 0)
                        <span class="badge rounded-pill bg-primary" style="font-size: 11px;">
                            {{ $conv->unread_count }} unread
                        </span>
                    @endif
                    <span class="text-muted small">
                        <i class="fa-regular fa-clock me-1"></i>{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : $conv->created_at->diffForHumans() }}
                    </span>
                </div>

                <h5 class="student-course-title mb-1">
                    <a href="{{ route('instructor.messages.show', $conv->id) }}" class="text-decoration-none text-dark">
                        {{ optional($conv->student)->name ?? 'Student' }}
                    </a>
                </h5>

                <p class="text-muted small mb-0 text-truncate" style="max-width: 560px;">
                    @if($conv->latestMessage)
                        @if($conv->latestMessage->sender_id === $user->id)
                            <span class="fw-semibold text-dark">You: </span>
                        @endif
                        @if($conv->latestMessage->body)
                            {{ Str::limit($conv->latestMessage->body, 80) }}
                        @else
                            <i class="fa-solid fa-paperclip me-1"></i>Attachment
                        @endif
                    @else
                        <span>No messages yet</span>
                    @endif
                </p>
            </div>

            <div class="student-course-actions">
                @if(!$conv->is_accepted && $tab === 'requests')
                    <button type="button" class="btn btn-success btn-sm accept-btn" data-id="{{ $conv->id }}">
                        <i class="fa-solid fa-check me-1"></i>Accept
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm decline-btn" data-id="{{ $conv->id }}" aria-label="Decline">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                @else
                    <a href="{{ route('instructor.messages.show', $conv->id) }}" class="btn btn-primary btn-sm px-3">
                        <i class="fa-solid fa-comments me-1"></i>Open Chat
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-5 px-3 bg-white border rounded-3">
            <div class="mb-3 text-muted">
                <i class="fa-solid fa-comments fa-2x"></i>
            </div>
            <h5 class="fw-semibold mb-1">{{ $tab === 'inbox' ? 'No conversations yet' : 'No pending requests' }}</h5>
            <p class="text-muted small mb-0">
                {{ $tab === 'inbox' ? 'Student messages will appear here.' : 'Message requests from non-enrolled students will appear here.' }}
            </p>
        </div>
    @endforelse
</div>

@if($conversations->hasPages())
    <div class="student-courses-pagination-bar mt-3">
        <div class="student-pagination-meta text-muted small">
            Showing {{ $conversations->firstItem() ?? 0 }} to {{ $conversations->lastItem() ?? 0 }} of {{ $conversations->total() }} conversations
        </div>
        <div>
            {{ $conversations->links() }}
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseMessagesUrl = @json(url('instructor/messages'));
    const csrfToken = @json(csrf_token());

    document.addEventListener('click', function (e) {
        const acceptBtn = e.target.closest('.accept-btn');
        if (acceptBtn) {
            const id = acceptBtn.dataset.id;
            const row = acceptBtn.closest('.message-conv-item');
            acceptBtn.disabled = true;
            acceptBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            fetch(baseMessagesUrl + '/' + id + '/accept', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Failed');
                if (row) row.remove();
                if (window.ToastMagic) ToastMagic.success('Request accepted');
            })
            .catch(function () {
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Accept';
                if (window.ToastMagic) ToastMagic.error('Failed to accept request');
            });
            return;
        }

        const declineBtn = e.target.closest('.decline-btn');
        if (declineBtn) {
            const id = declineBtn.dataset.id;
            const row = declineBtn.closest('.message-conv-item');
            if (!confirm('Decline this message request?')) return;
            declineBtn.disabled = true;

            fetch(baseMessagesUrl + '/' + id + '/decline', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Failed');
                if (row) row.remove();
                if (window.ToastMagic) ToastMagic.info('Request declined');
            })
            .catch(function () {
                declineBtn.disabled = false;
                if (window.ToastMagic) ToastMagic.error('Failed to decline request');
            });
        }
    });
});
</script>
@endpush
