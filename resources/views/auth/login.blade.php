<x-auth-card title="Connexion" intro="Retrouvez vos personnages et vos scènes.">
    <form class="form" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <x-honeypot />

        <x-field name="email" label="Adresse e-mail ou pseudo" autocomplete="username" required autofocus />
        <x-field name="password" label="Mot de passe" type="password" autocomplete="current-password" required />

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Rester connecté sur cet appareil</span>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn">Se connecter</button>
            <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
        </div>
    </form>

    @if ($registrationsOpen ?? false)
        <p class="muted">Pas encore de compte ? <a href="{{ route('register') }}">Rejoindre Hollow Creek</a></p>
    @endif
</x-auth-card>
