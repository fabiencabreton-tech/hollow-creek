<x-layout title="Mes données" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Mon compte' => route('account.edit'), 'Mes données' => null]" />
            <h1>Mes données</h1>
            <p class="lede">Tout ce que vous avez écrit à Tsukimori vous appartient : vous pouvez le télécharger à tout moment, et supprimer votre compte.</p>
        </header>

        <div class="stack page-body stack--loose">
            <section class="card stack" aria-labelledby="titre-export">
                <h2 id="titre-export" class="card__title">Télécharger mes données</h2>
                <p class="muted">Vos personnages, réponses, brouillons, publications, commentaires, messages envoyés, liens et demandes à l’équipe.</p>
                <div class="cluster">
                    <a class="btn" href="{{ route('account.data.html') }}">Fichier lisible (HTML)</a>
                    <a class="btn btn--ghost" href="{{ route('account.data.json') }}">Format réutilisable (JSON)</a>
                </div>
            </section>

            <section class="card stack danger-zone" aria-labelledby="titre-suppression">
                <h2 id="titre-suppression" class="card__title">Supprimer mon compte</h2>
                <p>La suppression est définitive. Vos personnages sont supprimés et leurs avatars redeviennent disponibles. Vos messages et demandes à l’équipe disparaissent de votre espace.</p>

                <form class="form" method="POST" action="{{ route('account.data.destroy') }}" novalidate data-confirm="Supprimer définitivement votre compte ?">
                    @csrf
                    @method('DELETE')

                    <fieldset class="field">
                        <legend>Vos réponses dans les scènes et vos publications</legend>
                        <label class="checkbox"><input type="radio" name="texts" value="anonymiser" @checked(old('texts', 'anonymiser') === 'anonymiser')> <span>Les laisser, signées « Ancien membre », pour ne pas trouer l’histoire de vos partenaires (conseillé)</span></label>
                        <label class="checkbox"><input type="radio" name="texts" value="effacer" @checked(old('texts') === 'effacer')> <span>Les effacer aussi, ainsi que mes messages privés</span></label>
                    </fieldset>

                    <x-field name="password" label="Mot de passe" type="password" bag="deleteAccount" id="suppression-mdp" autocomplete="current-password" required />
                    <x-field name="confirmation" label="Écrivez SUPPRIMER pour confirmer" bag="deleteAccount" id="suppression-confirmation" autocomplete="off" required />

                    <div class="form-actions">
                        <button type="submit" class="btn btn--danger">Supprimer définitivement mon compte</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layout>
