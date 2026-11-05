<x-layout title="Le bestiaire" description="Les catégories jouables de Tsukimori : natures, spécialisations et conditions.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Le bestiaire' => null]" />
            <h1>Le bestiaire</h1>
            <p class="lede">Les catégories jouables de Tsukimori. Le surnaturel reste mystérieux, mais n’empêche jamais les interactions entre joueurs : humains et fantômes peuvent se lier, s’aimer, enquêter ensemble.</p>
        </header>

        @if ($groups->isEmpty())
            <p class="empty-note">Le bestiaire sera présenté ici très bientôt.</p>
        @else
            <div class="stack stack--loose page-body">
                @foreach ($groups as $kind => $items)
                    @php($kindEnum = \App\Enums\SpeciesKind::from($kind))
                    <section aria-labelledby="bestiaire-{{ $kind }}">
                        <h2 id="bestiaire-{{ $kind }}" class="section__title">{{ $kindEnum->pluralLabel() }}</h2>
                        <p class="muted">{{ $kindEnum->intro() }}</p>
                        <div class="grid-cards">
                            @foreach ($items as $item)
                                <a class="tile tile--{{ $item->treatment->value }}" href="{{ route('species.show', $item) }}">
                                    @if ($item->imageUrl())
                                        <span class="tile__media"><img src="{{ $item->imageUrl() }}" alt="" loading="lazy"></span>
                                    @endif
                                    <span class="tile__eyebrow">{{ $item->parent ? 'Variante · '.$item->parent->name : $kindEnum->getLabel() }}@if ($item->is_rare) · rare @endif</span>
                                    <span class="tile__title">{{ $item->name }}</span>
                                    @if ($item->summary)
                                        <span class="tile__text">{{ $item->summary }}</span>
                                    @endif
                                    <span class="tile__more">Lire la fiche</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
