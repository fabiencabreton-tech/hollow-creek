<a class="tile" href="{{ route('events.show', $event) }}">
    @if ($event->imageUrl())
        <span class="tile__media"><img src="{{ $event->imageUrl() }}" alt="" loading="lazy" decoding="async"></span>
    @endif
    <span class="cluster"><span class="tile__eyebrow">{{ $event->rp_date ?: 'Date à venir' }}</span><x-status-badge :status="$event->status" /></span>
    <span class="tile__title">{{ $event->title }}</span>
    @if ($event->summary)
        <span class="tile__text">{{ $event->summary }}</span>
    @endif
    <span class="tile__more">Voir l’événement</span>
</a>
