<x-auth-card title="Confirmez votre mot de passe" intro="Cette action touche à la sécurité de votre compte : merci de saisir à nouveau votre mot de passe.">
    <form class="form" method="POST" action="{{ route('password.confirm.store') }}" novalidate>
        @csrf

        <x-field name="password" label="Mot de passe" type="password" autocomplete="current-password" required autofocus />

        <div class="form-actions">
            <button type="submit" class="btn">Confirmer</button>
        </div>
    </form>
</x-auth-card>
