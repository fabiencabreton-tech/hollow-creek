<x-auth-card title="Nouveau mot de passe">
    <x-errors-summary />

    <form class="form" method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-field name="email" label="Adresse e-mail" type="email" autocomplete="email" required :value="old('email', $request->query('email'))" />
        <x-field name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required
            hint="12 caractères au minimum." />
        <x-field name="password_confirmation" label="Confirmez le mot de passe" type="password" autocomplete="new-password" required />

        <div class="form-actions">
            <button type="submit" class="btn">Enregistrer le mot de passe</button>
        </div>
    </form>
</x-auth-card>
