<x-layout title="Le bestiaire" description="Les catégories jouables de Tsukimori : natures, spécialisations et conditions.">
    <x-page-hero image="sanctuaire" title="Le bestiaire" eyebrow="Natures, spécialisations et conditions"
        lead="Les catégories jouables de Tsukimori. Le surnaturel reste mystérieux, mais n’empêche jamais les interactions entre joueurs : humains et fantômes peuvent se lier, s’aimer, enquêter ensemble."
        :breadcrumbs="['Le contexte' => route('universe.index'), 'Les espèces' => null]" />
    <div class="container">

        @if ($groups->isEmpty())
            <p class="empty-note">Le bestiaire sera présenté ici très bientôt.</p>
        @else
            <div class="stack stack--loose page-body">
                @foreach ($groups as $kind => $items)
                    @php($kindEnum = \App\Enums\SpeciesKind::from($kind))
                    <section aria-labelledby="bestiaire-{{ $kind }}">
                        <h2 id="bestiaire-{{ $kind }}" class="rule-title">{{ $kindEnum->pluralLabel() }}</h2>
                        <p class="muted">{{ $kindEnum->intro() }}</p>
                        <div class="feature-grid feature-grid--two">
                            @foreach ($items as $item)
                                <a class="feature-card" href="{{ route('species.show', $item) }}">
                                    <x-cover :model="$item" :fallback="\App\Support\Ambiance::forSpecies($item)" size="small" class="feature-card__image" />
                                    <span class="feature-card__body">
                                        <span class="feature-card__eyebrow">{{ $item->parent ? 'Variante · '.$item->parent->name : $kindEnum->getLabel() }}@if ($item->is_rare) · rare @endif</span>
                                        <span class="feature-card__name">
                                            @if ($item->japanese_name)<span class="feature-card__kanji" lang="ja">{{ $item->japanese_name }}</span><span class="feature-card__dash" aria-hidden="true">—</span>@endif
                                            {{ $item->name }}
                                        </span>
                                        @if ($item->summary)
                                            <span class="feature-card__text">{{ \Illuminate\Support\Str::limit($item->summary, 150) }}</span>
                                        @endif
                                    </span>
                                    <span class="feature-card__arrow" aria-hidden="true"><x-picto name="fleche" /></span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
