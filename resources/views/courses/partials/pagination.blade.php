@if($courses->total() > 0)
<div class="edux-catalog-pagination">
    <span class="edux-catalog-pagination__info">
        Showing {{ $courses->firstItem() ?? 1 }} to {{ $courses->lastItem() ?? $courses->count() }} of {{ $courses->total() }} results
    </span>
    <ul class="edux-catalog-pagination__list">
        @if($courses->onFirstPage())
            <li><span class="page-numbers disabled"><i class="fa-solid fa-chevron-left"></i></span></li>
        @else
            <li><a class="page-numbers" href="{{ $courses->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a></li>
        @endif

        @foreach($courses->getUrlRange(1, max(1, $courses->lastPage())) as $page => $url)
            @if($page == $courses->currentPage())
                <li class="active"><span class="page-numbers">{{ $page }}</span></li>
            @else
                <li><a class="page-numbers" href="{{ $url }}">{{ $page }}</a></li>
            @endif
        @endforeach

        @if($courses->hasMorePages())
            <li><a class="page-numbers" href="{{ $courses->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a></li>
        @else
            <li><span class="page-numbers disabled"><i class="fa-solid fa-chevron-right"></i></span></li>
        @endif
    </ul>
</div>
@endif
