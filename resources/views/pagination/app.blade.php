@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="app-pagination">
        <div class="app-pagination__meta">
            @if ($paginator->firstItem())
                Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> results
            @else
                Showing <strong>{{ $paginator->count() }}</strong> results
            @endif
        </div>

        <div class="app-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="app-pagination__link is-disabled" aria-disabled="true">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="app-pagination__link">{{ __('pagination.previous') }}</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="app-pagination__separator" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="app-pagination__current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="app-pagination__link" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="app-pagination__link">{{ __('pagination.next') }}</a>
            @else
                <span class="app-pagination__link is-disabled" aria-disabled="true">{{ __('pagination.next') }}</span>
            @endif
        </div>
    </nav>
@endif
