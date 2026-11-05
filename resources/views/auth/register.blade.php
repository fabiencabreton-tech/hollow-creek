<x-auth-card title="Rejoindre Hollow Creek" intro="Un seul compte suffit pour jouer plusieurs personnages.">
    @if ($registrationsOpen)
        <x-errors-summary />

        <form class="form" method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf
            <x-honeypot />

            <x-field name="pseudo" label="Pseudo" autocomplete="nickname" required maxlength="{{ config('hollow.accounts.pseudo_max') }}"
                hint="Votre nom de joueur, visible des autres membres (pas celui d’un personnage). {{ config('hollow.accounts.pseudo_min') }} à {{ config('hollow.accounts.pseudo_max') }} caractères." />
            <x-field name="email" label="Adresse e-mail" type="email" autocomplete="email" required
                hint="Elle reste privée. Un lien de vérification vous sera envoyé." />
            <x-field name="password" label="Mot de passe" type="password" autocomplete="new-password" required
                hint="12 caractères au minimum. Une phrase de passe est idéale." />
            <x-field name="password_confirmation" label="Confirmez le mot de passe" type="password" autocomplete="new-password" required />

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="age" value="1" required @checked(old('age'))>
                    <span>J’atteste avoir au moins {{ config('hollow.accounts.minimum_age') }} ans.</span>
                </label>
                @error('age')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                    <span>J’accepte le <a href="{{ route('pages.reglement') }}" target="_blank" rel="noopener">règlement</a>, les <a href="{{ route('pages.conditions') }}" target="_blank" rel="noopener">conditions d’utilisation</a> et la <a href="{{ route('pages.confidentialite') }}" target="_blank" rel="noopener">politique de confidentialité</a>.</span>
                </label>
                @error('terms')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Créer mon compte</button>
                <a href="{{ route('login') }}">J’ai déjà un compte</a>
            </div>
        </form>
    @else
        <div class="alert alert--warning" role="status">
            <p>Les inscriptions sont fermées pour le moment. Elles ouvriront avec la bêta : revenez bientôt.</p>
        </div>
        <p><a href="{{ route('login') }}">J’ai déjà un compte</a></p>
    @endif
</x-auth-card>
