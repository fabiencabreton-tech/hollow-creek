<x-layout title="L’univers" description="Tsukimori, petite ville de la préfecture de Nagano : l’incendie de Kirisawa, les lanternes du lac et ceux qui reviennent.">
    <x-page-hero image="foret-brume" :title="$page?->title ?? 'L’univers'" kanji="月森町"
        eyebrow="Surnaturel · mystère · drame · romance · horreur psychologique"
        :breadcrumbs="['Accueil' => route('home'), 'Le contexte' => null]" />
    <div class="container">

        @if ($page)
            <article class="prose">
                {{ $page->renderedBody() }}
            </article>
        @endif

        <p class="fleuron" aria-hidden="true">✦</p>

        <section class="page-body" aria-labelledby="titre-explorer">
            <h2 id="titre-explorer" class="section__title">Explorer Tsukimori</h2>
            <div class="grid-cards">
                <a class="tile" href="{{ route('species.index') }}">
                    <span class="tile__eyebrow">{{ $species->count() }} {{ $species->count() > 1 ? 'catégories jouables' : 'catégorie jouable' }}</span>
                    <span class="tile__title">Le bestiaire</span>
                    <span class="tile__text">Humains, médiums, praticiens de l’occulte, Yūrei, Onryō, Revenus, Rappelés et Maudits.</span>
                    <span class="tile__more">Ouvrir le bestiaire</span>
                </a>
                <a class="tile" href="{{ route('kirisawa.index') }}">
                    <span class="tile__eyebrow">31 octobre 1996 · 2 h 17</span>
                    <span class="tile__title">Le registre de Kirisawa</span>
                    <span class="tile__text">Les quarante-trois victimes de l’incendie de l’hôpital des Brumes.</span>
                    <span class="tile__more">Consulter le registre</span>
                </a>
                <a class="tile" href="{{ route('families.index') }}">
                    <span class="tile__eyebrow">{{ $families->count() }} {{ $families->count() > 1 ? 'familles' : 'famille' }}</span>
                    <span class="tile__title">Les familles</span>
                    <span class="tile__text">{{ $families->pluck('name')->join(', ', ' et ') ?: 'Les familles de Tsukimori seront présentées par l’équipe.' }}</span>
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
                    <span class="tile__title">La Gazette de Tsukimori</span>
                    <span class="tile__text">Actualités, faits divers, vie locale, annonces et avis de décès.</span>
                    <span class="tile__more">Lire la Gazette</span>
                </a>
            </div>
        </section>
    </div>
</x-layout>
