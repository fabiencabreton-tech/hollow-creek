<x-auth-card title="Mot de passe oublié" intro="Indiquez l’adresse e-mail de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe, valable 60 minutes.">
    <form class="form" method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <x-honeypot />

        <x-field name="email" label="Adresse e-mail" type="email" autocomplete="email" required autofocus />

        <div class="form-actions">
            <button type="submit" class="btn">Recevoir le lien</button>
            <a href="{{ route('login') }}">Retour à la connexion</a>
        </div>
    </form>
</x-auth-card>
