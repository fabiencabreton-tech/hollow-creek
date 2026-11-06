<x-layout title="Les grandes familles de Tsukimori" description="Minase, Fujisawa, Tachibana, Katsuragi : les familles anciennes de Tsukimori, et celles que créent les joueurs.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les grandes familles' => null]" />
            <h1>Les grandes familles de Tsukimori</h1>
            <p class="lede">Des noms que tout le monde connaît en ville. Les rejoindre n’est jamais obligatoire : vous pouvez aussi créer votre propre famille, ou un habitant sans attaches.</p>
        </header>

        <div class="stack stack--loose page-body">
            <x-flash />
            <x-errors-summary />

            @if ($major->isEmpty())
                <p class="empty-note">Les grandes familles seront présentées ici très bientôt.</p>
            @else
                <div class="family-grid">
                    @foreach ($major as $family)
                        <a class="family-card" href="{{ route('families.show', $family) }}">
                            <span class="family-card__seal" lang="ja" aria-hidden="true">{{ $family->initial() }}</span>
                            <span class="family-card__body">
                                <span class="family-card__name">{{ $family->name }} @if ($family->japanese_name)<span class="family-card__kanji" lang="ja">{{ $family->japanese_name }}</span>@endif</span>
                                @if ($family->motto)
                                    <span class="family-card__motto">« {{ $family->motto }} »</span>
                                @endif
                                <span class="family-card__meta">
                                    @if ($family->district?->is_published){{ $family->district->name }} · @endif
                                    {{ $family->members_count }} {{ $family->members_count > 1 ? 'membres joués' : 'membre joué' }}
                                </span>
                                @if ($family->summary)
                                    <span class="family-card__text">{{ $family->summary }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($page)
                <section class="prose families-intro" aria-label="À propos des familles">{{ \App\Support\Markdown::render($page->body) }}</section>
            @endif

            @if ($others->isNotEmpty())
                <section aria-labelledby="familles-joueurs">
                    <h2 id="familles-joueurs" class="section__title">Familles des joueurs</h2>
                    <div class="grid-cards">
                        @foreach ($others as $family)
                            <a class="tile" href="{{ route('families.show', $family) }}">
                                <span class="tile__eyebrow">{{ $family->members_count }} {{ $family->members_count > 1 ? 'membres' : 'membre' }}</span>
                                <span class="tile__title">{{ $family->name }} @if ($family->japanese_name)<span lang="ja">{{ $family->japanese_name }}</span>@endif</span>
                                @if ($family->summary)
                                    <span class="tile__text">{{ \Illuminate\Support\Str::limit($family->summary, 160) }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @auth
                <div class="families-actions">
                    @if ($myCharacters->isNotEmpty())
                        <details class="card disclosure" @if ($errors->hasAny(['proposed_name', 'proposed_summary', 'proposed_japanese_name'])) open @endif>
                            <summary class="disclosure__summary">Proposer une nouvelle famille</summary>
                            <form class="form stack" method="POST" action="{{ route('families.propose') }}" novalidate>
                                @csrf
                                <p class="hint">Votre personnage en sera le premier membre. L’équipe relit chaque proposition.</p>
                                <div class="field">
                                    <label for="champ-proposition-personnage">Premier membre</label>
                                    <select id="champ-proposition-personnage" name="character_id">
                                        @foreach ($myCharacters as $character)
                                            <option value="{{ $character->id }}" @selected((int) old('character_id') === $character->id)>{{ $character->fullName() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-grid">
                                    <x-field name="proposed_name" label="Nom de la famille" maxlength="60" hint="Par exemple « Sakuma »." />
                                    <x-field name="proposed_japanese_name" label="En japonais (facultatif)" maxlength="20" hint="Par exemple « 佐久間 »." />
                                </div>
                                <div class="field">
                                    <label for="champ-proposition-presentation">Présentation</label>
                                    <textarea id="champ-proposition-presentation" name="proposed_summary" rows="5" maxlength="2000" @error('proposed_summary') aria-invalid="true" @enderror>{{ old('proposed_summary') }}</textarea>
                                    @error('proposed_summary')<p class="error">{{ $message }}</p>@enderror
                                </div>
                                <div class="field">
                                    <label for="champ-proposition-message">Un mot pour l’équipe (facultatif)</label>
                                    <textarea id="champ-proposition-message" name="message" rows="2" maxlength="2000">{{ old('message') }}</textarea>
                                </div>
                                <div class="form-actions"><button type="submit" class="btn">Envoyer la proposition</button></div>
                            </form>
                        </details>
                    @endif

                    @if ($myRequests->isNotEmpty())
                        <section class="card stack" aria-labelledby="mes-demandes-famille">
                            <h2 id="mes-demandes-famille" class="card__title">Mes demandes</h2>
                            <ul class="plain-list stack">
                                @foreach ($myRequests as $item)
                                    <li class="cluster">
                                        <span>{{ $item->label() }} · {{ $item->character?->fullName() }} — <strong>{{ $item->status->getLabel() }}</strong></span>
                                        @if ($item->status === \App\Enums\FamilyRequestStatus::Pending)
                                            <form method="POST" action="{{ route('family-requests.cancel', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button">Annuler</button>
                                            </form>
                                        @elseif ($item->response)
                                            <span class="muted">« {{ $item->response }} »</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @endauth
        </div>
    </div>
</x-layout>
