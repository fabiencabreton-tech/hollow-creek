<x-layout title="Tableau de bord" robots="noindex">
    <div class="container">
        <header class="page-header">
            <p class="eyebrow">{{ $rpDate }} · date RP</p>
            <h1>{{ $greeting }}, {{ $user->pseudo }}</h1>
        </header>

        <div class="stack">
            @if (request()->boolean('verified'))
                <div class="alert alert--success" role="status"><p>Votre adresse e-mail est vérifiée : bienvenue à Hollow Creek.</p></div>
            @endif
            <x-flash />
        </div>

        <div class="dashboard-grid">
            <section class="card stack" aria-labelledby="titre-etapes">
                <h2 id="titre-etapes" class="card__title">Premiers pas</h2>
                <ul class="checklist">
                    <li>
                        <span class="state state--done" aria-hidden="true"></span>
                        <span>Compte créé et adresse e-mail vérifiée.</span>
                    </li>
                    <li>
                        <span class="state" aria-hidden="true"></span>
                        <span><a href="{{ route('pages.reglement') }}">Lire le règlement</a> de Hollow Creek.</span>
                    </li>
                    <li>
                        <span class="state {{ $hasTwoFactor ? 'state--done' : '' }}" aria-hidden="true"></span>
                        <span>
                            @if ($hasTwoFactor)
                                Double authentification activée.
                            @else
                                Protéger votre compte avec la <a href="{{ route('account.edit') }}#double-authentification">double authentification</a> (conseillé).
                            @endif
                        </span>
                    </li>
                </ul>
            </section>

            <section class="card stack" aria-labelledby="titre-personnages">
                <h2 id="titre-personnages" class="card__title">Vos personnages</h2>
                <p class="muted">La création des fiches de personnage ouvrira prochainement. Vous pourrez alors en gérer plusieurs depuis ce même compte.</p>
            </section>

            @if ($isStaff)
                <section class="card stack" aria-labelledby="titre-equipe">
                    <h2 id="titre-equipe" class="card__title">Équipe</h2>
                    @if ($hasTwoFactor)
                        <p class="muted">Votre accès à l’administration est actif.</p>
                        <div><a class="btn" href="{{ url('/admin') }}">Ouvrir l’administration</a></div>
                    @else
                        <p class="muted">L’administration exige la double authentification. Activez-la sur votre compte pour y accéder.</p>
                        <div><a class="btn" href="{{ route('account.edit') }}#double-authentification">Activer la double authentification</a></div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-layout>
