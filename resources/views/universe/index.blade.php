<x-layout title="L’univers" description="Hollow Creek, petite ville du nord-est des États-Unis : l’incendie d’Alderwick, la Nuit des Lanternes et ceux qui reviennent.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'L’univers' => null]" />
            <p class="eyebrow">Surnaturel · mystère · drame · romance · horreur psychologique</p>
            <h1>{{ $page?->title ?? 'L’univers' }}</h1>
        </header>

        @if ($page)
            <article class="prose">
                {{ $page->renderedBody() }}
            </article>
        @endif

        <p class="fleuron" aria-hidden="true">✦</p>

        <section class="page-body" aria-labelledby="titre-explorer">
            <h2 id="titre-explorer" class="section__title">Explorer Hollow Creek</h2>
            <div class="grid-cards">
                <a class="tile" href="{{ route('species.index') }}">
                    <span class="tile__eyebrow">{{ $species->count() }} {{ $species->count() > 1 ? 'espèces jouables' : 'espèce jouable' }}</span>
                    <span class="tile__title">Les espèces</span>
                    <span class="tile__text">Humains, revenants, fantômes, médiums, sorcières et chasseurs de spectres.</span>
                    <span class="tile__more">Découvrir les espèces</span>
                </a>
                <a class="tile" href="{{ route('families.index') }}">
                    <span class="tile__eyebrow">{{ $families->count() }} {{ $families->count() > 1 ? 'familles' : 'famille' }}</span>
                    <span class="tile__title">Les familles fondatrices</span>
                    <span class="tile__text">{{ $families->pluck('name')->join(', ', ' et ') ?: 'Bientôt présentées.' }}</span>
                    <span class="tile__more">Découvrir les familles</span>
                </a>
                <a class="tile" href="{{ route('city.index') }}">
                    <span class="tile__eyebrow">Quartiers et lieux</span>
                    <span class="tile__title">La ville</span>
                    <span class="tile__text">Les rues, les commerces, la forêt et le lac où se jouent les scènes.</span>
                    <span class="tile__more">Parcourir la ville</span>
                </a>
                <a class="tile" href="{{ route('gazette.index') }}">
                    <span class="tile__eyebrow">Le journal local</span>
                    <span class="tile__title">The Hollow Creek Gazette</span>
                    <span class="tile__text">Actualités, faits divers, vie locale, annonces et avis de décès.</span>
                    <span class="tile__more">Lire la Gazette</span>
                </a>
            </div>
        </section>
    </div>
</x-layout>
