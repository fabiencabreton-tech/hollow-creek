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
    <meta name="description" content="{{ $description ?? 'Hollow Creek — Ceux qui reviennent : un RPG littéraire surnaturel, écrit par des humains, pour des humains.' }}">
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
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="{{ route('home') }}">{{ $siteName }}</a>

            <button type="button" class="nav-toggle" data-nav-toggle aria-controls="site-nav" aria-expanded="false">
                Menu
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Navigation principale">
                <ul class="site-nav__links">
                    <li><a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Accueil</a></li>
                    <li><a href="{{ route('pages.reglement') }}" @if (request()->routeIs('pages.reglement')) aria-current="page" @endif>Règlement</a></li>
                    @auth
                        <li><a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Tableau de bord</a></li>
                    @endauth
                </ul>

                <ul class="site-nav__account">
                    @auth
                        @if ($user->isStaff())
                            <li><a href="{{ url('/admin') }}">Administration</a></li>
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

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <div class="stack stack--tight">
                <p class="site-footer__name">{{ $siteName }}</p>
                <p class="site-footer__motto">{{ config('hollow.motto') }}</p>
            </div>
            <nav aria-label="Pages officielles">
                <ul>
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
