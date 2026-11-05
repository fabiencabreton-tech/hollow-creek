{{-- Gabarit autonome des pages d'erreur : aucune requête à la base, aucune session requise. --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('hollow.name', 'Hollow Creek') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if (is_file(public_path('build/manifest.json')) || is_file(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="{{ url('/') }}">{{ config('hollow.name', 'Hollow Creek') }}</a>
        </div>
    </header>
    <main class="error-page" id="contenu">
        <div class="container stack">
            @include('partials.stopped-clock')
            <p class="error-code">@yield('code')</p>
            <h1>@yield('heading')</h1>
            <p class="muted">@yield('message')</p>
            @hasSection('actions')
                <div class="cluster">@yield('actions')</div>
            @else
                <div class="cluster"><a class="btn btn--ghost" href="{{ url('/') }}">Revenir à l’accueil</a></div>
            @endif
        </div>
    </main>
</body>
</html>
