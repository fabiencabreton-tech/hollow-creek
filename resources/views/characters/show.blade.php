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

                <section class="entry__section">
                    <h2>Liens et scènes</h2>
                    <p class="muted">Les relations, les scènes en cours et terminées apparaîtront ici avec l’ouverture des scènes RP.</p>
                </section>
            </div>
        </div>
    </div>
</x-layout>
