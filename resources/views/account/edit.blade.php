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
                <section id="preferences" class="card stack" aria-labelledby="titre-preferences">
                    <h2 id="titre-preferences" class="card__title">Notifications et e-mails</h2>
                    <p class="muted">Toutes les notifications s’affichent sur le site, sous la lanterne. Choisissez ici celles qui vous arrivent aussi par e-mail, et celles qui s’affichent sur vos téléphones et ordinateurs abonnés (voir ci-dessous).</p>
                    <form class="form" method="POST" action="{{ route('account.preferences') }}">
                        @csrf
                        @method('PUT')
                        <div class="preferences">
                            @foreach (config('hollow.notifications.types') as $type => $definition)
                                <div class="preferences__row">
                                    <label for="pref-{{ $type }}">{{ $definition['label'] }}</label>
                                    <select id="pref-{{ $type }}" name="notifications[{{ $type }}]">
                                        @foreach (config('hollow.notifications.modes') as $mode => $label)
                                            <option value="{{ $mode }}" @selected($user->notificationMode($type) === $mode)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label class="checkbox preferences__push" for="push-{{ $type }}">
                                        <input type="checkbox" id="push-{{ $type }}" name="push[{{ $type }}]" value="1" @checked($user->wantsPush($type))>
                                        Sur mes appareils
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <p class="hint">Le résumé quotidien part chaque matin, s’il y a du nouveau. Les e-mails liés au compte (vérification, mot de passe) sont toujours envoyés.</p>
                        <div class="form-actions">
                            <button type="submit" class="btn">Enregistrer</button>
                        </div>
                    </form>
                </section>

                {{-- Notifications push : abonnement de l'appareil, appareils abonnés, essai --}}
                @php
                    $pushKey = app(\App\Services\PushSender::class)->publicKey();
                    $pushDevices = $user->pushSubscriptions()->latest('updated_at')->get();
                @endphp
                <section id="notifications-appareils" class="card stack" aria-labelledby="titre-push">
                    <h2 id="titre-push" class="card__title">Notifications sur vos appareils</h2>
                    @if ($pushKey)
                        <p class="muted">Recevez sur ce téléphone ou cet ordinateur, même site fermé, une alerte pour une réponse, un message ou une décision de l’équipe. Elle ne montre qu’un titre, jamais le texte d’un RP ou d’un message.</p>
                        <div class="push-panel" data-push data-push-key="{{ $pushKey }}" data-push-store="{{ route('push.store') }}" data-push-destroy="{{ route('push.destroy') }}">
                            <p class="push-panel__status" data-push-status role="status">Vérification de cet appareil…</p>
                            <div class="cluster">
                                <button type="button" class="btn" data-push-enable hidden>Activer sur cet appareil</button>
                                <button type="button" class="btn btn--ghost" data-push-disable hidden>Désactiver sur cet appareil</button>
                            </div>
                            <p class="hint" data-push-ios hidden>Sur iPhone et iPad, les notifications fonctionnent une fois Tsukimori <a href="{{ route('pages.application') }}">ajouté à l’écran d’accueil</a> : ouvrez-le depuis son icône, puis revenez ici.</p>
                            <p class="hint" data-push-denied hidden>Les notifications sont bloquées pour Tsukimori dans ce navigateur. Autorisez-les dans les réglages du site (l’icône à gauche de l’adresse, ou Réglages › Notifications sur téléphone), puis rechargez la page.</p>
                            <p class="hint" data-push-unsupported hidden>Ce navigateur ne prend pas en charge les notifications push.</p>
                            @csrf
                        </div>

                        @if ($pushDevices->isNotEmpty())
                            <h3 class="push-panel__title">Appareils abonnés</h3>
                            <ul class="session-list">
                                @foreach ($pushDevices as $device)
                                    <li>
                                        <span>{{ $device->device ?: 'Appareil' }}</span>
                                        <span class="muted">
                                            depuis le {{ $device->created_at->locale('fr')->isoFormat('LL') }}
                                            @if ($device->last_used_at) · dernière alerte {{ $device->last_used_at->locale('fr')->diffForHumans() }} @endif
                                        </span>
                                        <form method="POST" action="{{ route('push.forget', $device) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button">Retirer</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                            <form method="POST" action="{{ route('push.test') }}">
                                @csrf
                                <button type="submit" class="btn btn--ghost">Envoyer une notification d’essai</button>
                            </form>
                        @endif
                    @else
                        <p class="muted">Les notifications sur téléphone et ordinateur ne sont pas encore activées sur le site. Les notifications sous la lanterne et les e-mails fonctionnent normalement.</p>
                    @endif
                </section>

                <section id="donnees" class="card stack" aria-labelledby="titre-donnees">
                    <h2 id="titre-donnees" class="card__title">Mes données</h2>
                    <p class="muted">Télécharger tout ce que vous avez écrit, ou supprimer votre compte.</p>
                    <div><a class="btn btn--ghost" href="{{ route('account.data') }}">Ouvrir « Mes données »</a></div>
                </section>

                <section id="bloques" class="card stack" aria-labelledby="titre-bloques">
                    <h2 id="titre-bloques" class="card__title">Joueurs bloqués</h2>
                    @php($blocks = \App\Models\UserBlock::query()->with('blocked')->where('user_id', $user->id)->latest('id')->get())
                    <form method="POST" action="{{ route('blocks.store') }}" class="cluster">
                        @csrf
                        <label for="champ-bloquer" class="visually-hidden">Pseudo à bloquer</label>
                        <input id="champ-bloquer" type="text" name="pseudo" maxlength="30" placeholder="Pseudo" required>
                        <button type="submit" class="btn btn--ghost">Bloquer</button>
                    </form>
                    @error('pseudo')<p class="error">{{ $message }}</p>@enderror
                    @if ($blocks->isEmpty())
                        <p class="muted">Vous n’avez bloqué personne. Un blocage empêche les messages, les invitations de scène et les propositions de lien, dans les deux sens.</p>
                    @else
                        <ul class="plain-list stack stack--tight">
                            @foreach ($blocks as $block)
                                <li class="cluster">
                                    <span>{{ $block->blocked?->pseudo ?? 'Compte supprimé' }}</span>
                                    <form method="POST" action="{{ route('blocks.destroy', $block) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-button">Débloquer</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

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
