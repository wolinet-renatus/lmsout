<div class="card border mt-3 mb-3">
    <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <strong>Account Security:</strong>
            @if ($account->is_blocked)
                <span class="badge badge-danger ml-1">Blocked</span>
                @if ($account->blocked_reason) <small class="text-muted ml-1">({{ $account->blocked_reason }})</small> @endif
            @elseif ($account->locked_until && $account->locked_until->isFuture())
                <span class="badge badge-warning ml-1">Locked until {{ $account->locked_until->format('H:i') }}</span>
            @else
                <span class="badge badge-success ml-1">Active</span>
            @endif
            @if ($account->force_password_reset)
                <span class="badge badge-info ml-1">Password Reset Pending</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
            @if ($account->is_blocked)
                <form action="{{ route('admin.user-security.unblock', $account) }}" method="POST" class="mr-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-unlock"></i> Unblock User</button>
                </form>
            @else
                <form action="{{ route('admin.user-security.block', $account) }}" method="POST" class="form-inline mr-2" onsubmit="return confirm('Block {{ $account->name }}?')">
                    @csrf
                    <input type="text" name="reason" class="form-control form-control-sm mr-1" placeholder="Block reason (optional)">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Block</button>
                </form>
            @endif
            <form action="{{ route('admin.user-security.force-reset', $account) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning"><i class="fas fa-key"></i> Force Password Change</button>
            </form>
        </div>
    </div>
</div>
