@extends('layouts.admin')

@section('title', 'Security & Activity Audit Logs')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">System Maintenance</div>
        <div class="breadcrumb-item active">Audit Logs</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history mr-2 text-primary"></i>System &amp; Security Audit Trail</h4>
        </div>
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="form-row align-items-end">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Search Description / Event</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search logs...">
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Channel</label>
                    <select name="log_name" class="form-control form-control-sm">
                        <option value="">All Channels</option>
                        @foreach ($logNames as $name)
                            <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>{{ ucfirst($name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Actor</label>
                    <select name="causer_id" class="form-control form-control-sm">
                        <option value="">Any User</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" {{ (string) request('causer_id') === (string) $actor->id ? 'selected' : '' }}>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Channel</th>
                            <th>Event</th>
                            <th>Description</th>
                            <th>Actor</th>
                            <th>Details / IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="small text-nowrap">{{ $activity->created_at->format('d M Y H:i:s') }}</td>
                                <td><span class="badge badge-{{ $activity->log_name === 'security' ? 'danger' : 'info' }}">{{ $activity->log_name ?? 'default' }}</span></td>
                                <td><code>{{ $activity->event ?? '-' }}</code></td>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->causer?->name ?? 'System / Guest' }}</td>
                                <td class="small text-muted">
                                    @if ($activity->properties && $activity->properties->has('ip'))
                                        IP: {{ $activity->properties->get('ip') }}
                                    @elseif ($activity->subject_type)
                                        {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No audit log entries recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($activities->hasPages())
            <div class="card-footer">{{ $activities->links() }}</div>
        @endif
    </div>
@endsection
