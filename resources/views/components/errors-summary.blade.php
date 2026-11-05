@props(['bag' => 'default'])
@php($bagErrors = $errors->getBag($bag))
@if ($bagErrors->any())
    <div class="alert alert--error" role="alert">
        <div>
            <p>Le formulaire contient {{ $bagErrors->count() > 1 ? 'des erreurs' : 'une erreur' }} :</p>
            <ul>
                @foreach ($bagErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
