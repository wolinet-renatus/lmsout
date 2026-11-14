@include('admin.dashboards.partials.scope-header')

<div class="card mb-4">
    <div class="card-header">
        <h4><i class="fas fa-th-large mr-1 text-primary"></i> Workspace Overview</h4>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Active summary for <strong>{{ $analytics['scope']['scope_label'] ?? 'Assigned Center' }}</strong>.
        </p>

        <div class="row">
            @if (! is_null($analytics['applicants_count']))
                <div class="col-md-3 mb-3">
                    <div class="card border mb-0">
                        <div class="card-body">
                            <div class="small text-muted">Applicants</div>
                            <div class="h3 mb-0">{{ number_format($analytics['applicants_count']) }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @if (! is_null($analytics['active_classes_count']))
                <div class="col-md-3 mb-3">
                    <div class="card border mb-0">
                        <div class="card-body">
                            <div class="small text-muted">Active Classes</div>
                            <div class="h3 mb-0">{{ number_format($analytics['active_classes_count']) }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @if (! is_null($analytics['invoices_balance']))
                <div class="col-md-3 mb-3">
                    <div class="card border mb-0">
                        <div class="card-body">
                            <div class="small text-muted">Invoiced Balance</div>
                            <div class="h3 mb-0">{{ currency_format($analytics['invoices_balance']) }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @if (! is_null($analytics['ready_certificates_count']))
                <div class="col-md-3 mb-3">
                    <div class="card border mb-0">
                        <div class="card-body">
                            <div class="small text-muted">Ready Certificates</div>
                            <div class="h3 mb-0">{{ number_format($analytics['ready_certificates_count']) }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @if (! is_null($analytics['open_tickets_count']))
                <div class="col-md-3 mb-3">
                    <div class="card border mb-0">
                        <div class="card-body">
                            <div class="small text-muted">Open Tickets</div>
                            <div class="h3 mb-0">{{ number_format($analytics['open_tickets_count']) }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <hr>
        <div class="small text-muted mb-2"><strong>Active Permissions:</strong></div>
        <div class="d-flex flex-wrap" style="gap: 6px;">
            @forelse ($analytics['permissions'] ?? [] as $perm)
                <span class="badge badge-light border">{{ $perm }}</span>
            @empty
                <span class="text-muted">No granular permissions granted.</span>
            @endforelse
        </div>
    </div>
</div>
