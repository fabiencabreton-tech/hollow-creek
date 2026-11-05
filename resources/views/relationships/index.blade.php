<x-layout title="Relations" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Relations' => null]" />
            <h1>Relations</h1>
            <p class="lede">Un lien n’apparaît sur les fiches qu’une fois accepté par les deux joueurs. Chacun rédige sa propre description, et chacun peut y mettre fin. Pour proposer un lien, rendez-vous sur le profil du personnage.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            <section aria-labelledby="titre-recues">
                <h2 id="titre-recues" class="section__title">Propositions reçues</h2>
                @forelse ($received as $relationship)
                    <article class="card stack relation-card">
                        <p><strong><a href="{{ route('characters.show', $relationship->character) }}">{{ $relationship->character?->fullName() }}</a></strong>
                            propose un lien <em>{{ $relationship->label() }}</em> à <strong>{{ $relationship->target?->fullName() }}</strong>.</p>
                        @if ($relationship->description)
                            <blockquote class="muted">{{ $relationship->description }}</blockquote>
                        @endif
                        <form method="POST" action="{{ route('relationships.accept', $relationship) }}" class="stack stack--tight">
                            @csrf
                            <label for="desc-{{ $relationship->id }}">Votre description du lien (facultatif)</label>
                            <textarea id="desc-{{ $relationship->id }}" name="description" rows="3" maxlength="2000"></textarea>
                            <div class="cluster">
                                <button type="submit" class="btn">Accepter</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('relationships.decline', $relationship) }}">
                            @csrf
                            <button type="submit" class="link-button">Décliner</button>
                        </form>
                    </article>
                @empty
                    <p class="muted">Aucune proposition en attente.</p>
                @endforelse
            </section>

            <section aria-labelledby="titre-envoyees">
                <h2 id="titre-envoyees" class="section__title">Propositions envoyées</h2>
                @forelse ($sent as $relationship)
                    <div class="cluster">
                        <span>{{ $relationship->character?->fullName() }} → {{ $relationship->target?->fullName() }} · {{ $relationship->label() }}</span>
                        <form method="POST" action="{{ route('relationships.withdraw', $relationship) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-button">Retirer</button>
                        </form>
                    </div>
                @empty
                    <p class="muted">Aucune proposition envoyée en attente.</p>
                @endforelse
            </section>

            <section aria-labelledby="titre-liens">
                <h2 id="titre-liens" class="section__title">Liens actifs</h2>
                @forelse ($active as $relationship)
                    @php($mine = in_array($relationship->character_id, $myIds, true) ? $relationship->character : $relationship->target)
                    <article class="card stack relation-card">
                        <p><strong>{{ $mine?->fullName() }}</strong> · {{ $relationship->label() }} · <a href="{{ route('characters.show', $relationship->otherSide($mine)) }}">{{ $relationship->otherSide($mine)?->fullName() }}</a></p>
                        <form method="POST" action="{{ route('relationships.describe', $relationship) }}" class="stack stack--tight">
                            @csrf
                            @method('PUT')
                            <label for="mine-{{ $relationship->id }}">Votre description</label>
                            <textarea id="mine-{{ $relationship->id }}" name="description" rows="3" maxlength="2000">{{ $relationship->descriptionFor($mine) }}</textarea>
                            <div class="cluster"><button type="submit" class="btn btn--ghost">Enregistrer</button></div>
                        </form>
                        <form method="POST" action="{{ route('relationships.end', $relationship) }}" data-confirm="Mettre fin à ce lien ? Il disparaîtra des deux fiches.">
                            @csrf
                            <button type="submit" class="link-button">Mettre fin au lien</button>
                        </form>
                    </article>
                @empty
                    <p class="muted">Aucun lien pour le moment.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-layout>
