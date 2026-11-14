@if (isset($paginator) && $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $paginator->total() > 0)
    <div class="px-3 py-2 bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted">
            Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> of <strong>{{ number_format($paginator->total()) }}</strong>
        </span>
        @if ($paginator->hasPages())
            <div class="out-portal-card-pagination mb-0">
                {!! $paginator->onEachSide(1)->links('pagination::bootstrap-5') !!}
            </div>
        @endif
    </div>
@endif
