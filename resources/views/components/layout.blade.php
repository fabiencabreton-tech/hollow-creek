@props([
    'title' => null,
    'description' => null,
    'robots' => null,
    // En-tête posé sur l'illustration (accueil) plutôt qu'au-dessus.
    'overlay' => false,
])
@php
    $siteName = config('hollow.name');
    $fullTitle = $title ? $title.' · '.$siteName : $siteName.' — '.config('hollow.tagline');
    $user = auth()->user();
    $verified = $user?->hasVerifiedEmail() ?? false;
    $unreadNotifications = $verified ? app(\App\Services\Notifier::class)->unreadCount($user) : 0;
    $unreadMessages = $verified ? \App\Models\Conversation::unreadCountFor($user) : 0;
    $activeCharacter = $verified ? $user->activeCharacter() : null;
    $rpgActive = request()->routeIs('scenes.*', 'characters.index', 'characters.show', 'predefined.*', 'feed.*', 'events.*', 'investigations.*', 'clues.*', 'faceclaims.*', 'ads.*', 'archives.*', 'journal.*');
    $rpgLinks = [
        ['scenes.index', 'Scènes', 'plume', 'Les histoires en cours'],
        ['characters.index', 'Personnages', 'personnes', 'Les habitants de la ville'],
        ['predefined.index', 'Rôles prédéfinis', 'masque', 'Rôles et scénarios à prendre'],
        ['feed.index', 'Murmures', 'murmure', 'Pensées et images des personnages'],
        ['events.index', 'Événements', 'calendrier', 'Les rendez-vous de la ville'],
        ['investigations.index', 'Enquêtes', 'loupe', 'Indices et mystères'],
        ['faceclaims.index', 'Bottin des avatars', 'compte', 'Les visages déjà pris'],
        ['ads.index', 'Petites annonces', 'parchemin', 'Ventes, emplois, rencontres'],
        ['archives.index', 'Archives municipales', 'livre', 'Les registres de la mairie'],
    ];
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description ?? 'Tsukimori — Ceux qui reviennent : un RPG littéraire surnaturel, écrit par des humains, pour des humains.' }}">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <meta name="theme-color" content="#211a26">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icones/apple-touch-icon.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Tsukimori">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <script nonce="{{ Vite::cspNonce() }}">
        document.documentElement.classList.add('js');
        try { if (localStorage.getItem('hollow-creek-theme') === 'parchemin') { document.documentElement.setAttribute('data-theme', 'parchemin'); } } catch (e) {}
        try { if (localStorage.getItem('tsukimori-mouvement') === 'reduit') { document.documentElement.setAttribute('data-motion', 'reduit'); } } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['has-player-bar' => $verified, 'has-overlay-header' => $overlay]) @if (request()->routeIs('home', 'dashboard')) data-app-root @endif>
    @include('partials.icons')
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header @class(['site-header', 'site-header--overlay' => $overlay]) data-site-header>
        <div class="site-header__inner">
            {{-- Application installée : pas de bouton « retour » du navigateur, on en propose un. --}}
            <button type="button" class="app-back" data-app-back><span aria-hidden="true">‹</span><span class="visually-hidden">Revenir à la page précédente</span></button>

            <a class="brand" href="{{ route('home') }}">
                <span class="brand__latin">{{ $siteName }} Machi</span>
                <span class="brand__kanji" lang="ja">{{ config('hollow.kanji') }}</span>
            </a>

            <nav id="site-nav" class="site-nav" aria-label="Navigation principale">
                <ul class="site-nav__links">
                    <li><a href="{{ $verified ? route('dashboard') : route('home') }}" @if (request()->routeIs('home', 'dashboard')) aria-current="page" @endif>Accueil</a></li>
                    <li><a href="{{ route('universe.index') }}" @if (request()->routeIs('universe.*', 'kirisawa.*')) aria-current="page" @endif>Contexte</a></li>
                    <li><a href="{{ route('families.index') }}" @if (request()->routeIs('families.*')) aria-current="page" @endif>Maisons</a></li>
                    <li><a href="{{ route('species.index') }}" @if (request()->routeIs('species.*')) aria-current="page" @endif>Espèces</a></li>
                    <li><a href="{{ route('gazette.index') }}" @if (request()->routeIs('gazette.*')) aria-current="page" @endif>Gazette</a></li>
                    <li><a href="{{ route('city.index') }}" @if (request()->routeIs('city.*', 'districts.*', 'locations.*', 'residences.*')) aria-current="page" @endif>Carte</a></li>
                    <li class="site-nav__group">
                        <details class="nav-menu" data-nav-menu>
                            <summary @class(['nav-menu__summary', 'is-current' => $rpgActive])>RPG<x-picto name="bas" class="nav-menu__caret" /></summary>
                            <ul class="nav-menu__list">
                                @foreach ($rpgLinks as [$routeName, $label, $icon, $hint])
                                    <li>
                                        <a href="{{ route($routeName) }}" @if (request()->routeIs(str_replace('.index', '.*', $routeName))) aria-current="page" @endif>
                                            <x-picto :name="$icon" />
                                            <span class="nav-menu__label">{{ $label }}<span class="nav-menu__hint">{{ $hint }}</span></span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    </li>
                </ul>

                {{-- Dans le tiroir (téléphone) : recherche et compte. --}}
                <div class="site-nav__drawer-extra">
                    <form class="drawer-search" method="GET" action="{{ route('search') }}" role="search">
                        <label class="visually-hidden" for="recherche-tiroir">Rechercher sur le site</label>
                        <input id="recherche-tiroir" type="search" name="q" placeholder="Rechercher un personnage, une scène, un lieu…" value="{{ request()->routeIs('search') ? request('q') : '' }}" maxlength="100">
                        <button type="submit" class="drawer-search__button"><x-picto name="loupe" /><span class="visually-hidden">Rechercher</span></button>
                    </form>
                    <ul class="drawer-account">
                        @auth
                            @if ($activeCharacter)
                                <li class="drawer-account__character"><a href="{{ route('dashboard') }}#personnage"><span class="active-chip__dot" aria-hidden="true"></span>{{ $activeCharacter->fullName() }}</a></li>
                            @endif
                            @if ($verified)
                                <li><a href="{{ route('characters.mine') }}">Mes personnages</a></li>
                                <li><a href="{{ route('scenes.mine') }}">Mes scènes</a></li>
                                <li><a href="{{ route('notifications.index') }}">Notifications <span class="counter" data-counter="notifications" @if ($unreadNotifications === 0) hidden @endif>{{ $unreadNotifications }}<span class="visually-hidden"> non lues</span></span></a></li>
                                <li><a href="{{ route('messages.index') }}">Messages <span class="counter" data-counter="messages" @if ($unreadMessages === 0) hidden @endif>{{ $unreadMessages }}<span class="visually-hidden"> non lus</span></span></a></li>
                            @endif
                            <li><a href="{{ route('account.edit') }}">Mon compte</a></li>
                            @if ($user->isStaff())
                                <li><a href="{{ url('/admin') }}">Administration</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="link-button">Se déconnecter</button>
                                </form>
                            </li>
                        @else
                            <li><a href="{{ route('login') }}">Connexion</a></li>
                            @if ($registrationsOpen ?? false)
                                <li><a class="btn btn--glow" href="{{ route('register') }}">Rejoindre Tsukimori</a></li>
                            @endif
                        @endauth
                    </ul>
                </div>
            </nav>

            <div class="site-tools">
                <details class="tool-menu tool-menu--search" data-nav-menu>
                    <summary class="tool-button" title="Rechercher"><x-picto name="loupe" /><span class="visually-hidden">Rechercher</span></summary>
                    <div class="tool-menu__panel">
                        <form class="header-search" method="GET" action="{{ route('search') }}" role="search">
                            <label class="visually-hidden" for="recherche-entete">Rechercher sur le site</label>
                            <input id="recherche-entete" type="search" name="q" placeholder="Un personnage, une scène, un lieu…" value="{{ request()->routeIs('search') ? request('q') : '' }}" maxlength="100">
                            <button type="submit" class="btn btn--small">Chercher</button>
                        </form>
                    </div>
                </details>

                @auth
                    @if ($verified)
                        <a class="tool-button nav-counter" href="{{ route('notifications.index') }}" data-counter-url="{{ route('notifications.counts') }}" title="Notifications" @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                            <x-picto name="lanterne" /><span class="visually-hidden">Notifications</span>
                            <span class="counter" data-counter="notifications" @if ($unreadNotifications === 0) hidden @endif>{{ $unreadNotifications }}<span class="visually-hidden"> non lues</span></span>
                        </a>
                        <a class="tool-button nav-counter" href="{{ route('messages.index') }}" title="Messages" @if (request()->routeIs('messages.*')) aria-current="page" @endif>
                            <x-picto name="enveloppe" /><span class="visually-hidden">Messages</span>
                            <span class="counter" data-counter="messages" @if ($unreadMessages === 0) hidden @endif>{{ $unreadMessages }}<span class="visually-hidden"> non lus</span></span>
                        </a>
                    @endif

                    <details class="tool-menu tool-menu--account" data-nav-menu>
                        <summary class="tool-button tool-button--account" title="Mon espace">
                            <x-picto name="compte" />
                            @if ($activeCharacter)
                                <span class="tool-button__name">{{ $activeCharacter->first_name ?: $activeCharacter->fullName() }}</span>
                            @endif
                            <span class="visually-hidden">Mon espace</span>
                        </summary>
                        <div class="tool-menu__panel account-menu">
                            <p class="account-menu__who">
                                <span class="account-menu__pseudo">{{ $user->pseudo }}</span>
                                @if ($activeCharacter)
                                    <a class="active-chip" href="{{ route('dashboard') }}#personnage" title="Personnage actif : changer depuis le tableau de bord">
                                        <span class="active-chip__dot" aria-hidden="true"></span><span class="visually-hidden">Personnage actif : </span>{{ $activeCharacter->fullName() }}
                                    </a>
                                @endif
                            </p>
                            <ul class="account-menu__links">
                                <li><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                                @if ($verified)
                                    <li><a href="{{ route('characters.mine') }}">Mes personnages</a></li>
                                    <li><a href="{{ route('scenes.mine') }}">Mes scènes</a></li>
                                    <li><a href="{{ route('relationships.index') }}">Mes liens</a></li>
                                @endif
                                <li><a href="{{ route('account.edit') }}" @if (request()->routeIs('account.*')) aria-current="page" @endif>Mon compte</a></li>
                                @if ($user->isStaff())
                                    <li><a href="{{ url('/admin') }}">Administration</a></li>
                                @endif
                            </ul>
                            <form method="POST" action="{{ route('logout') }}" class="account-menu__logout">
                                @csrf
                                <button type="submit" class="link-button">Se déconnecter</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a class="tool-link" href="{{ route('login') }}">Connexion</a>
                    @if ($registrationsOpen ?? false)
                        <a class="btn btn--small btn--glow tool-join" href="{{ route('register') }}">Rejoindre</a>
                    @endif
                @endauth

                <button type="button" class="nav-toggle" data-nav-toggle aria-controls="site-nav" aria-expanded="false">
                    <x-picto name="menu" class="nav-toggle__open" /><x-picto name="fermer" class="nav-toggle__close" />
                    <span class="nav-toggle__label">Menu</span>
                </button>
            </div>
        </div>
    </header>

    <main id="contenu" tabindex="-1">
        {{ $slot }}
    </main>

    @if ($verified)
        {{-- Téléphone d'abord : l'espace joueur à portée de pouce. --}}
        <nav class="player-bar" aria-label="Espace joueur">
            <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif><x-picto name="maison" />Accueil</a>
            <a href="{{ route('scenes.mine') }}" @if (request()->routeIs('scenes.*')) aria-current="page" @endif><x-picto name="plume" />Scènes</a>
            <a href="{{ route('messages.index') }}" @if (request()->routeIs('messages.*')) aria-current="page" @endif><x-picto name="enveloppe" />Messages
                <span class="counter" data-counter="messages" @if ($unreadMessages === 0) hidden @endif>{{ $unreadMessages }}<span class="visually-hidden"> non lus</span></span></a>
            <a href="{{ route('faceclaims.index') }}" @if (request()->routeIs('faceclaims.*')) aria-current="page" @endif><x-picto name="livre" />Bottin</a>
            <a href="{{ route('characters.mine') }}" @if (request()->routeIs('characters.mine', 'characters.edit', 'characters.profile.*')) aria-current="page" @endif><x-picto name="compte" />Profil</a>
        </nav>
    @endif

    @unless (request()->routeIs('contact.*'))
        <x-contact-bubble />
    @endunless

    <footer class="site-footer">
        <x-sakura-branch class="site-footer__branch" />
        <div class="container site-footer__inner">
            <div class="site-footer__brand">
                <p class="site-footer__kanji" lang="ja">{{ config('hollow.kanji') }}</p>
                <p class="site-footer__name">{{ $siteName }} — {{ config('hollow.tagline') }}</p>
                <p class="site-footer__motto">{{ config('hollow.motto') }}</p>
            </div>
            <nav class="site-footer__column" aria-label="Explorer Tsukimori">
                <p class="site-footer__title">Explorer</p>
                <ul>
                    <li><a href="{{ route('universe.index') }}">Le contexte</a></li>
                    <li><a href="{{ route('families.index') }}">Les maisons</a></li>
                    <li><a href="{{ route('species.index') }}">Les espèces</a></li>
                    <li><a href="{{ route('city.index') }}">La carte</a></li>
                    <li><a href="{{ route('gazette.index') }}">La Gazette</a></li>
                    <li><a href="{{ route('archives.index') }}">Archives municipales</a></li>
                </ul>
            </nav>
            <nav class="site-footer__column" aria-label="Jouer à Tsukimori">
                <p class="site-footer__title">Jouer</p>
                <ul>
                    <li><a href="{{ route('pages.rejoindre') }}">Rejoindre</a></li>
                    <li><a href="{{ route('pages.faq') }}">Questions fréquentes</a></li>
                    <li><a href="{{ route('predefined.index') }}">Rôles prédéfinis</a></li>
                    <li><a href="{{ route('faceclaims.index') }}">Bottin des avatars</a></li>
                    <li><a href="{{ route('ads.index') }}">Petites annonces</a></li>
                    <li><a href="{{ route('pages.application') }}">Installer l’application</a></li>
                </ul>
            </nav>
            <nav class="site-footer__column" aria-label="Pages officielles">
                <p class="site-footer__title">Le site</p>
                <ul>
                    <li><a href="{{ route('contact.create') }}">Contacter l’équipe</a></li>
                    <li><a href="{{ route('pages.reglement') }}">Règlement</a></li>
                    <li><a href="{{ route('pages.mentions-legales') }}">Mentions légales</a></li>
                    <li><a href="{{ route('pages.confidentialite') }}">Confidentialité</a></li>
                    <li><a href="{{ route('pages.conditions') }}">Conditions d’utilisation</a></li>
                </ul>
            </nav>
        </div>
        <div class="container site-footer__bottom">
            <ul class="site-footer__toggles">
                <li><button type="button" class="link-button" data-theme-toggle aria-pressed="false" hidden>Mode parchemin</button></li>
                <li><button type="button" class="link-button" data-motion-toggle aria-pressed="false" hidden>Réduire les animations</button></li>
            </ul>
            @if (($illustrationsCredit ?? '') !== '')
                <p class="site-footer__credit">{{ $illustrationsCredit }}</p>
            @endif
        </div>
    </footer>
</body>
</html>
