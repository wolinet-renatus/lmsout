@if (isset($paginator) && $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $paginator->total() > 0)
    <div class="card-footer bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap border-top" style="gap: 8px;">
        <span class="small text-muted">
            Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> of <strong>{{ number_format($paginator->total()) }}</strong>
        </span>
        @if ($paginator->hasPages())
            <div class="dashboard-card-pagination mb-0">
                {!! $paginator->onEachSide(1)->links('pagination::bootstrap-4') !!}
            </div>
        @endif
    </div>
@endif
