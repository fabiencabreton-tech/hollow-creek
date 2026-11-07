<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        // Mots de passe : 12 caractères minimum ; en production, ceux qui figurent dans
        // une fuite connue sont refusés (vérification anonyme « Have I Been Pwned »,
        // seuls les 5 premiers caractères d'une empreinte quittent le serveur).
        Password::defaults(function () {
            $rule = Password::min(12)->max(255);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Derrière le proxy HTTPS d'Alwaysdata, toutes les adresses générées sont en https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

        Gate::policy(Role::class, RolePolicy::class);

        // Les écouteurs d'app/Listeners (ex. RecordStaffLogin) sont découverts automatiquement.

        View::composer(['components.layout', 'home', 'dashboard', 'dashboard-pending', 'auth.*', 'gazette.*'], function ($view): void {
            $settings = $this->app->make(Settings::class);
            $view->with('rpDate', $settings->rpDate());
            $view->with('weather', $settings->weather());
            $view->with('registrationsOpen', $settings->registrationsOpen());
            $view->with('illustrationsCredit', $settings->illustrationsCredit());
        });
    }
}
