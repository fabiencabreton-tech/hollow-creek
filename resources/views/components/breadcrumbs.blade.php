@props(['items' => []])
{{-- $items : [libellé => adresse] ; la dernière entrée peut avoir une adresse nulle (page courante). --}}
<nav aria-label="Fil d’Ariane">
    <ol class="breadcrumbs">
        @foreach ($items as $label => $url)
            <li>
                @if ($url)
                    <a href="{{ $url }}">{{ $label }}</a>
                @else
                    <span aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
