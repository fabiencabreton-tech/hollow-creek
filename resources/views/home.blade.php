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
            </aside>
        </div>

        @include('partials.lake')
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
