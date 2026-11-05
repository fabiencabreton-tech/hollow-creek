@php
    use App\Enums\ParticipantStatus;
    use App\Enums\SceneStatus;
    use App\Enums\TurnMode;

    $user = auth()->user();
    $active = $scene->participants->where('status', ParticipantStatus::Accepted);
    $invited = $scene->participants->where('status', ParticipantStatus::Invited);
    $nextParticipant = $scene->turn_mode === TurnMode::Rounds && $scene->next_user_id
        ? $scene->participants->firstWhere('user_id', $scene->next_user_id)
        : null;
    $warnings = $scene->warningLabels();
@endphp
<x-layout :title="$scene->title" :robots="$scene->isPrivate() ? 'noindex' : null">
    @if (session('clear_local'))
        <div hidden data-clear-local-key="{{ session('clear_local') }}"></div>
    @endif

    <div class="container">
        <header class="page-header scene-header">
            <x-breadcrumbs :items="['Les scènes' => route('scenes.index'), $scene->title => null]" />
            <div class="cluster">
                <p class="eyebrow">{{ $scene->rp_date ?: 'Scène' }}@if ($scene->location) · <a href="{{ route('locations.show', $scene->location) }}">{{ $scene->location->name }}</a>@endif</p>
                <x-status-badge :status="$scene->status" />
                @if ($scene->isPrivate())
                    <span class="badge">Privée</span>
                @endif
                @if ($scene->turn_mode === TurnMode::Rounds)
                    <span class="badge">Tour par tour</span>
                @endif
            </div>
            <h1>{{ $scene->title }}</h1>
            @if ($scene->event && $scene->event->is_published)
                <p class="muted">Événement : <a href="{{ route('events.show', $scene->event) }}">{{ $scene->event->title }}</a></p>
            @endif

            <p class="scene-cast">
                @foreach ($active as $p)
                    @if ($p->character)
                        <span class="scene-cast__member">
                            <x-portrait :character="$p->character" size="tiny" />
                            @if ($p->character->status === \App\Enums\CharacterStatus::Accepted)
                                <a href="{{ route('characters.show', $p->character) }}">{{ $p->character->fullName() }}</a>
                            @else
                                {{ $p->character->fullName() }}
                            @endif
                        </span>
                    @endif
                @endforeach
            </p>

            @if ($warnings !== [])
                <p class="scene-warnings" role="note"><strong>Avertissements :</strong> {{ implode(' · ', $warnings) }}</p>
            @endif
        </header>

        <div class="stack">
            <x-flash />
            <x-errors-summary />
        </div>

        @if ($participant?->status === ParticipantStatus::Invited)
            <section class="card stack invitation-card" aria-labelledby="titre-invitation">
                <h2 id="titre-invitation" class="card__title">Vous êtes invité dans cette scène</h2>
                @if ($myCharacters->isEmpty())
                    <p class="muted">Il vous faut un personnage accepté pour la rejoindre.</p>
                @else
                    <form method="POST" action="{{ route('scenes.invitations.accept', [$scene, $participant]) }}" class="cluster">
                        @csrf
                        <label for="champ-invitation-personnage" class="visually-hidden">Personnage</label>
                        <select id="champ-invitation-personnage" name="character_id">
                            @foreach ($myCharacters as $character)
                                <option value="{{ $character->id }}" @selected($participant->character_id === $character->id)>{{ $character->fullName() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn">Accepter</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('scenes.invitations.decline', [$scene, $participant]) }}">
                    @csrf
                    <button type="submit" class="link-button">Décliner l’invitation</button>
                </form>
            </section>
        @endif

        <div class="scene-layout">
            <div class="scene-reading" data-reading>
                <div class="reading-tools cluster">
                    @if ($firstUnreadId && $posts->getCollection()->doesntContain('id', $firstUnreadId))
                        <a class="btn btn--ghost btn--small" href="{{ route('scenes.unread', $scene) }}">Première réponse non lue</a>
                    @endif
                    <span class="js-only reading-tools__size" role="group" aria-label="Taille du texte">
                        <button type="button" class="editor__tool" data-reading-size="-1" title="Réduire le texte">A−</button>
                        <button type="button" class="editor__tool" data-reading-size="1" title="Agrandir le texte">A+</button>
                    </span>
                    @can('export', $scene)
                        <a class="link-button" href="{{ route('scenes.export', $scene) }}">Télécharger la scène</a>
                    @endcan
                </div>

                @forelse ($posts as $post)
                    @if ($post->id === $firstUnreadId)
                        <p class="post__unread-marker" id="non-lu">Nouvelles réponses</p>
                    @endif
                    <article id="reponse-{{ $post->id }}" @class(['post', 'post--c-'.$post->character?->dialogue_color => $post->character?->dialogue_color])>
                        <header class="post__header">
                            @if ($post->character)
                                <x-portrait :character="$post->character" size="small" />
                            @endif
                            <div>
                                <p class="post__character">
                                    @if ($post->character?->status === \App\Enums\CharacterStatus::Accepted)
                                        <a href="{{ route('characters.show', $post->character) }}">{{ $post->character_name }}</a>
                                    @else
                                        {{ $post->character_name }}
                                    @endif
                                </p>
                                <p class="post__meta">
                                    {{ $post->author?->pseudo ?? 'Ancien membre' }} ·
                                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->locale('fr')->isoFormat('LLL') }}</time>
                                    @if ($post->edited_at)
                                        · modifiée le {{ $post->edited_at->locale('fr')->isoFormat('LLL') }}
                                    @endif
                                    · {{ $post->word_count }} mots
                                </p>
                            </div>
                        </header>
                        <div class="post__body prose">{{ $post->html() }}</div>
                        <footer class="post__footer cluster">
                            @can('update', $post)
                                <a class="link-button" href="{{ route('scenes.posts.edit', [$scene, $post]) }}">Modifier</a>
                            @endcan
                            <x-report-link type="reponse" :id="$post->id" :owner="$post->user_id" />
                        </footer>
                    </article>
                @empty
                    <p class="empty-note">Cette scène n’a pas encore de réponse.</p>
                @endforelse

                {{ $posts->links('partials.pagination') }}
            </div>

            <aside class="scene-aside stack" aria-label="Participer">
                @if ($scene->status === SceneStatus::Paused)
                    <p class="alert alert--warning">La scène est en pause : on ne peut pas y répondre pour le moment.</p>
                @elseif (in_array($scene->status, [SceneStatus::Finished, SceneStatus::Archived], true))
                    <p class="muted">Cette scène est {{ mb_strtolower($scene->status->getLabel()) }}.</p>
                @endif

                @if ($nextParticipant && $scene->status === SceneStatus::Ongoing)
                    <p class="turn-note">{{ $nextParticipant->user_id === $user?->id ? 'C’est à vous d’écrire.' : 'Au tour de '.($nextParticipant->character?->fullName() ?? $nextParticipant->user?->pseudo).'.' }}</p>
                @endif

                @can('join', $scene)
                    @if ($myCharacters->isNotEmpty())
                        <form method="POST" action="{{ route('scenes.join', $scene) }}" class="card stack">
                            @csrf
                            <p class="card__title">Cette scène est ouverte</p>
                            <label for="champ-rejoindre">Rejoindre avec</label>
                            <select id="champ-rejoindre" name="character_id">
                                @foreach ($myCharacters as $character)
                                    <option value="{{ $character->id }}">{{ $character->fullName() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn">Rejoindre la scène</button>
                        </form>
                    @endif
                @endcan

                @can('manage', $scene)
                    <details class="card stack scene-manage" @if ($errors->has('character_id') || $errors->has('status')) open @endif>
                        <summary class="card__title">Gérer la scène</summary>

                        <form method="POST" action="{{ route('scenes.status', $scene) }}" class="stack stack--tight">
                            @csrf
                            <label for="champ-statut">Statut</label>
                            <select id="champ-statut" name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($scene->status === $status)>{{ $status->getLabel() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn--ghost">Changer le statut</button>
                        </form>

                        @can('invite', $scene)
                            @if ($partners->isNotEmpty())
                                <form method="POST" action="{{ route('scenes.invite', $scene) }}" class="stack stack--tight">
                                    @csrf
                                    <label for="champ-inviter">Inviter un partenaire</label>
                                    <select id="champ-inviter" name="character_id">
                                        @foreach ($partners as $partner)
                                            <option value="{{ $partner->id }}">{{ $partner->fullName() }} — {{ $partner->user?->pseudo }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn--ghost">Inviter</button>
                                </form>
                            @endif
                        @endcan

                        @if ($invited->isNotEmpty())
                            <div class="stack stack--tight">
                                <p>Invitations en attente</p>
                                <ul class="plain-list stack stack--tight">
                                    @foreach ($invited as $invitation)
                                        <li class="cluster">
                                            <span>{{ $invitation->user?->pseudo }}@if ($invitation->character) ({{ $invitation->character->fullName() }})@endif</span>
                                            <form method="POST" action="{{ route('scenes.invitations.cancel', [$scene, $invitation]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button">Retirer</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </details>
                @endcan

                @can('leave', $scene)
                    <form method="POST" action="{{ route('scenes.leave', $scene) }}" data-confirm="Quitter cette scène ? Vos réponses restent dans l’histoire.">
                        @csrf
                        <button type="submit" class="link-button">Quitter la scène</button>
                    </form>
                @endcan
            </aside>
        </div>

        @can('draft', $scene)
            <section id="ecrire" class="card stack scene-reply" aria-labelledby="titre-ecrire">
                <h2 id="titre-ecrire" class="card__title">Écrire</h2>

                @if ($preview)
                    <div class="editor__preview card">
                        <p class="eyebrow">Aperçu</p>
                        <div class="post__body prose">{{ $preview }}</div>
                    </div>
                @endif

                <form class="form" method="POST" action="{{ route('scenes.reply', $scene) }}" novalidate>
                    @csrf
                    <x-scene-editor
                        :value="old('body', $draft?->body ?? '')"
                        :draft-url="route('scenes.draft', $scene)"
                        :local-key="'hollow-creek-scene-'.$scene->id"
                        :saved-at="$draft?->updated_at?->getTimestampMs()"
                        :label="'Réponse de '.($participant?->character?->fullName() ?? 'votre personnage')" />

                    <div class="form-actions">
                        @can('reply', $scene)
                            <button type="submit" class="btn btn--large">Publier la réponse</button>
                        @else
                            <p class="muted">{{ $scene->status === SceneStatus::Paused ? 'Scène en pause.' : 'Ce n’est pas encore votre tour : votre brouillon est gardé.' }}</p>
                        @endcan
                        <button type="submit" class="btn btn--ghost" name="action" value="apercu" data-editor-preview>Aperçu</button>
                    </div>
                    <p class="hint">J’écris moi-même chacune de mes réponses : aucun texte généré par une intelligence artificielle.</p>
                </form>
            </section>
        @endcan
    </div>
</x-layout>
