<x-layout title="Demande envoyée" robots="noindex">
    <div class="container">
        <header class="page-header">
            <h1>Demande envoyée</h1>
            @if ($reference)
                <p class="lede">Votre référence : <strong>{{ $reference }}</strong>. Un e-mail de confirmation vient de partir, avec un lien personnel pour suivre la conversation et répondre.</p>
            @else
                <p class="lede">Un e-mail de confirmation vient de partir, avec un lien personnel pour suivre la conversation.</p>
            @endif
        </header>
        <p class="page-body"><a href="{{ route('home') }}">Retour à l’accueil</a></p>
    </div>
</x-layout>
