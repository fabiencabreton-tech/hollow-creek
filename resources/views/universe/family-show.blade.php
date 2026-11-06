@php
    $nodeOptions = $treeMembers->sortBy(fn ($member) => $member->displayName());
@endphp
<x-layout :title="$family->name" :description="$family->motto ?: $family->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="family-banner @if ($family->imageUrl()) family-banner--image @endif">
        <div class="container family-banner__inner">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les grandes familles' => route('families.index'), $family->name => null]" />
            <x-preview-banner :show="$isPreview" />
            <div class="family-banner__title">
                <span class="family-card__seal family-card__seal--large" lang="ja" aria-hidden="true">{{ $family->initial() }}</span>
                <div class="stack stack--tight">
                    <p class="eyebrow">{{ $family->is_major ? 'Grande famille de Tsukimori' : 'Famille de joueurs' }}</p>
                    <h1>{{ $family->name }} @if ($family->japanese_name)<span class="family-banner__kanji" lang="ja">{{ $family->japanese_name }}</span>@endif</h1>
                    @if ($family->motto)
                        <p class="family-banner__motto">« {{ $family->motto }} »</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stack page-body">
            <x-flash />
            <x-errors-summary />
        </div>

        <div class="entry family-entry">
            <div class="stack stack--loose">
                <dl class="family-facts card">
                    <div>
                        <dt>Quartier</dt>
                        <dd>
                            @if ($district)<a href="{{ route('districts.show', $district) }}">{{ $district->name }}</a>@endif
                            @if ($family->district_note){{ $district ? ' — ' : '' }}{{ $family->district_note }}@endif
                            @if (! $district && ! $family->district_note) — @endif
                        </dd>
                    </div>
                    @if ($family->activities)
                        <div><dt>Activités</dt><dd>{{ $family->activities }}</dd></div>
                    @endif
                    @if ($family->reputation)
                        <div><dt>Réputation</dt><dd>{{ $family->reputation }}</dd></div>
                    @endif
                    <div>
                        <dt>Nouveaux membres</dt>
                        <dd>{{ $family->is_open ? 'Bienvenus : la famille accueille de nouveaux personnages.' : 'La famille n’accueille personne pour le moment.' }}</dd>
                    </div>
                </dl>

                @if ($family->summary)
                    <p class="lede">{{ $family->summary }}</p>
                @endif

                @if ($family->history)
                    <section class="entry__section" aria-labelledby="histoire">
                        <h2 id="histoire" class="section__title">Histoire</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($family->history) }}</div>
                    </section>
                @endif

                @if ($family->kirisawa_link)
                    <section class="entry__section" aria-labelledby="kirisawa">
                        <h2 id="kirisawa" class="section__title">Lien avec Kirisawa</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($family->kirisawa_link) }}</div>
                    </section>
                @endif

                @if ($family->rp_notes)
                    <section class="entry__section" aria-labelledby="jouer">
                        <h2 id="jouer" class="section__title">Jouer un membre de la famille</h2>
                        <div class="prose">{{ \App\Support\Markdown::render($family->rp_notes) }}</div>
                    </section>
                @endif

                @if ($related->isNotEmpty())
                    <section class="entry__section" aria-labelledby="liens-familles">
                        <h2 id="liens-familles" class="section__title">Liens avec les autres familles</h2>
                        <ul class="plain-list family-relations">
                            @foreach ($related as $row)
                                <li>
                                    <a href="{{ route('families.show', $row['family']) }}" class="family-relations__name">{{ $row['family']->name }}</a>
                                    <span>{{ $row['summary'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="muted">Ce sont des bases narratives : chaque joueur reste libre de créer ses propres liens, romances, rivalités et conflits.</p>
                    </section>
                @endif

                <section class="entry__section" aria-labelledby="arbre">
                    <h2 id="arbre" class="section__title">L’arbre généalogique</h2>
                    @if ($tree['nodes'] === [])
                        <p class="muted">L’arbre se remplira à mesure que des personnages rejoindront la famille.</p>
                    @else
                        <p class="muted">Survolez ou parcourez au clavier un membre pour éclairer sa lignée. Les noms soulignés sont des personnages joués.</p>
                        <div class="family-tree" data-family-tree>
                            <svg class="family-tree__svg" viewBox="0 0 {{ $tree['width'] }} {{ $tree['height'] }}" width="{{ $tree['width'] }}" height="{{ $tree['height'] }}" role="group" aria-label="Arbre généalogique : {{ count($tree['nodes']) }} membres">
                                @foreach ($tree['edges'] as $edge)
                                    <path class="family-tree__edge family-tree__edge--{{ $edge['kind'] }}" d="{{ $edge['d'] }}" data-from="{{ $edge['from'] }}" data-to="{{ $edge['to'] }}" />
                                @endforeach
                                @foreach ($tree['nodes'] as $node)
                                    @php
                                        $label = $node['name'].($node['deceased'] ? ' (décédé·e)' : '').($node['note'] ? ' — '.$node['note'] : '');
                                    @endphp
                                    @if ($node['url'])
                                        <a href="{{ $node['url'] }}" class="family-tree__node family-tree__node--played" data-node="{{ $node['id'] }}" data-parents="{{ implode(',', $node['parents']) }}" data-spouse="{{ $node['spouse'] }}" aria-label="{{ $label }}">
                                    @else
                                        <g class="family-tree__node @if ($node['deceased']) family-tree__node--deceased @endif" data-node="{{ $node['id'] }}" data-parents="{{ implode(',', $node['parents']) }}" data-spouse="{{ $node['spouse'] }}" tabindex="0" role="img" aria-label="{{ $label }}">
                                    @endif
                                        <title>{{ $label }}</title>
                                        <rect x="{{ $node['x'] }}" y="{{ $node['y'] }}" width="{{ \App\Support\FamilyTree::NODE_WIDTH }}" height="{{ \App\Support\FamilyTree::NODE_HEIGHT }}" rx="8" />
                                        <text x="{{ $node['x'] + \App\Support\FamilyTree::NODE_WIDTH / 2 }}" y="{{ $node['y'] + ($node['note'] ? 24 : 34) }}" text-anchor="middle" class="family-tree__name">{{ \Illuminate\Support\Str::limit($node['name'], 22) }}</text>
                                        @if ($node['note'] || $node['deceased'])
                                            <text x="{{ $node['x'] + \App\Support\FamilyTree::NODE_WIDTH / 2 }}" y="{{ $node['y'] + 43 }}" text-anchor="middle" class="family-tree__note">{{ \Illuminate\Support\Str::limit(($node['deceased'] ? '故 ' : '').($node['note'] ?? ''), 26) }}</text>
                                        @endif
                                    @if ($node['url'])
                                        </a>
                                    @else
                                        </g>
                                    @endif
                                @endforeach
                            </svg>
                        </div>

                        <details class="disclosure">
                            <summary class="disclosure__summary">L’arbre sous forme de liste</summary>
                            <ul class="family-tree-list">
                                @foreach ($tree['nodes'] as $node)
                                    @php
                                        $parentNames = collect($node['parents'])->map(fn ($id) => $treeMembers->firstWhere('id', $id)?->displayName())->filter()->join(' et ');
                                        $spouseName = $node['spouse'] ? $treeMembers->firstWhere('id', $node['spouse'])?->displayName() : null;
                                    @endphp
                                    <li>
                                        @if ($node['url'])<a href="{{ $node['url'] }}">{{ $node['name'] }}</a>@else{{ $node['name'] }}@endif
                                        @if ($node['deceased']) (décédé·e)@endif
                                        @if ($parentNames !== '') — enfant de {{ $parentNames }}@endif
                                        @if ($spouseName) — uni·e à {{ $spouseName }}@endif
                                        @if ($node['note']) — {{ $node['note'] }}@endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    @foreach ($myNodes as $node)
                        <details class="card disclosure" @if ($errors->hasAny(['parent_id', 'second_parent_id', 'spouse_id'])) open @endif>
                            <summary class="disclosure__summary">Placer {{ $node->displayName() }} dans l’arbre</summary>
                            <form class="form stack" method="POST" action="{{ route('families.place', [$family, $node]) }}" novalidate>
                                @csrf
                                @method('PUT')
                                <div class="form-grid">
                                    @foreach (['parent_id' => 'Parent', 'second_parent_id' => 'Second parent', 'spouse_id' => 'Conjoint ou conjointe'] as $field => $fieldLabel)
                                        <div class="field">
                                            <label for="champ-{{ $field }}-{{ $node->id }}">{{ $fieldLabel }}</label>
                                            <select id="champ-{{ $field }}-{{ $node->id }}" name="{{ $field }}">
                                                <option value="">Aucun</option>
                                                @foreach ($nodeOptions as $option)
                                                    @continue($option->id === $node->id)
                                                    <option value="{{ $option->id }}" @selected((int) old($field, $node->{$field}) === $option->id)>{{ $option->displayName() }}</option>
                                                @endforeach
                                            </select>
                                            @error($field)<p class="error">{{ $message }}</p>@enderror
                                        </div>
                                    @endforeach
                                </div>
                                <x-field name="note" label="Mention dans l’arbre (facultatif)" maxlength="300" :value="old('note', $node->note)" hint="Par exemple « Héritière de l’atelier »." />
                                <div class="form-actions"><button type="submit" class="btn">Mettre à jour l’arbre</button></div>
                            </form>
                        </details>
                    @endforeach
                </section>

                <section class="entry__section" aria-labelledby="membres">
                    <h2 id="membres" class="section__title">Les membres joués</h2>
                    @if ($members->isEmpty())
                        <p class="muted">Aucun personnage n’appartient encore à cette famille. Le premier rôle vous attend peut-être.</p>
                    @else
                        <ul class="member-list plain-list">
                            @foreach ($members as $member)
                                <li>
                                    <a class="member-list__item" href="{{ route('characters.show', $member) }}">
                                        <x-portrait :character="$member" size="small" />
                                        <span>{{ $member->fullName() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="entry__section" aria-labelledby="recherches">
                    <h2 id="recherches" class="section__title">Membres recherchés et préliens</h2>

                    @if ($predefined->isEmpty() && $prelinks->isEmpty())
                        <p class="muted">Aucun prélien ouvert pour le moment.</p>
                    @endif

                    @if ($predefined->isNotEmpty())
                        <h3>Rôles proposés par l’équipe</h3>
                        <div class="grid-cards">
                            @foreach ($predefined as $role)
                                <a class="tile" href="{{ route('predefined.show', $role) }}">
                                    <span class="tile__eyebrow">Prédéfini</span>
                                    <span class="tile__title">{{ $role->title }}</span>
                                    @if ($role->summary)<span class="tile__text">{{ \Illuminate\Support\Str::limit($role->summary, 140) }}</span>@endif
                                    <span class="tile__more">Lire le rôle</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($prelinks->isNotEmpty())
                        <h3>Préliens des joueurs</h3>
                        <div class="prelink-list">
                            @foreach ($prelinks as $prelink)
                                <article id="prelien-{{ $prelink->id }}" class="prelink card">
                                    <p class="eyebrow">{{ $prelink->relation->getLabel() }} recherché·e</p>
                                    <h4 class="prelink__title">{{ $prelink->title }}</h4>
                                    <p class="muted">
                                        Proposé par <a href="{{ route('characters.show', $prelink->character) }}">{{ $prelink->character->fullName() }}</a>
                                        @if ($prelink->age_range) · {{ $prelink->age_range }}@endif
                                        @if ($prelink->faceclaims) · avatars suggérés : {{ $prelink->faceclaims }}@endif
                                    </p>
                                    <div class="prose">{{ $prelink->html() }}</div>
                                    <div class="cluster">
                                        @auth
                                            @if ($prelink->user_id === auth()->id())
                                                <form method="POST" action="{{ route('prelinks.fill', $prelink) }}" class="cluster">
                                                    @csrf
                                                    <button type="submit" class="link-button">Marquer comme pourvu</button>
                                                </form>
                                                <form method="POST" action="{{ route('prelinks.destroy', $prelink) }}" data-confirm="Retirer ce prélien ?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="link-button">Retirer</button>
                                                </form>
                                            @else
                                                @if ($prelink->author)
                                                    <a href="{{ route('messages.create', ['a' => $prelink->author->pseudo]) }}">Proposer ma candidature en message privé</a>
                                                @endif
                                                @if ($family->is_open)
                                                    <form method="POST" action="{{ route('families.new-character', $family) }}">
                                                        @csrf
                                                        <button type="submit" class="link-button">Créer ce personnage dans la famille</button>
                                                    </form>
                                                @endif
                                            @endif
                                        @endauth
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    @if ($myMembers->isNotEmpty())
                        <details class="card disclosure" @if ($errors->hasAny(['relation', 'title', 'description'])) open @endif>
                            <summary class="disclosure__summary">Proposer un prélien familial</summary>
                            <form class="form stack" method="POST" action="{{ route('prelinks.store', $family) }}" novalidate>
                                @csrf
                                <div class="form-grid">
                                    <div class="field">
                                        <label for="champ-prelien-personnage">Votre personnage</label>
                                        <select id="champ-prelien-personnage" name="character_id">
                                            @foreach ($myMembers as $character)
                                                <option value="{{ $character->id }}" @selected((int) old('character_id') === $character->id)>{{ $character->fullName() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="champ-prelien-lien">Lien recherché</label>
                                        <select id="champ-prelien-lien" name="relation">
                                            @foreach ($relations as $relation)
                                                <option value="{{ $relation->value }}" @selected(old('relation') === $relation->value)>{{ $relation->getLabel() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <x-field name="title" label="Titre" maxlength="120" hint="Par exemple « La sœur aînée partie à Tokyo »." />
                                <div class="field">
                                    <label for="champ-prelien-description">Description</label>
                                    <textarea id="champ-prelien-description" name="description" rows="6" maxlength="4000" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                                    @error('description')<p class="error">{{ $message }}</p>@enderror
                                </div>
                                <div class="form-grid">
                                    <x-field name="age_range" label="Âge (facultatif)" maxlength="40" />
                                    <x-field name="faceclaims" label="Avatars suggérés (facultatif)" maxlength="200" hint="Des personnes réelles uniquement." />
                                </div>
                                <div class="form-actions"><button type="submit" class="btn">Publier le prélien</button></div>
                            </form>
                        </details>
                    @endif
                </section>
            </div>

            <aside class="entry__aside stack stack--loose" aria-label="Rejoindre la famille">
                <x-figure :url="$family->imageUrl()" :credit="$family->image_credit" :alt="$family->name" />

                <section class="card stack family-join" aria-labelledby="rejoindre">
                    <h2 id="rejoindre" class="card__title">Rejoindre les {{ $family->shortName() }}</h2>
                    @if (! $family->is_open)
                        <p class="muted">Cette famille n’accueille personne pour le moment.</p>
                    @elseif (! auth()->check())
                        <p><a href="{{ route('login') }}">Connectez-vous</a> pour créer un personnage dans cette famille.</p>
                    @else
                        <form method="POST" action="{{ route('families.new-character', $family) }}">
                            @csrf
                            <button type="submit" class="btn">Créer un personnage {{ $family->shortName() }}</button>
                        </form>
                        <p class="hint">La fiche s’ouvre avec la famille déjà choisie ; l’équipe valide le lien avec la fiche.</p>

                        @foreach ($pending as $item)
                            <p class="alert">Demande en attente pour {{ $item->character?->fullName() }}.</p>
                        @endforeach

                        @php $candidates = $myCharacters->where('family_id', '!==', $family->id)->values(); @endphp
                        @if ($candidates->isNotEmpty())
                            <form class="form stack" method="POST" action="{{ route('families.join', $family) }}" novalidate>
                                @csrf
                                <p class="hint">Ou faites entrer un personnage déjà accepté (mariage, adoption, parenté découverte…).</p>
                                <div class="field">
                                    <label for="champ-rejoindre-personnage">Personnage</label>
                                    <select id="champ-rejoindre-personnage" name="character_id">
                                        @foreach ($candidates as $character)
                                            <option value="{{ $character->id }}">{{ $character->fullName() }}{{ $character->family_id ? ' (quitte sa famille actuelle)' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('character_id')<p class="error">{{ $message }}</p>@enderror
                                </div>
                                <div class="field">
                                    <label for="champ-rejoindre-message">Votre demande</label>
                                    <textarea id="champ-rejoindre-message" name="message" rows="3" maxlength="2000" placeholder="Quel lien avec la famille ?"></textarea>
                                </div>
                                <div><button type="submit" class="btn btn--ghost">Demander à rejoindre</button></div>
                            </form>
                        @endif

                        @foreach ($myMembers as $character)
                            <form method="POST" action="{{ route('families.leave', $character) }}" data-confirm="{{ $character->fullName() }} quittera la famille. Continuer ?">
                                @csrf
                                <button type="submit" class="link-button">{{ $character->fullName() }} quitte la famille</button>
                            </form>
                        @endforeach
                    @endif
                </section>

                @if ($others->isNotEmpty())
                    <nav class="card stack" aria-label="Autres familles">
                        <p class="eyebrow">Autres familles</p>
                        <ul class="stack stack--tight plain-list">
                            @foreach ($others as $other)
                                <li><a href="{{ route('families.show', $other) }}">{{ $other->name }}</a> @if ($other->japanese_name)<span lang="ja" class="muted">{{ $other->japanese_name }}</span>@endif</li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
