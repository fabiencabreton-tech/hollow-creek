<x-layout>
    <section class="hero" aria-labelledby="titre-accueil">
        <div class="container hero__inner">
            <div class="hero__text">
                <p class="eyebrow">RPG littéraire · surnaturel · mystère · romance</p>
                <h1 id="titre-accueil" class="hero__title">{{ config('hollow.name') }}</h1>
                <p class="hero__subtitle">{{ config('hollow.tagline') }}</p>

                @if ($presentation)
                    <div class="prose">{{ $presentation->renderedBody() }}</div>
                @endif

                <div class="cluster">
                    @auth
                        <a class="btn btn--large" href="{{ route('dashboard') }}">Mon tableau de bord</a>
                    @else
                        @if ($registrationsOpen)
                            <a class="btn btn--large" href="{{ route('register') }}">Rejoindre Hollow Creek</a>
                        @endif
                        <a class="btn btn--ghost btn--large" href="{{ route('login') }}">Se connecter</a>
                    @endauth
                    <a class="btn btn--ghost btn--large" href="{{ route('pages.reglement') }}">Lire le règlement</a>
                </div>

                @guest
                    @unless ($registrationsOpen)
                        <p class="muted">Les inscriptions ne sont pas encore ouvertes. Revenez bientôt : la ville vous attend.</p>
                    @endunless
                @endguest
            </div>

            <aside class="hero__today card stack" aria-label="Hollow Creek, aujourd’hui">
                <p class="eyebrow">Hollow Creek, aujourd’hui</p>
                <p class="today__date">{{ $rpDate }}</p>
                <div class="today__clock">
                    @include('partials.stopped-clock')
                    <p>Les horloges se sont arrêtées à 2 h 17.</p>
                </div>
                @if ($weather !== '')
                    <dl class="facts">
                        <dt>Météo</dt>
                        <dd>{{ $weather }}</dd>
                    </dl>
                @endif
            </aside>
        </div>

        @include('partials.lake')
    </section>

    @if ($headline)
        <section class="section" aria-labelledby="titre-gazette">
            <div class="container">
                <div class="home-gazette card">
                    <div class="stack">
                        <p class="home-gazette__masthead">The Hollow Creek Gazette</p>
                        <p class="eyebrow">{{ $headline->rubric->label() }}</p>
                        <h2 id="titre-gazette"><a href="{{ route('gazette.show', $headline) }}">{{ $headline->title }}</a></h2>
                        @if ($headline->excerpt)
                            <p class="muted">{{ $headline->excerpt }}</p>
                        @endif
                    </div>
                    <a class="btn btn--ghost" href="{{ route('gazette.index') }}">Lire la Gazette</a>
                </div>
            </div>
        </section>
    @endif

    <section class="section" aria-labelledby="titre-explorer">
        <div class="container">
            <h2 id="titre-explorer" class="section__title">Explorer Hollow Creek</h2>
            <div class="grid-cards">
                <a class="tile" href="{{ route('universe.index') }}">
                    <span class="tile__eyebrow">1996 · 2026</span>
                    <span class="tile__title">L’univers</span>
                    <span class="tile__text">L’incendie d’Alderwick, la Nuit des Lanternes et ceux qui reviennent.</span>
                    <span class="tile__more">Découvrir</span>
                </a>
                <a class="tile" href="{{ route('species.index') }}">
                    <span class="tile__eyebrow">Six espèces</span>
                    <span class="tile__title">Les espèces</span>
                    <span class="tile__text">Humains, revenants, fantômes, médiums, sorcières et chasseurs de spectres.</span>
                    <span class="tile__more">Découvrir</span>
                </a>
                <a class="tile" href="{{ route('city.index') }}">
                    <span class="tile__eyebrow">Quartiers et lieux</span>
                    <span class="tile__title">La ville</span>
                    <span class="tile__text">Les rues, les commerces, la forêt et le lac où se jouent les scènes.</span>
                    <span class="tile__more">Parcourir</span>
                </a>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="titre-principes">
        <div class="container">
            <h2 id="titre-principes" class="section__title">Une communauté d’écriture entre humains</h2>
            <div class="grid-cards">
                <article class="card stack">
                    <h3>Écrit par des personnes</h3>
                    <p class="muted">Chaque réponse, chaque personnage et chaque intrigue sont rédigés par des joueurs et par l’équipe. Aucun texte n’est produit par une intelligence artificielle.</p>
                </article>
                <article class="card stack">
                    <h3>Réservé aux adultes</h3>
                    <p class="muted">Les joueurs et leurs personnages sont majeurs. Romance, mystère et horreur psychologique se jouent entre adultes, dans le respect du règlement.</p>
                </article>
                <article class="card stack">
                    <h3>Vos textes vous appartiennent</h3>
                    <p class="muted">Données hébergées en France, sans publicité ni traceur. Vous pouvez à tout moment consulter vos informations et en demander la suppression.</p>
                </article>
            </div>
        </div>
    </section>
</x-layout>
