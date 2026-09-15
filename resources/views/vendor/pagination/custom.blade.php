@if ($paginator->hasPages())
    <nav class="page-pagination" aria-label="{{ __('Pagination Navigation') }}">
        <span class="page-counter">
            @if ($paginator->firstItem())
                Menampilkan {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} dari {{ $paginator->total() }} hasil
            @else
                {{ $paginator->count() }} hasil
            @endif
        </span>
        <div class="page-btns">
            @if ($paginator->onFirstPage())
                <span class="page-btn disabled" aria-disabled="true">&laquo; Sebelumnya</span>
            @else
                <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page-btn disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &raquo;</a>
            @else
                <span class="page-btn disabled" aria-disabled="true">Berikutnya &raquo;</span>
            @endif
        </div>
    </nav>
@endif