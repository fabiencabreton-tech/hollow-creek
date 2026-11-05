{{-- Bulle de contact : une personne de l'équipe répond toujours elle-même. --}}
@php($online = app(\App\Services\ContactDesk::class)->staffOnline())
<details class="contact-bubble">
    <summary class="contact-bubble__toggle">
        <span aria-hidden="true">✉</span><span class="contact-bubble__label">Écrire à l’équipe</span>
    </summary>
    <div class="contact-bubble__panel" role="group" aria-label="Contacter l’équipe">
        <p class="contact-bubble__status">
            <span @class(['contact-bubble__dot', 'is-online' => $online]) aria-hidden="true"></span>
            {{ $online ? 'Un membre de l’équipe est en ligne.' : app(\App\Support\Settings::class)->contactDelay() }}
        </p>
        <p class="muted">Pas de robot ici : une personne de l’équipe lit et répond à chaque demande.</p>
        <ul class="plain-list stack stack--tight">
            <li><a href="{{ route('contact.create', ['motif' => 'question']) }}">Poser une question</a></li>
            <li><a href="{{ route('faceclaims.index') }}">Réserver un avatar</a></li>
            <li><a href="{{ route('contact.create', ['motif' => 'predefini']) }}">Un prédéfini m’intéresse</a></li>
            <li><a href="{{ route('contact.create', ['motif' => 'probleme']) }}">Signaler un problème</a></li>
            @auth
                <li><a class="muted-link" href="{{ route('contact.mine') }}">Mes demandes</a></li>
            @else
                <li><a class="muted-link" href="{{ route('contact.resend') }}">Retrouver le suivi d’une demande</a></li>
            @endauth
        </ul>
    </div>
</details>
