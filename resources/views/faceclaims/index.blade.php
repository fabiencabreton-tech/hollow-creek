<x-layout title="Le bottin des avatars" description="Les personnalités réelles qui prêtent leurs traits aux personnages de Hollow Creek.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Le bottin' => null]" />
            <h1>Le bottin des avatars</h1>
            <p class="lede">Chaque personnage emprunte les traits d’une personnalité réelle. Une même personnalité ne peut représenter qu’un seul personnage : vérifiez sa disponibilité, puis réservez-la.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            <form class="filters" method="GET" action="{{ route('faceclaims.index') }}" role="search">
                <div class="field">
                    <label for="recherche-bottin">Rechercher une personnalité</label>
                    <input id="recherche-bottin" type="search" name="q" value="{{ $search }}" placeholder="Prénom et nom" maxlength="120">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--ghost">Rechercher</button>
                </div>
            </form>

            @if ($search !== '')
                <div class="card stack">
                    @php($active = $exactMatch?->activeClaim)
                    @if ($active)
                        <p><strong>{{ $exactMatch->name }}</strong> : <x-status-badge :status="$active->status" /></p>
                    @else
                        <p><strong>{{ $exactMatch?->name ?? $search }}</strong> : <span class="badge badge--ok">Disponible</span></p>
                        @auth
                            @if (auth()->user()->hasVerifiedEmail())
                                <form method="POST" action="{{ route('faceclaims.store') }}" class="cluster">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $exactMatch?->name ?? $search }}">
                                    <button type="submit" class="btn">Demander la réservation</button>
                                    <span class="muted">L’équipe valide la demande ; la réservation dure {{ app(\App\Support\Settings::class)->reservationDays() }} jours, prolongeable une fois.</span>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('faceclaims.guest') }}" class="stack">
                                @csrf
                                <x-honeypot />
                                <input type="hidden" name="name" value="{{ $exactMatch?->name ?? $search }}">
                                <p class="muted">Pas encore de compte ? Demandez la réservation avec votre adresse e-mail : un lien de confirmation vous sera envoyé, et la réservation rejoindra votre compte quand vous vous inscrirez avec la même adresse. <a href="{{ route('login') }}">Déjà inscrit : connexion</a>.</p>
                                <div class="form-grid">
                                    <x-field name="guest_pseudo" label="Pseudo" required maxlength="30" :value="old('guest_pseudo')" />
                                    <x-field name="guest_email" label="Adresse e-mail" type="email" required maxlength="190" :value="old('guest_email')" autocomplete="email" />
                                </div>
                                <div><button type="submit" class="btn">Demander la réservation</button></div>
                            </form>
                        @endauth
                    @endif
                </div>
            @endif

            @if ($myClaims->isNotEmpty())
                <section class="card stack" aria-labelledby="titre-mes-demandes">
                    <h2 id="titre-mes-demandes" class="card__title">Mes demandes</h2>
                    <ul class="stack plain-list">
                        @foreach ($myClaims as $claim)
                            <li class="cluster">
                                <strong>{{ $claim->faceclaim->name }}</strong>
                                <x-status-badge :status="$claim->status" />
                                @if ($claim->expires_at)
                                    <span class="muted">jusqu’au {{ $claim->expires_at->locale('fr')->isoFormat('LL') }}</span>
                                @endif
                                @if ($claim->canBeExtended())
                                    <form method="POST" action="{{ route('faceclaims.extend', $claim) }}">
                                        @csrf
                                        <button type="submit" class="link-button">Prolonger</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('faceclaims.destroy', $claim) }}" data-confirm="Annuler cette demande ? La personnalité redeviendra disponible.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link-button">Annuler</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="titre-bottin">
                <h2 id="titre-bottin" class="section__title">Avatars utilisés ou demandés</h2>
                @if ($faceclaims->isEmpty())
                    <p class="muted">{{ $search !== '' ? 'Aucune personnalité utilisée ne correspond à cette recherche.' : 'Aucun avatar n’est encore utilisé.' }}</p>
                @else
                    <table class="directory">
                        <thead>
                            <tr><th scope="col">Personnalité</th><th scope="col">Statut</th><th scope="col">Personnage</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($faceclaims as $faceclaim)
                                <tr>
                                    <td>{{ $faceclaim->name }}</td>
                                    <td><x-status-badge :status="$faceclaim->activeClaim->status" /></td>
                                    <td>
                                        @if ($faceclaim->activeClaim->status === \App\Enums\ClaimStatus::Taken && $faceclaim->activeClaim->character)
                                            <a href="{{ route('characters.show', $faceclaim->activeClaim->character) }}">{{ $faceclaim->activeClaim->character->fullName() }}</a>
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $faceclaims->links('partials.pagination') }}
                @endif
            </section>
        </div>
    </div>
</x-layout>
