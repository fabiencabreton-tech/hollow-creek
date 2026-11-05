@php
    $twoFactorPending = filled($user->two_factor_secret) && ! $user->two_factor_confirmed_at;
    $twoFactorActive = $user->hasConfirmedTwoFactor();
    $showRecoveryCodes = $twoFactorActive && in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true);
@endphp
<x-layout title="Mon compte" robots="noindex">
    <div class="container">
        <header class="page-header">
            <p class="eyebrow">Espace joueur</p>
            <h1>Mon compte</h1>
        </header>

        <div class="account-layout">
            <ul class="account-menu" aria-label="Rubriques du compte">
                <li><a href="#profil">Pseudo et e-mail</a></li>
                <li><a href="#mot-de-passe">Mot de passe</a></li>
                <li><a href="#double-authentification">Double authentification</a></li>
                <li><a href="#appareils">Appareils connectés</a></li>
            </ul>

            <div class="account-sections">
                <x-flash />

                @unless ($user->hasVerifiedEmail())
                    <div class="alert alert--warning" role="status">
                        <div class="stack">
                            <p>Votre adresse e-mail n’est pas encore vérifiée.</p>
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn--ghost">Renvoyer le lien de vérification</button>
                            </form>
                        </div>
                    </div>
                @endunless

                {{-- Pseudo et e-mail --}}
                <section id="profil" class="card stack" aria-labelledby="titre-profil">
                    <h2 id="titre-profil" class="card__title">Pseudo et adresse e-mail</h2>
                    <form class="form" method="POST" action="{{ route('user-profile-information.update') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <x-field name="pseudo" label="Pseudo" bag="updateProfileInformation" autocomplete="nickname" required
                            :value="old('pseudo', $user->pseudo)" maxlength="{{ config('hollow.accounts.pseudo_max') }}" />
                        <x-field name="email" label="Adresse e-mail" type="email" bag="updateProfileInformation" autocomplete="email" required
                            :value="old('email', $user->email)" hint="Si vous la changez, il faudra vérifier la nouvelle adresse." />
                        <div class="form-actions">
                            <button type="submit" class="btn">Enregistrer</button>
                        </div>
                    </form>
                </section>

                {{-- Mot de passe --}}
                <section id="mot-de-passe" class="card stack" aria-labelledby="titre-mdp">
                    <h2 id="titre-mdp" class="card__title">Mot de passe</h2>
                    <form class="form" method="POST" action="{{ route('user-password.update') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <x-field name="current_password" label="Mot de passe actuel" type="password" bag="updatePassword" autocomplete="current-password" required />
                        <x-field name="password" label="Nouveau mot de passe" type="password" bag="updatePassword" id="nouveau-mdp" autocomplete="new-password" required
                            hint="12 caractères au minimum." />
                        <x-field name="password_confirmation" label="Confirmez le nouveau mot de passe" type="password" bag="updatePassword" id="nouveau-mdp-confirmation" autocomplete="new-password" required />
                        <div class="form-actions">
                            <button type="submit" class="btn">Changer le mot de passe</button>
                        </div>
                    </form>
                </section>

                {{-- Double authentification --}}
                <section id="double-authentification" class="card stack" aria-labelledby="titre-2fa">
                    <div class="cluster">
                        <h2 id="titre-2fa" class="card__title">Double authentification</h2>
                        @if ($twoFactorActive)
                            <span class="badge badge--ok">Activée</span>
                        @elseif ($twoFactorPending)
                            <span class="badge badge--wait">À confirmer</span>
                        @else
                            <span class="badge">Désactivée</span>
                        @endif
                    </div>
                    <p class="muted">À chaque connexion, un code à six chiffres fourni par une application d’authentification (Aegis, Google Authenticator, 2FAS, Proton Pass…) vous sera demandé en plus du mot de passe.
                        @if ($user->isStaff()) <strong>Elle est obligatoire pour accéder à l’administration.</strong> @endif
                    </p>

                    @if (! $passwordRecentlyConfirmed)
                        <div>
                            <a class="btn" href="{{ route('account.security') }}">{{ $twoFactorActive ? 'Gérer la double authentification' : 'Activer la double authentification' }}</a>
                        </div>
                        <p class="muted">Votre mot de passe vous sera d’abord demandé.</p>
                    @elseif ($twoFactorPending)
                        <ol class="stack">
                            <li>Scannez ce code avec votre application d’authentification :
                                <div class="qr-code" role="img" aria-label="Code QR à scanner">{!! $user->twoFactorQrCodeSvg() !!}</div>
                            </li>
                            <li>Ou saisissez cette clé à la main : <span class="secret-key">{{ \Laravel\Fortify\Fortify::currentEncrypter()->decrypt($user->two_factor_secret) }}</span></li>
                            <li>Puis indiquez le code à six chiffres affiché par l’application.</li>
                        </ol>
                        <form class="form" method="POST" action="{{ route('two-factor.confirm') }}" novalidate>
                            @csrf
                            <x-field name="code" label="Code à six chiffres" bag="confirmTwoFactorAuthentication" inputmode="numeric" autocomplete="one-time-code" class="code-input" maxlength="6" required />
                            <div class="form-actions">
                                <button type="submit" class="btn">Confirmer l’activation</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-button">Annuler l’activation</button>
                        </form>
                    @elseif ($twoFactorActive)
                        @if ($showRecoveryCodes)
                            <div class="stack">
                                <p><strong>Codes de secours.</strong> Notez-les ou imprimez-les maintenant et gardez-les en lieu sûr : chacun permet une connexion si vous perdez votre téléphone, une seule fois.</p>
                                <ul class="recovery-codes" id="codes-de-secours">
                                    @foreach ($user->recoveryCodes() as $code)
                                        <li>{{ $code }}</li>
                                    @endforeach
                                </ul>
                                <div><button type="button" class="btn btn--ghost" data-copy-target="codes-de-secours" hidden>Copier les codes</button></div>
                            </div>
                        @endif
                        <div class="cluster">
                            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                                @csrf
                                <button type="submit" class="btn btn--ghost">Créer de nouveaux codes de secours</button>
                            </form>
                            <form method="POST" action="{{ route('two-factor.disable') }}"
                                data-confirm="{{ $user->isStaff() ? 'Sans double authentification, vous perdrez l’accès à l’administration. Désactiver ?' : 'Désactiver la double authentification ?' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger">Désactiver</button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <button type="submit" class="btn">Activer la double authentification</button>
                        </form>
                    @endif
                </section>

                {{-- Appareils connectés --}}
                <section id="appareils" class="card stack" aria-labelledby="titre-appareils">
                    <h2 id="titre-appareils" class="card__title">Appareils connectés</h2>
                    @if ($sessions->isNotEmpty())
                        <ul class="session-list">
                            @foreach ($sessions as $session)
                                <li>
                                    <span>{{ $session->device }} @if ($session->is_current) <span class="badge badge--ok">Cet appareil</span> @endif</span>
                                    <span class="muted">{{ $session->ip_address }} · actif {{ $session->last_active }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="muted">Un doute sur un appareil ? Déconnectez tous les autres ; il faudra s’y reconnecter avec le mot de passe.</p>
                    <form class="form" method="POST" action="{{ route('account.sessions.destroy') }}" novalidate>
                        @csrf
                        @method('DELETE')
                        <x-field name="password" label="Mot de passe" type="password" bag="logoutOtherSessions" id="mdp-appareils" autocomplete="current-password" required />
                        <div class="form-actions">
                            <button type="submit" class="btn btn--ghost">Déconnecter mes autres appareils</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-layout>
