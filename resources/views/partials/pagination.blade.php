@if ($paginator->hasPages())
    <nav aria-label="Pages">
        <ul class="pagination">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="is-disabled">Précédente</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Précédente</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="is-disabled">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivante</a>
                @else
                    <span class="is-disabled">Suivante</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
