{{--
    Éditeur de texte des scènes : Markdown limité (gras, italique, citation, séparateur),
    compteur de mots, sauvegarde automatique sur le serveur (si $draftUrl) et copie de
    secours dans le navigateur (si $localKey). Sans JavaScript, c'est une zone de texte.
--}}
@props([
    'name' => 'body',
    'id' => 'champ-reponse',
    'label' => 'Votre réponse',
    'value' => '',
    'draftUrl' => null,
    'localKey' => null,
    'clearLocal' => false,
    'savedAt' => null,
    'rows' => 14,
])
<div class="editor" data-editor
    @if ($draftUrl) data-draft-url="{{ $draftUrl }}" @endif
    @if ($localKey) data-local-key="{{ $localKey }}" @endif
    @if ($clearLocal) data-clear-local="1" @endif
    @if ($savedAt) data-server-saved="{{ $savedAt }}" @endif
    data-preview-url="{{ route('scenes.preview') }}">
    <div class="field">
        <label for="{{ $id }}">{{ $label }}</label>
        <div class="editor__toolbar js-only" role="toolbar" aria-label="Mise en forme" aria-controls="{{ $id }}">
            <button type="button" class="editor__tool" data-editor-action="bold" title="Gras"><strong>G</strong><span class="visually-hidden"> (gras)</span></button>
            <button type="button" class="editor__tool" data-editor-action="italic" title="Italique"><em>I</em><span class="visually-hidden"> (italique)</span></button>
            <button type="button" class="editor__tool" data-editor-action="dialogue" title="Dialogue">« »<span class="visually-hidden"> (dialogue)</span></button>
            <button type="button" class="editor__tool" data-editor-action="quote" title="Citation">❝<span class="visually-hidden"> (citation)</span></button>
            <button type="button" class="editor__tool" data-editor-action="separator" title="Séparateur">⁂<span class="visually-hidden"> (séparateur)</span></button>
        </div>
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" maxlength="{{ config('hollow.scenes.max_post_length') }}" required
            data-word-count="{{ $id }}-compteur" @error($name) aria-invalid="true" @enderror>{{ $value }}</textarea>
        @error($name)<p class="error">{{ $message }}</p>@enderror
        <p class="editor__meta hint">
            <span id="{{ $id }}-compteur" aria-live="polite"></span>
            <span class="editor__status" data-editor-status aria-live="polite"></span>
        </p>
        <p class="hint">**gras**, *italique*, « dialogue », &gt; citation, --- séparateur. Une ligne vide sépare les paragraphes.</p>
    </div>

    <div class="alert alert--warning" data-editor-restore hidden>
        <p>Une version plus récente de ce texte est gardée sur cet appareil.
            <button type="button" class="link-button" data-editor-restore-button>La reprendre</button></p>
    </div>

    <div class="editor__preview card" data-editor-preview-output hidden aria-live="polite">
        <p class="eyebrow">Aperçu</p>
        <div class="post__body prose" data-editor-preview-body></div>
    </div>
</div>
