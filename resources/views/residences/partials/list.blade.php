<div class="grid-cards">
    @foreach ($residences as $residence)
            <a class="tile" href="{{ route('residences.show', $residence) }}">
                <span class="tile__eyebrow">{{ $residence->kind->getLabel() }}</span>
                <span class="tile__title">{{ $residence->name }}</span>
                @if ($residence->summary)
                    <span class="tile__text">{{ $residence->summary }}</span>
                @endif
                <span class="tile__more">{{ $residence->residents_count }} / {{ $residence->capacity }} {{ $residence->capacity > 1 ? 'habitants' : 'habitant' }}@unless ($residence->is_open) · non libre @endunless</span>
            </a>
    @endforeach
</div>
