@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'bag' => 'default',
    'id' => null,
])
@php
    $id ??= 'champ-'.$name;
    $error = $errors->getBag($bag)->first($name);
    $describedBy = trim(($hint ? $id.'-aide ' : '').($error ? $id.'-erreur' : ''));
    $isPassword = $type === 'password';
@endphp
<div class="field">
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($hint)
        <p class="hint" id="{{ $id }}-aide">{{ $hint }}</p>
    @endif
    @if ($isPassword)
        <div class="password-field">
            <input id="{{ $id }}" name="{{ $name }}" type="password"
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($error) aria-invalid="true" @endif
                {{ $attributes }}>
            <button type="button" class="password-toggle" data-password-toggle aria-controls="{{ $id }}" aria-pressed="false" hidden>Afficher</button>
        </div>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value ?? old($name) }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes }}>
    @endif
    @if ($error)
        <p class="error" id="{{ $id }}-erreur">{{ $error }}</p>
    @endif
</div>
