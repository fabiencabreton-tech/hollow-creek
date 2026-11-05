@props([
    'title' => null,
    'description' => null,
    'robots' => null,
])
@php
    $siteName = config('hollow.name');
    $fullTitle = $title ? $title.' · '.$siteName : $siteName.' — '.config('hollow.tagline');
    $user = auth()->user();
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
    <meta name="theme-color" content="#0e1311">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script nonce="{{ Vite::cspNonce() }}">
        document.documentElement.classList.add('js');
        try { if (localStorage.getItem('hollow-creek-theme') === 'parchemin') { document.documentElement.setAttribute('data-theme', 'parchemin'); } } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['has-player-bar' => $user?->hasVerifiedEmail()])>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="{{ route('home') }}">{{ $siteName }} <span class="brand__kanji" lang="ja">{{ config('hollow.kanji') }}</span></a>

            <button type="button" class="nav-toggle" data-nav-toggle aria-controls="site-nav" aria-expanded="false">
                Menu
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Navigation principale">
                <ul class="site-nav__links">
                    <li><a href="{{ route('universe.index') }}" @if (request()->routeIs('universe.*', 'species.*', 'families.*')) aria-current="page" @endif>L’univers</a></li>
                    <li><a href="{{ route('city.index') }}" @if (request()->routeIs('city.*', 'districts.*', 'locations.*')) aria-current="page" @endif>La ville</a></li>
                    <li><a href="{{ route('gazette.index') }}" @if (request()->routeIs('gazette.*')) aria-current="page" @endif>Gazette</a></li>
                    <li><a href="{{ route('scenes.index') }}" @if (request()->routeIs('scenes.index', 'scenes.show', 'scenes.create')) aria-current="page" @endif>Scènes</a></li>
                    <li><a href="{{ route('characters.index') }}" @if (request()->routeIs('characters.index', 'characters.show', 'predefined.*')) aria-current="page" @endif>Personnages</a></li>
                    <li><a href="{{ route('feed.index') }}" @if (request()->routeIs('feed.*')) aria-current="page" @endif>Murmures</a></li>
                    <li><a href="{{ route('events.index') }}" @if (request()->routeIs('events.*')) aria-current="page" @endif>Événements</a></li>
                    <li><a href="{{ route('search') }}" @if (request()->routeIs('search')) aria-current="page" @endif>Rechercher</a></li>
                    @auth
                        <li><a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Tableau de bord</a></li>
                    @endauth
                </ul>

                <ul class="site-nav__account">
                    @auth
                        @if ($user->isStaff())
                            <li><a href="{{ url('/admin') }}">Administration</a></li>
                        @endif
                        @php
                            $unreadNotifications = $user->hasVerifiedEmail() ? app(\App\Services\Notifier::class)->unreadCount($user) : 0;
                            $unreadMessages = $user->hasVerifiedEmail() ? \App\Models\Conversation::unreadCountFor($user) : 0;
                        @endphp
                        @if ($user->hasVerifiedEmail())
                            <li>
                                <a class="nav-counter" href="{{ route('notifications.index') }}" data-counter-url="{{ route('notifications.counts') }}" @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                                    <span class="lantern" aria-hidden="true"></span>Notifications
                                    <span class="counter" data-counter="notifications" @if ($unreadNotifications === 0) hidden @endif>{{ $unreadNotifications }}<span class="visually-hidden"> non lues</span></span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-counter" href="{{ route('messages.index') }}" @if (request()->routeIs('messages.*')) aria-current="page" @endif>
                                    Messages
                                    <span class="counter" data-counter="messages" @if ($unreadMessages === 0) hidden @endif>{{ $unreadMessages }}<span class="visually-hidden"> non lus</span></span>
                                </a>
                            </li>
                        @endif
                        <li><a href="{{ route('account.edit') }}" @if (request()->routeIs('account.*')) aria-current="page" @endif>Mon compte</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="link-button muted-link">Se déconnecter</button>
                            </form>
                        </li>
                    @else
                        <li><a class="muted-link" href="{{ route('login') }}">Connexion</a></li>
                        @if ($registrationsOpen ?? false)
                            <li><a class="btn" href="{{ route('register') }}">Rejoindre</a></li>
                        @endif
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenu" tabindex="-1">
        {{ $slot }}
    </main>

    @if ($user?->hasVerifiedEmail())
        {{-- Téléphone d'abord : l'espace joueur à portée de pouce. --}}
        <nav class="player-bar" aria-label="Espace joueur">
            <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif><span aria-hidden="true">⌂</span>Accueil</a>
            <a href="{{ route('scenes.mine') }}" @if (request()->routeIs('scenes.*')) aria-current="page" @endif><span aria-hidden="true">✎</span>Scènes</a>
            <a href="{{ route('messages.index') }}" @if (request()->routeIs('messages.*')) aria-current="page" @endif><span aria-hidden="true">✉</span>Messages
                <span class="counter" data-counter="messages" @if (($unreadMessages ?? 0) === 0) hidden @endif>{{ $unreadMessages ?? 0 }}<span class="visually-hidden"> non lus</span></span></a>
            <a href="{{ route('faceclaims.index') }}" @if (request()->routeIs('faceclaims.*')) aria-current="page" @endif><span aria-hidden="true">☰</span>Bottin</a>
            <a href="{{ route('characters.mine') }}" @if (request()->routeIs('characters.mine', 'characters.edit', 'characters.profile.*')) aria-current="page" @endif><span aria-hidden="true">☺</span>Profil</a>
        </nav>
    @endif

    @unless (request()->routeIs('contact.*'))
        <x-contact-bubble />
    @endunless

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <div class="stack stack--tight">
                <p class="site-footer__name">{{ $siteName }}</p>
                <p class="site-footer__motto">{{ config('hollow.motto') }}</p>
            </div>
            <nav aria-label="Pages officielles">
                <ul>
                    <li><a href="{{ route('pages.rejoindre') }}">Rejoindre</a></li>
                    <li><a href="{{ route('pages.faq') }}">Questions fréquentes</a></li>
                    <li><a href="{{ route('faceclaims.index') }}">Bottin des avatars</a></li>
                    <li><a href="{{ route('predefined.index') }}">Prédéfinis</a></li>
                    <li><a href="{{ route('contact.create') }}">Contacter l’équipe</a></li>
                    <li><a href="{{ route('pages.reglement') }}">Règlement</a></li>
                    <li><a href="{{ route('pages.mentions-legales') }}">Mentions légales</a></li>
                    <li><a href="{{ route('pages.confidentialite') }}">Confidentialité</a></li>
                    <li><a href="{{ route('pages.conditions') }}">Conditions d’utilisation</a></li>
                    <li><button type="button" class="link-button" data-theme-toggle aria-pressed="false" hidden>Mode parchemin</button></li>
                </ul>
            </nav>
        </div>
    </footer>
</body>
</html>
