<x-auth-card title="Double authentification" intro="Saisissez le code à six chiffres affiché par votre application d’authentification.">
    <form class="form" method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
        @csrf

        <x-field name="code" label="Code de l’application" inputmode="numeric" autocomplete="one-time-code" class="code-input" maxlength="6" autofocus />

        <div class="form-actions">
            <button type="submit" class="btn">Valider</button>
        </div>
    </form>

    <details class="stack">
        <summary>Je n’ai plus accès à mon application</summary>
        <form class="form" method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
            @csrf
            <x-field name="recovery_code" label="Code de secours" autocomplete="off" hint="L’un des codes conservés lors de l’activation. Chaque code ne sert qu’une fois." />
            <div class="form-actions">
                <button type="submit" class="btn btn--ghost">Utiliser ce code de secours</button>
            </div>
        </form>
    </details>
</x-auth-card>
