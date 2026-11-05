@php
    $names = $scene->relationLoaded('activeParticipants')
        ? $scene->activeParticipants->map(fn ($p) => $p->character?->fullName())->filter()->join(', ', ' et ')
        : null;
@endphp
<article class="scene-card">
    <div class="cluster">
        <h3 class="scene-card__title"><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a></h3>
        <x-status-badge :status="$scene->status" />
        @if ($scene->isPrivate())
            <span class="badge">Privée</span>
        @endif
    </div>
    <p class="scene-card__meta">
        {{ $scene->rp_date ?: 'Date RP non précisée' }}@if ($scene->location) · <a href="{{ route('locations.show', $scene->location) }}">{{ $scene->location->name }}</a>@endif
        · {{ $scene->posts_count }} {{ $scene->posts_count > 1 ? 'réponses' : 'réponse' }}
    </p>
    @if ($scene->event_id && $scene->event)
        <p class="scene-card__meta">Événement : <a href="{{ route('events.show', $scene->event) }}">{{ $scene->event->title }}</a></p>
    @endif
    @if ($names)
        <p class="scene-card__cast">{{ $names }}</p>
    @endif
    @if ($scene->warningLabels() !== [])
        <p class="scene-card__warnings"><span class="visually-hidden">Avertissements : </span>{{ implode(' · ', $scene->warningLabels()) }}</p>
    @endif
</article>
