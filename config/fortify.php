<?php

use App\Http\Middleware\RejectHoneypotSubmissions;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Laravel Fortify : comptes, connexion et double authentification
|--------------------------------------------------------------------------
|
| Fortify fournit la logique sécurisée (connexion, inscription, mot de passe
| oublié, vérification d'e-mail, double authentification). Les pages sont
| celles de Hollow Creek (resources/views/auth) et les adresses sont en français.
|
*/

return [

    'guard' => 'web',

    // Le limiteur « formulaires-publics » et le champ piège protègent l'inscription
    // et la demande de nouveau mot de passe (voir AppServiceProvider).
    'middleware' => ['web', 'throttle:formulaires-publics', RejectHoneypotSubmissions::class],

    'auth_middleware' => 'auth',

    'passwords' => 'users',

    // Le champ s'appelle « email » mais accepte aussi le pseudo (voir FortifyServiceProvider).
    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => false,

    'views' => true,

    'home' => '/tableau-de-bord',

    'prefix' => '',

    'domain' => null,

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => null,
    ],

    'paths' => [
        'login' => 'connexion',
        'logout' => 'deconnexion',
        'password' => [
            'request' => 'mot-de-passe-oublie',
            'reset' => 'nouveau-mot-de-passe/{token}',
            'email' => 'mot-de-passe-oublie',
            'update' => 'nouveau-mot-de-passe',
            'confirm' => 'compte/confirmer-mot-de-passe',
            'confirmation' => 'compte/statut-confirmation-mot-de-passe',
        ],
        'register' => 'inscription',
        'verification' => [
            'notice' => 'verifier-adresse-email',
            'verify' => 'verifier-adresse-email/{id}/{hash}',
            'send' => 'verifier-adresse-email/renvoyer',
        ],
        'user-profile-information' => [
            'update' => 'compte/profil',
        ],
        'user-password' => [
            'update' => 'compte/mot-de-passe',
        ],
        'two-factor' => [
            'login' => 'double-authentification',
            'enable' => 'compte/double-authentification',
            'confirm' => 'compte/double-authentification/confirmer',
            'disable' => 'compte/double-authentification',
            'qr-code' => 'compte/double-authentification/qr-code',
            'secret-key' => 'compte/double-authentification/cle-secrete',
            'recovery-codes' => 'compte/double-authentification/codes-de-secours',
        ],
        'passkey' => [
            'login-options' => null,
            'login' => null,
            'confirm-options' => null,
            'confirm' => null,
            'registration-options' => null,
            'store' => null,
            'destroy' => null,
        ],
    ],

    'redirects' => [
        'login' => null,
        'logout' => '/',
        'password-confirmation' => null,
        'register' => null,
        'email-verification' => null,
        'password-reset' => null,
    ],

    'passkeys' => [
        'relying_party_id' => parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST),
        'allowed_origins' => [env('APP_URL', 'http://localhost')],
        'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', env('APP_KEY')),
        'timeout' => 60000,
    ],

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],

];
