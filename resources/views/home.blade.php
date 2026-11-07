{{-- Vitrine de Tsukimori pour les visiteurs. Les membres connectés arrivent sur leur tableau de bord. --}}
@php($ambiance = \App\Support\Ambiance::class)
<x-layout overlay>
    <section class="hero-lac" aria-labelledby="titre-accueil">
        <x-ambiance name="lac-torii" eager class="hero-lac__image" />
        @include('partials.lake-lanterns')
        <div class="hero-lac__veil" aria-hidden="true"></div>
        <p class="hero-lac__vertical" lang="ja" aria-hidden="true">ツキモリ町</p>

        <div class="container hero-lac__inner">
            <h1 id="titre-accueil" class="hero-lac__title">
                <span class="hero-lac__kanji" lang="ja">{{ config('hollow.kanji') }}</span>
                <span class="hero-lac__latin">{{ config('hollow.name') }} Machi</span>
            </h1>
            <x-ornament class="hero-lac__ornament" />
            <p class="hero-lac__tagline">{{ config('hollow.tagline') }}.</p>
            <p class="hero-lac__pitch">Une petite ville de Nagano, un lac dans la brume. Trente ans après l’incendie de Kirisawa, les morts reviennent. Écrivez ce qu’ils vont changer.</p>

            <div class="hero-lac__actions">
                @auth
                    <a class="btn btn--large btn--glow" href="{{ route('dashboard') }}">Mon tableau de bord</a>
                @else
                    @if ($registrationsOpen)
                        <a class="btn btn--large btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a>
                    @endif
                @endauth
                <a class="btn btn--large btn--ghost" href="{{ route('universe.index') }}">Découvrir l’univers <x-picto name="fleche" /></a>
            </div>

            @guest
                <p class="hero-lac__login">
                    @unless ($registrationsOpen)
                        Les inscriptions ne sont pas encore ouvertes : la ville vous attend bientôt. ·
                    @endunless
                    Déjà habitant ? <a href="{{ route('login') }}">Se connecter</a>
                </p>
            @endguest
        </div>

        <p class="hero-lac__today">
            <span class="hero-lac__today-label">Aujourd’hui à Tsukimori</span>
            <span>{{ $rpDate }}</span>
            @if ($weather !== '')<span>{{ $weather }}</span>@endif
            @if ($charactersCount > 0)<span>{{ $charactersCount }} {{ $charactersCount > 1 ? 'habitants joués' : 'habitant joué' }}</span>@endif
        </p>
    </section>

    <nav class="entries" aria-label="Entrer dans Tsukimori">
        <div class="container entries__inner">
            <a class="entry" href="{{ route('universe.index') }}">
                <x-picto name="torii" class="entry__icon" />
                <span class="entry__title">Le contexte</span>
                <span class="entry__text">L’histoire de Kirisawa et ses échos à Tsukimori.</span>
            </a>
            <a class="entry" href="{{ route('families.index') }}">
                <x-picto name="sakura" class="entry__icon" />
                <span class="entry__title">Les maisons</span>
                <span class="entry__text">Quatre familles, quatre héritages.</span>
            </a>
            <a class="entry" href="{{ route('gazette.index') }}">
                <x-picto name="lanterne" class="entry__icon" />
                <span class="entry__title">La gazette</span>
                <span class="entry__text">Actualités, archives et rumeurs de la ville.</span>
            </a>
            <a class="entry" href="{{ route('city.index') }}">
                <x-picto name="montagnes" class="entry__icon" />
                <span class="entry__title">La carte</span>
                <span class="entry__text">Explorer Tsukimori et ses environs.</span>
            </a>
        </div>
    </nav>

    @if ($headline)
        <section class="section frontpage" aria-labelledby="titre-une">
            <div class="container frontpage__grid">
                <div class="frontpage__main">
                    <h2 id="titre-une" class="rule-title">À la une</h2>
                    <article class="une">
                        <a class="une__photo photo-print" href="{{ route('gazette.show', $headline) }}" tabindex="-1" aria-hidden="true">
                            <x-cover :model="$headline" :fallback="$ambiance::forArticle($headline)" />
                        </a>
                        <div class="une__body">
                            <p class="une__meta"><span class="tag">{{ $headline->rubric->label() }}</span>@if ($headline->rp_date)<span>{{ $headline->rp_date }}</span>@endif</p>
                            <h3 class="une__title"><a href="{{ route('gazette.show', $headline) }}">{{ $headline->title }}</a></h3>
                            @if ($headline->excerpt)
                                <p class="une__excerpt">{{ \Illuminate\Support\Str::limit($headline->excerpt, 220) }}</p>
                            @endif
                            <a class="more-link" href="{{ route('gazette.show', $headline) }}">Lire l’article <x-picto name="fleche" /></a>
                        </div>
                    </article>
                </div>

                @if ($latest->isNotEmpty())
                    <div class="frontpage__side">
                        <h2 class="rule-title">Derniers articles</h2>
                        <ul class="latest">
                            @foreach ($latest as $article)
                                <li>
                                    <a class="latest__item" href="{{ route('gazette.show', $article) }}">
                                        <x-cover :model="$article" :fallback="$ambiance::forArticle($article)" size="small" class="latest__thumb" />
                                        <span class="latest__body">
                                            <span class="latest__rubric">{{ $article->rubric->label() }}</span>
                                            <span class="latest__title">{{ $article->title }}</span>
                                        </span>
                                        <x-picto name="chevron" class="latest__chevron" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a class="more-link" href="{{ route('gazette.index') }}">Toute la Gazette <x-picto name="fleche" /></a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <nav class="showcase-nav" aria-label="Sur cette page">
        <div class="container">
            <ul>
                <li><a href="#mysteres">Les mystères</a></li>
                @if ($natures->isNotEmpty())<li><a href="#incarner">Qui incarner</a></li>@endif
                @if ($families->isNotEmpty())<li><a href="#familles">Les maisons</a></li>@endif
                @if ($roles->isNotEmpty())<li><a href="#roles">Rôles à prendre</a></li>@endif
                <li><a href="#ville">La ville</a></li>
                <li><a href="#rejoindre">Rejoindre</a></li>
            </ul>
        </div>
    </nav>

    <section class="section" id="mysteres" aria-labelledby="titre-mysteres">
        <div class="container">
            <header class="section-head">
                <p class="section-head__eyebrow">Ce que la ville tait</p>
                <h2 id="titre-mysteres" class="section-head__title">Les mystères de Tsukimori</h2>
                <x-ornament />
            </header>
            <div class="mystery-grid">
                <article class="mystery">
                    <x-ambiance name="kirisawa" size="small" class="mystery__image" />
                    <div class="mystery__body">
                        <p class="mystery__date">31 octobre 1996 · 2 h 17</p>
                        <h3 class="mystery__title">L’incendie de Kirisawa</h3>
                        <p>L’hôpital psychiatrique de Kirisawa, « l’hôpital des Brumes », brûle en pleine nuit. Quarante-trois personnes décèdent. La cause de l’incendie et l’importance de 2 h 17 restent un mystère.</p>
                    </div>
                </article>
                <article class="mystery">
                    <x-ambiance name="lanternes" size="small" class="mystery__image" />
                    <div class="mystery__body">
                        <p class="mystery__date">Chaque 31 octobre depuis 1997</p>
                        <h3 class="mystery__title">La quarante-quatrième lanterne</h3>
                        <p>Quarante-trois lanternes pour les victimes. Une quarante-quatrième, blanche, continue d’apparaître. Certaines années, un nom s’inscrit sur son papier.</p>
                    </div>
                </article>
                <article class="mystery">
                    <x-ambiance name="sanctuaire" size="small" class="mystery__image" />
                    <div class="mystery__body">
                        <p class="mystery__date">Depuis le 1er novembre 2026</p>
                        <h3 class="mystery__title">Ceux qui reviennent</h3>
                        <p>Des victimes de Kirisawa réapparaissent dans les rues. Elles n’ont pas vieilli et se souviennent de tout. Personne ne sait pourquoi elles reviennent.</p>
                    </div>
                </article>
            </div>
            <p class="section-more"><a class="more-link" href="{{ route('universe.index') }}">Lire le contexte complet <x-picto name="fleche" /></a></p>
        </div>
    </section>

    @if ($natures->isNotEmpty())
        <section class="section section--mist" id="incarner" aria-labelledby="titre-incarner">
            <div class="container">
                <header class="section-head">
                    <p class="section-head__eyebrow">Humain ordinaire ou mort revenu</p>
                    <h2 id="titre-incarner" class="section-head__title">Qui incarner ?</h2>
                    <x-ornament />
                    <p class="section-head__lead">Le surnaturel n’est jamais obligatoire.</p>
                </header>
                <div class="feature-grid">
                    @foreach ($natures as $nature)
                        <a class="feature-card" href="{{ route('species.show', $nature) }}">
                            <x-cover :model="$nature" :fallback="$ambiance::forSpecies($nature)" size="small" class="feature-card__image" />
                            <span class="feature-card__body">
                                <span class="feature-card__name">
                                    @if ($nature->japanese_name)<span class="feature-card__kanji" lang="ja">{{ $nature->japanese_name }}</span><span class="feature-card__dash" aria-hidden="true">—</span>@endif
                                    {{ $nature->name }}
                                </span>
                                @if ($nature->summary)<span class="feature-card__text">{{ \Illuminate\Support\Str::limit($nature->summary, 110) }}</span>@endif
                                @if ($nature->is_rare)<span class="feature-card__badge">Nature rare</span>@endif
                            </span>
                            <span class="feature-card__arrow" aria-hidden="true"><x-picto name="fleche" /></span>
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
                <header class="section-head">
                    <p class="section-head__eyebrow">Quatre familles, quatre héritages</p>
                    <h2 id="titre-familles" class="section-head__title">Les maisons de Tsukimori</h2>
                    <x-ornament />
                    <p class="section-head__lead">Des noms que toute la ville connaît. Les rejoindre est possible, jamais obligatoire.</p>
                </header>
                <div class="feature-grid feature-grid--two">
                    @foreach ($families as $family)
                        <a class="feature-card" href="{{ route('families.show', $family) }}">
                            <x-cover :model="$family" :fallback="$ambiance::forFamily($family)" size="small" class="feature-card__image" />
                            <span class="feature-card__body">
                                <span class="feature-card__name">
                                    @if ($family->japanese_name)<span class="feature-card__kanji" lang="ja">{{ $family->japanese_name }}</span><span class="feature-card__dash" aria-hidden="true">—</span>@endif
                                    {{ $family->shortName() }}
                                </span>
                                @if ($family->motto)<span class="feature-card__text">« {{ $family->motto }} »</span>@endif
                                @if ($family->activities)<span class="feature-card__meta">{{ $family->activities }}</span>@endif
                            </span>
                            <span class="feature-card__arrow" aria-hidden="true"><x-picto name="fleche" /></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($roles->isNotEmpty())
        <section class="section section--mist" id="roles" aria-labelledby="titre-roles">
            <div class="container">
                <header class="section-head">
                    <p class="section-head__eyebrow">Prêts à être écrits</p>
                    <h2 id="titre-roles" class="section-head__title">Rôles et scénarios à prendre</h2>
                    <x-ornament />
                </header>
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
                    <p class="section-more"><a class="more-link" href="{{ route('predefined.index') }}">Les {{ $rolesCount }} rôles disponibles <x-picto name="fleche" /></a></p>
                @endif
            </div>
        </section>
    @endif

    <section class="section" id="ville" aria-labelledby="titre-ville-accueil">
        <div class="container">
            <header class="section-head">
                <p class="section-head__eyebrow">Cinq quartiers, un lac, des sanctuaires</p>
                <h2 id="titre-ville-accueil" class="section-head__title">La ville et son lac</h2>
                <x-ornament />
                <p class="section-head__lead">Choisissez un lieu : chaque scène s’y écrit.</p>
            </header>
            @if ($places->isNotEmpty())
                <div class="map-frame">
                    @include('city.partials.map')
                </div>
            @endif
            <p class="section-more"><a class="more-link" href="{{ route('city.index') }}">Tous les quartiers et lieux <x-picto name="fleche" /></a></p>
        </div>
    </section>

    @if ($event || $scenes->isNotEmpty())
        <section class="section section--mist" aria-labelledby="titre-vie">
            <div class="container">
                <header class="section-head">
                    <p class="section-head__eyebrow">La ville bouge</p>
                    <h2 id="titre-vie" class="section-head__title">En ce moment</h2>
                    <x-ornament />
                </header>
                <div class="grid-cards">
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
            <header class="section-head">
                <p class="section-head__eyebrow">Nos principes</p>
                <h2 id="titre-principes" class="section-head__title">Une communauté d’écriture entre humains</h2>
                <x-ornament />
            </header>
            <div class="principles">
                <article class="principle">
                    <x-picto name="plume" class="principle__icon" />
                    <h3 class="principle__title">Écrit par des personnes</h3>
                    <p>Chaque réponse, chaque personnage et chaque intrigue sont rédigés par des joueurs et par l’équipe. Aucun texte n’est produit par une intelligence artificielle.</p>
                </article>
                <article class="principle">
                    <x-picto name="lune" class="principle__icon" />
                    <h3 class="principle__title">Réservé aux adultes</h3>
                    <p>Les joueurs et leurs personnages sont majeurs. Romance, mystère et horreur psychologique se jouent entre adultes, dans le respect du règlement.</p>
                </article>
                <article class="principle">
                    <x-picto name="livre" class="principle__icon" />
                    <h3 class="principle__title">Vos textes vous appartiennent</h3>
                    <p>Données hébergées en France, sans publicité ni traceur. Vous pouvez à tout moment consulter vos informations et en demander la suppression.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="join-band" id="rejoindre" aria-labelledby="titre-rejoindre">
        <x-ambiance name="lanternes" class="join-band__image" />
        <x-sakura-branch class="join-band__branch" />
        <div class="container join-band__inner">
            <div class="join-band__text">
                <h2 id="titre-rejoindre" class="join-band__title">Votre lanterne vous attend.</h2>
                <p>Un compte, une fiche de personnage relue par l’équipe, puis une première scène dans un lieu de la ville.</p>
            </div>
            <div class="join-band__actions">
                @guest
                    @if ($registrationsOpen)
                        <a class="btn btn--large btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a>
                    @else
                        <p class="muted">Les inscriptions ouvriront bientôt.</p>
                    @endif
                    <a class="btn btn--ghost btn--large" href="{{ route('pages.reglement') }}">Lire le règlement</a>
                @else
                    <a class="btn btn--large btn--glow" href="{{ route('dashboard') }}">Mon tableau de bord</a>
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
