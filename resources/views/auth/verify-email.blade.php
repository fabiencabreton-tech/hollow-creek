<x-auth-card title="Vérifiez votre adresse e-mail" intro="Un lien de vérification vient de vous être envoyé. Cliquez dessus pour activer votre compte. Pensez à regarder dans les courriers indésirables.">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="form-actions">
            <button type="submit" class="btn">Renvoyer le lien</button>
            <a href="{{ route('account.edit') }}">Corriger mon adresse e-mail</a>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="link-button">Se déconnecter</button>
    </form>
</x-auth-card>
