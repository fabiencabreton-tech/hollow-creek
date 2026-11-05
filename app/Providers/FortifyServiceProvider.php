<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Support\Pseudo;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Connexion avec l'adresse e-mail ou le pseudo. Un compte suspendu ne peut pas se connecter.
     * La durée de vérification est constante pour ne pas révéler si un compte existe.
     */
    protected function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            return (new Timebox)->call(function () use ($request): ?User {
                $identifier = trim((string) $request->input(Fortify::username()));
                $password = (string) $request->input('password');

                if ($identifier === '' || $password === '') {
                    return null;
                }

                $user = str_contains($identifier, '@')
                    ? User::query()->where('email', mb_strtolower($identifier))->first()
                    : User::query()->where('pseudo_key', Pseudo::key($identifier))->first();

                if (! $user || ! Hash::check($password, $user->password)) {
                    return null;
                }

                if ($user->isSuspended()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => 'Ce compte est suspendu. Pour toute question, contactez l’équipe de Hollow Creek.',
                    ]);
                }

                if (Hash::needsRehash($user->password)) {
                    $user->forceFill(['password' => Hash::make($password)])->save();
                }

                return $user;
            }, 250_000);
        });
    }

    protected function configureViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register', [
            'registrationsOpen' => app(Settings::class)->registrationsOpen(),
        ]));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->session()->get('login.id'));
        });

        // Inscription et demande de nouveau mot de passe : limites par adresse IP.
        RateLimiter::for('formulaires-publics', function (Request $request) {
            if (! $request->isMethod('POST')) {
                return Limit::none();
            }

            return match ($request->route()?->getName()) {
                'register.store' => Limit::perHour(5)->by('inscription|'.$request->ip()),
                'password.email' => Limit::perMinutes(15, 5)->by('mot-de-passe|'.$request->ip()),
                default => Limit::none(),
            };
        });
    }
}
