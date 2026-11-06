{{-- Vitrine de Tsukimori pour les visiteurs. Les membres connectés arrivent sur leur tableau de bord. --}}
<x-layout>
    <section class="hero" aria-labelledby="titre-accueil">
        <div class="container hero__inner">
            <div class="hero__text">
                <p class="eyebrow">RPG littéraire · Japon rural · surnaturel · mystère</p>
                <h1 id="titre-accueil" class="hero__title">{{ config('hollow.name') }}</h1>
                <p class="hero__subtitle">{{ config('hollow.tagline') }} <span class="hero__kanji" lang="ja">{{ config('hollow.kanji') }}</span></p>
                <p class="hero__pitch">Une petite ville de Nagano, un lac dans la brume. Trente ans après l’incendie de Kirisawa, les morts reviennent. Écrivez ce qu’ils vont changer.</p>

                <div class="cluster hero__actions">
                    @auth
                        <a class="btn btn--large" href="{{ route('dashboard') }}">Mon tableau de bord</a>
                    @else
                        @if ($registrationsOpen)
                            <a class="btn btn--large btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a>
                        @endif
                        <a class="btn btn--ghost btn--large" href="{{ route('login') }}">Se connecter</a>
                    @endauth
                    <a class="hero__link" href="{{ route('universe.index') }}">Découvrir l’univers</a>
                </div>

                @guest
                    @unless ($registrationsOpen)
                        <p class="muted">Les inscriptions ne sont pas encore ouvertes. Revenez bientôt : la ville vous attend.</p>
                    @endunless
                @endguest
            </div>

            <aside class="hero__today card stack" aria-label="Tsukimori, aujourd’hui">
                <p class="eyebrow">Tsukimori, aujourd’hui</p>
                <p class="today__date">{{ $rpDate }}</p>
                <div class="today__clock">
                    @include('partials.stopped-clock')
                    <p>Kirisawa, 31 octobre 1996, 2 h 17.</p>
                </div>
                @if ($weather !== '')
                    <dl class="facts">
                        <dt>Météo</dt>
                        <dd>{{ $weather }}</dd>
                    </dl>
                @endif
                @if ($charactersCount > 0)
                    <p class="muted">{{ $charactersCount }} {{ $charactersCount > 1 ? 'personnages vivent' : 'personnage vit' }} à Tsukimori.</p>
                @endif
            </aside>
        </div>

        @include('partials.lake')
    </section>

    <nav class="showcase-nav" aria-label="Sur cette page">
        <div class="container">
            <ul>
                <li><a href="#mysteres">Les mystères</a></li>
                <li><a href="#incarner">Qui incarner</a></li>
                <li><a href="#familles">Les familles</a></li>
                @if ($roles->isNotEmpty())<li><a href="#roles">Rôles à prendre</a></li>@endif
                <li><a href="#ville">La ville</a></li>
                <li><a href="#rejoindre">Rejoindre</a></li>
            </ul>
        </div>
    </nav>

    <section class="section" id="mysteres" aria-labelledby="titre-mysteres">
        <div class="container">
            <h2 id="titre-mysteres" class="section__title">Les mystères de Tsukimori</h2>
            <div class="mystery-grid">
                <article class="mystery">
                    <p class="mystery__date">31 octobre 1996 · 2 h 17</p>
                    <h3 class="mystery__title">L’incendie de Kirisawa</h3>
                    <p>L’hôpital psychiatrique de Kirisawa, « l’hôpital des Brumes », brûle en pleine nuit. Quarante-trois personnes décèdent. La cause de l’incendie et l’importance de 2 h 17 restent un mystère.</p>
                </article>
                <article class="mystery">
                    <p class="mystery__date">Chaque 31 octobre depuis 1997</p>
                    <h3 class="mystery__title">La quarante-quatrième lanterne</h3>
                    <p>Quarante-trois lanternes pour les victimes. Une quarante-quatrième, blanche, continue d’apparaître. Certaines années, un nom s’inscrit sur son papier.</p>
                </article>
                <article class="mystery">
                    <p class="mystery__date">Depuis le 1er novembre 2026</p>
                    <h3 class="mystery__title">Ceux qui reviennent</h3>
                    <p>Des victimes de Kirisawa réapparaissent dans les rues. Elles n’ont pas vieilli et se souviennent de tout. Personne ne sait pourquoi elles reviennent.</p>
                </article>
            </div>
            @if ($presentation)
                <div class="prose showcase-presentation">{{ $presentation->renderedBody() }}</div>
            @endif
            <p><a class="hero__link" href="{{ route('universe.index') }}">Lire le contexte complet</a></p>
        </div>
    </section>

    @if ($natures->isNotEmpty())
        <section class="section" id="incarner" aria-labelledby="titre-incarner">
            <div class="container">
                <h2 id="titre-incarner" class="section__title">Qui incarner ?</h2>
                <p class="lede">Humain ordinaire ou mort revenu : le surnaturel n’est jamais obligatoire.</p>
                <div class="grid-cards">
                    @foreach ($natures as $nature)
                        <a class="tile tile--{{ $nature->treatment?->value }}" href="{{ route('species.show', $nature) }}">
                            <span class="tile__eyebrow">{{ $nature->is_rare ? 'Nature rare' : 'Nature' }}</span>
                            <span class="tile__title">{{ $nature->name }}</span>
                            @if ($nature->summary)<span class="tile__text">{{ \Illuminate\Support\Str::limit($nature->summary, 150) }}</span>@endif
                        </a>
                    @endforeach
                </div>
                @if ($traits->isNotEmpty())
                    <p class="chips" aria-label="Spécialisations et conditions">
                        <span class="muted">À ajouter à une nature :</span>
                        @foreach ($traits as $trait)
                            <a class="chip" href="{{ route('species.show', $trait) }}">{{ $trait->name }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
        </section>
    @endif

    @if ($families->isNotEmpty())
        <section class="section" id="familles" aria-labelledby="titre-familles">
            <div class="container">
                <h2 id="titre-familles" class="section__title">Les grandes familles</h2>
                <p class="lede">Quatre noms que toute la ville connaît. Les rejoindre est possible, jamais obligatoire.</p>
                <div class="family-grid family-grid--compact">
                    @foreach ($families as $family)
                        <a class="family-card" href="{{ route('families.show', $family) }}">
                            <span class="family-card__seal" lang="ja" aria-hidden="true">{{ $family->initial() }}</span>
                            <span class="family-card__body">
                                <span class="family-card__name">{{ $family->shortName() }} @if ($family->japanese_name)<span class="family-card__kanji" lang="ja">{{ $family->japanese_name }}</span>@endif</span>
                                @if ($family->motto)<span class="family-card__motto">« {{ $family->motto }} »</span>@endif
                                @if ($family->activities)<span class="family-card__meta">{{ $family->activities }}</span>@endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($roles->isNotEmpty())
        <section class="section" id="roles" aria-labelledby="titre-roles">
            <div class="container">
                <h2 id="titre-roles" class="section__title">Rôles et scénarios à prendre</h2>
                <div class="grid-cards">
                    @foreach ($roles as $role)
                        <a class="tile" href="{{ route('predefined.show', $role) }}">
                            <span class="tile__eyebrow">{{ collect([$role->species?->name, $role->family?->shortName()])->filter()->join(' · ') ?: 'Prédéfini' }}</span>
                            <span class="tile__title">{{ $role->title }}</span>
                            @if ($role->summary)<span class="tile__text">{{ \Illuminate\Support\Str::limit($role->summary, 150) }}</span>@endif
                            <span class="tile__more">Lire le rôle</span>
                        </a>
                    @endforeach
                </div>
                @if ($rolesCount > $roles->count())
                    <p><a class="hero__link" href="{{ route('predefined.index') }}">Les {{ $rolesCount }} rôles disponibles</a></p>
                @endif
            </div>
        </section>
    @endif

    <section class="section" id="ville" aria-labelledby="titre-ville-accueil">
        <div class="container">
            <h2 id="titre-ville-accueil" class="section__title">La ville et son lac</h2>
            <p class="lede">Cinq quartiers, un lac dans la brume, des sanctuaires et les ruines de Kirisawa. Choisissez un lieu : chaque scène s’y écrit.</p>
            @if ($places->isNotEmpty())
                @include('city.partials.map')
            @endif
            <p><a class="hero__link" href="{{ route('city.index') }}">Tous les quartiers et lieux</a></p>
        </div>
    </section>

    @if ($headline || $event || $scenes->isNotEmpty())
        <section class="section" aria-labelledby="titre-vie">
            <div class="container">
                <h2 id="titre-vie" class="section__title">En ce moment</h2>
                <div class="grid-cards">
                    @if ($headline)
                        <a class="tile" href="{{ route('gazette.show', $headline) }}">
                            <span class="tile__eyebrow">La Gazette · {{ $headline->rubric->label() }}</span>
                            <span class="tile__title">{{ $headline->title }}</span>
                            @if ($headline->excerpt)<span class="tile__text">{{ \Illuminate\Support\Str::limit($headline->excerpt, 140) }}</span>@endif
                        </a>
                    @endif
                    @if ($event)
                        <a class="tile" href="{{ route('events.show', $event) }}">
                            <span class="tile__eyebrow">Événement · {{ $event->rp_date }}</span>
                            <span class="tile__title">{{ $event->title }}</span>
                            @if ($event->summary)<span class="tile__text">{{ \Illuminate\Support\Str::limit($event->summary, 140) }}</span>@endif
                        </a>
                    @endif
                    @foreach ($scenes as $scene)
                        <a class="tile" href="{{ route('scenes.show', $scene) }}">
                            <span class="tile__eyebrow">Scène{{ $scene->location ? ' · '.$scene->location->name : '' }}</span>
                            <span class="tile__title">{{ $scene->title }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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

    <section class="join-band" id="rejoindre" aria-labelledby="titre-rejoindre">
        <div class="container join-band__inner">
            <div class="stack">
                <h2 id="titre-rejoindre">Votre lanterne vous attend.</h2>
                <p>Un compte, une fiche de personnage relue par l’équipe, puis une première scène dans un lieu de la ville.</p>
            </div>
            <div class="cluster">
                @guest
                    @if ($registrationsOpen)
                        <a class="btn btn--large btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a>
                    @else
                        <p class="muted">Les inscriptions ouvriront bientôt.</p>
                    @endif
                    <a class="btn btn--ghost btn--large" href="{{ route('pages.reglement') }}">Lire le règlement</a>
                @else
                    <a class="btn btn--large" href="{{ route('dashboard') }}">Mon tableau de bord</a>
                @endguest
            </div>
        </div>
    </section>

    @guest
        @if ($registrationsOpen)
            <div class="join-sticky" data-join-sticky>
                <a class="btn btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a>
            </div>
        @endif
    @endguest
</x-layout>
