<a class="tile" href="{{ route('locations.show', $location) }}">
    @if ($location->imageUrl())
        <span class="tile__media"><img src="{{ $location->imageUrl() }}" alt="" loading="lazy"></span>
    @endif
    @if ($location->kind)
        <span class="tile__eyebrow">{{ $location->kind }}</span>
    @endif
    <span class="tile__title">{{ $location->name }}</span>
    @if ($location->summary)
        <span class="tile__text">{{ $location->summary }}</span>
    @endif
    <span class="tile__more">Entrer</span>
</a>
