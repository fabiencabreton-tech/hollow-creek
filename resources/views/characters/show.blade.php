<x-layout :title="$character->fullName()" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header profile-header">
            <x-breadcrumbs :items="['Les personnages' => route('characters.index'), $character->fullName() => null]" />
            @if ($isPreview)
                <p class="preview-banner" role="status">Aperçu : cette fiche ({{ $character->status->getLabel() }}) n’est pas encore publique.</p>
            @endif
        </header>

        <div class="profile">
            <aside class="profile__aside" aria-label="Portrait et identité">
                <x-portrait :character="$character" size="large" />
                @if ($character->avatar_credit)
                    <p class="profile__credit">{{ $character->faceclaim?->name }}@if ($character->faceclaim) · @endif{{ $character->avatar_credit }}</p>
                @endif
                <dl class="facts">
                    <dt>Âge</dt><dd>{{ $character->age }} ans @if ($character->apparent_age && $character->apparent_age !== $character->age) (en paraît {{ $character->apparent_age }})@endif</dd>
                    <dt>Espèce</dt><dd>@if ($character->species)<a href="{{ route('species.show', $character->species) }}">{{ $character->species->name }}</a>@else — @endif</dd>
                    <dt>Famille</dt><dd>@if ($character->family)<a href="{{ route('families.show', $character->family) }}">{{ $character->family->name }}</a>@else Indépendant @endif</dd>
                    <dt>Profession</dt><dd>{{ $character->profession ?: '—' }}</dd>
                    <dt>Joué par</dt><dd>{{ $character->user?->pseudo }}</dd>
                </dl>
            </aside>

            <div class="profile__main">
                <p class="eyebrow">{{ $character->species?->name ?? 'Personnage' }}</p>
                <h1>{{ $character->fullName() }}</h1>
                @if ($character->quote)
                    <blockquote class="profile__quote">« {{ $character->quote }} »</blockquote>
                @endif

                @if ($character->history)
                    <section class="entry__section">
                        <h2>Histoire</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($character->history) }}</div>
                    </section>
                @endif

                @if ($character->personality)
                    <section class="entry__section">
                        <h2>Caractère</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($character->personality) }}</div>
                    </section>
                @endif

                @php
                    $ongoing = $scenes->whereIn('status', [\App\Enums\SceneStatus::Ongoing, \App\Enums\SceneStatus::Paused]);
                    $done = $scenes->where('status', \App\Enums\SceneStatus::Finished);
                @endphp
                <section class="entry__section">
                    <h2>Scènes en cours</h2>
                    @if ($ongoing->isEmpty())
                        <p class="muted">Aucune scène publique en cours.</p>
                    @else
                        <ul class="plain-list stack stack--tight">
                            @foreach ($ongoing as $scene)
                                <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a><span class="muted"> · {{ $scene->rp_date }}@if ($scene->location) · {{ $scene->location->name }}@endif</span></li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="entry__section">
                    <h2>RP terminés</h2>
                    @if ($done->isEmpty())
                        <p class="muted">Aucune scène publique terminée.</p>
                    @else
                        <ul class="plain-list stack stack--tight">
                            @foreach ($done as $scene)
                                <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a><span class="muted"> · {{ $scene->rp_date }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-layout>
