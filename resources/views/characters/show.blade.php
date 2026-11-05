@php
    $viewer = auth()->user();
    $isOwner = $viewer && $viewer->id === $character->user_id;
    $layout = $character->profile_layout ?: 'classique';
    $ongoing = $scenes->whereIn('status', [\App\Enums\SceneStatus::Ongoing, \App\Enums\SceneStatus::Paused]);
    $done = $scenes->where('status', \App\Enums\SceneStatus::Finished);
    $natureDetails = $character->natureDetails();
@endphp
<x-layout :title="$character->fullName()" :robots="$isPreview ? 'noindex' : null">
    <div @class(['profile-page', 'profile-page--'.$layout, 'accent--'.$character->accent_color => $character->accent_color])>
        @if ($character->bannerUrl())
            <figure class="profile-banner">
                <img src="{{ $character->bannerUrl() }}" alt="" decoding="async">
                @if ($character->banner_credit)<figcaption>{{ $character->banner_credit }}</figcaption>@endif
            </figure>
        @endif

        <div class="container">
            <header class="page-header profile-header">
                <x-breadcrumbs :items="['Les personnages' => route('characters.index'), $character->fullName() => null]" />
                @if ($isPreview)
                    <p class="preview-banner" role="status">Aperçu : cette fiche ({{ $character->status->getLabel() }}) n’est pas encore publique.</p>
                @endif
                <x-flash />
                <x-errors-summary />
            </header>

            <div class="profile">
                <aside class="profile__aside" aria-label="Portrait et identité">
                    <x-portrait :character="$character" size="large" />
                    @if ($character->avatar_credit)
                        <p class="profile__credit">{{ $character->faceclaim?->name }}@if ($character->faceclaim) · @endif{{ $character->avatar_credit }}</p>
                    @endif
                    <dl class="facts">
                        <dt>Âge</dt><dd>{{ $character->age }} ans @if ($character->apparent_age && $character->apparent_age !== $character->age) (en paraît {{ $character->apparent_age }})@endif</dd>
                        <dt>Nature</dt><dd>@if ($character->species)<a href="{{ route('species.show', $character->species) }}">{{ $character->species->name }}</a>@else — @endif</dd>
                        @if ($character->traits->isNotEmpty())
                            <dt>Particularités</dt><dd>@foreach ($character->traits as $trait)<a href="{{ route('species.show', $trait) }}">{{ $trait->name }}</a>@if (! $loop->last), @endif @endforeach</dd>
                        @endif
                        @if ($character->kirisawaVictim)
                            <dt>Registre</dt><dd><a href="{{ route('kirisawa.index') }}#victime-{{ $character->kirisawaVictim->number }}">Victime n°{{ $character->kirisawaVictim->number }}</a></dd>
                        @endif
                        @if ($character->death_date)
                            <dt>Mort le</dt><dd>{{ $character->death_date->locale('fr')->isoFormat('LL') }}</dd>
                        @endif
                        <dt>Famille</dt><dd>@if ($character->family)<a href="{{ route('families.show', $character->family) }}">{{ $character->family->name }}</a>@else Indépendant @endif</dd>
                        <dt>Profession</dt><dd>{{ $character->profession ?: '—' }}</dd>
                        <dt>Joué par</dt><dd>{{ $character->user?->pseudo }}</dd>
                    </dl>

                    @if ($isOwner && $character->status === \App\Enums\CharacterStatus::Accepted)
                        <a class="btn btn--ghost" href="{{ route('characters.profile.edit', $character) }}">Personnaliser le profil</a>
                    @elseif ($viewer && ! $isOwner && $character->user && ! $isPreview)
                        <div class="stack stack--tight profile__actions">
                            <a class="btn btn--ghost" href="{{ route('messages.create', ['a' => $character->user->pseudo]) }}">Écrire à {{ $character->user->pseudo }}</a>
                            <x-report-link type="profil" :id="$character->id" :owner="$character->user_id" />
                        </div>
                    @endif
                </aside>

                <div class="profile__main">
                    <p class="eyebrow">{{ $character->species?->name ?? 'Personnage' }}</p>
                    <h1>{{ $character->fullName() }}</h1>
                    @if ($character->quote)
                        <blockquote class="profile__quote">« {{ $character->quote }} »</blockquote>
                    @endif

                    @if ($character->isAbsent())
                        <p class="absence-note" role="note"><strong>Absent·e</strong>@if ($character->absence_until) jusqu’au {{ $character->absence_until->locale('fr')->isoFormat('LL') }}@endif : {{ $character->absence_note }}</p>
                    @endif

                    <nav class="profile-tabs js-only" aria-label="Sections du profil" data-tabs>
                        <a href="#histoire" data-tab>Histoire</a>
                        <a href="#caractere" data-tab>Caractère</a>
                        @if ($natureDetails !== [])
                            <a href="#nature" data-tab>Nature</a>
                        @endif
                        <a href="#liens" data-tab>Liens</a>
                        <a href="#rp" data-tab>RP</a>
                        <a href="#publications" data-tab>Publications</a>
                    </nav>

                    <section id="histoire" class="entry__section" data-tab-panel>
                        <h2>Histoire</h2>
                        @if ($character->history)
                            <div class="prose">{{ \App\Support\Markdown::render($character->history) }}</div>
                        @else
                            <p class="muted">Non rédigée.</p>
                        @endif
                    </section>

                    <section id="caractere" class="entry__section" data-tab-panel>
                        <h2>Caractère</h2>
                        @if ($character->personality)
                            <div class="prose">{{ \App\Support\Markdown::render($character->personality) }}</div>
                        @else
                            <p class="muted">Non rédigé.</p>
                        @endif
                    </section>

                    @if ($natureDetails !== [])
                        <section id="nature" class="entry__section" data-tab-panel>
                            <h2>Nature</h2>
                            <dl class="nature-details">
                                @foreach ($natureDetails as $label => $value)
                                    <dt>{{ $label }}</dt>
                                    <dd>{{ $value }}</dd>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    <section id="liens" class="entry__section" data-tab-panel>
                        <h2>Liens</h2>
                        @if ($relationships->isEmpty())
                            <p class="muted">Aucun lien pour le moment.</p>
                        @else
                            <ul class="plain-list relation-list">
                                @foreach ($relationships as $relationship)
                                    @php($other = $relationship->otherSide($character))
                                    @if ($other)
                                        <li class="relation-list__item">
                                            <x-portrait :character="$other" size="small" />
                                            <div>
                                                <p><a href="{{ route('characters.show', $other) }}">{{ $other->fullName() }}</a> · <span class="muted">{{ $relationship->label() }}</span></p>
                                                @if ($relationship->descriptionFor($character))
                                                    <p class="relation-list__text">{{ $relationship->descriptionFor($character) }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif

                        @if ($myCharacters->isNotEmpty() && $character->status === \App\Enums\CharacterStatus::Accepted)
                            <details class="card stack">
                                <summary>Proposer un lien</summary>
                                <form method="POST" action="{{ route('relationships.store') }}" class="stack">
                                    @csrf
                                    <input type="hidden" name="target_character_id" value="{{ $character->id }}">
                                    <div class="form-grid">
                                        <div class="field">
                                            <label for="champ-lien-personnage">Votre personnage</label>
                                            <select id="champ-lien-personnage" name="character_id">
                                                @foreach ($myCharacters as $mine)
                                                    <option value="{{ $mine->id }}">{{ $mine->fullName() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for="champ-lien-type">Type de lien</label>
                                            <select id="champ-lien-type" name="kind">
                                                @foreach (\App\Enums\RelationshipKind::cases() as $kind)
                                                    <option value="{{ $kind->value }}">{{ $kind->getLabel() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <x-field name="label" label="Libellé libre (facultatif)" maxlength="60" hint="Par exemple « Ancienne colocataire » ou « Dette impayée »." />
                                    <div class="field">
                                        <label for="champ-lien-description">Votre description du lien (facultatif)</label>
                                        <textarea id="champ-lien-description" name="description" rows="3" maxlength="2000"></textarea>
                                    </div>
                                    <div class="form-actions"><button type="submit" class="btn">Proposer</button></div>
                                </form>
                            </details>
                        @endif
                    </section>

                    <section id="rp" class="entry__section" data-tab-panel>
                        <h2>RP</h2>
                        <h3 class="profile__subtitle">En cours</h3>
                        @if ($ongoing->isEmpty())
                            <p class="muted">Aucune scène publique en cours.</p>
                        @else
                            <ul class="plain-list stack stack--tight">
                                @foreach ($ongoing as $scene)
                                    <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a><span class="muted"> · {{ $scene->rp_date }}@if ($scene->location) · {{ $scene->location->name }}@endif</span></li>
                                @endforeach
                            </ul>
                        @endif
                        <h3 class="profile__subtitle">RP terminés</h3>
                        @if ($done->isEmpty())
                            <p class="muted">Aucune scène publique terminée.</p>
                        @else
                            <ul class="plain-list stack stack--tight">
                                @foreach ($done as $scene)
                                    <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a><span class="muted"> · {{ $scene->rp_date }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section id="publications" class="entry__section" data-tab-panel>
                        <h2>Publications</h2>
                        @forelse ($publications as $post)
                            @include('feed.partials.post', ['post' => $post])
                        @empty
                            <p class="muted">Aucune publication.</p>
                        @endforelse
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layout>
