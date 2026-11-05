<x-layout title="Modifier une réponse" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Les scènes' => route('scenes.index'), $scene->title => route('scenes.show', $scene), 'Modifier une réponse' => null]" />
            <h1>Modifier une réponse</h1>
            <p class="lede">La réponse portera la mention « modifiée le… ». La version précédente reste consultable par l’équipe.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            <form class="form card" method="POST" action="{{ route('scenes.posts.update', [$scene, $post]) }}" novalidate>
                @csrf
                @method('PUT')
                <x-scene-editor :value="old('body', $post->body)" :label="'Réponse de '.$post->character_name" />
                <div class="form-actions">
                    <button type="submit" class="btn">Enregistrer la modification</button>
                    <button type="button" class="btn btn--ghost js-only" data-editor-preview>Aperçu</button>
                    <a href="{{ route('scenes.show', $scene) }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
