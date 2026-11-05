@php
    $messages = [
        'verification-link-sent' => 'Un nouveau lien de vérification vient d’être envoyé à votre adresse e-mail.',
        'profile-information-updated' => 'Vos informations ont été enregistrées.',
        'password-updated' => 'Votre mot de passe a été modifié.',
        'two-factor-authentication-enabled' => 'Scannez maintenant le code avec votre application, puis saisissez le code à six chiffres pour terminer l’activation.',
        'two-factor-authentication-confirmed' => 'La double authentification est activée. Conservez vos codes de secours en lieu sûr.',
        'two-factor-authentication-disabled' => 'La double authentification est désactivée.',
        'recovery-codes-generated' => 'De nouveaux codes de secours ont été créés ; les anciens ne fonctionnent plus.',
        'other-sessions-logged-out' => 'Vos autres appareils ont été déconnectés.',
    ];
    $status = session('status');
    $statusText = is_string($status) ? ($messages[$status] ?? $status) : null;
@endphp
@if ($statusText)
    <div class="alert alert--success" role="status">
        <p>{{ $statusText }}</p>
    </div>
@endif
@if (session('warning'))
    <div class="alert alert--warning" role="alert">
        <p>{{ session('warning') }}</p>
    </div>
@endif
