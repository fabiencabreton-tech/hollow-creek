<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Pseudo;
use App\Support\RolesAndPermissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crée (ou promeut) un compte administrateur depuis la console SSH.
 * C'est la seule façon de créer le tout premier administrateur.
 */
#[Signature('hollow:admin {--email= : Adresse e-mail d’un compte existant à promouvoir administrateur}')]
#[Description('Créer le compte administrateur de Tsukimori, ou promouvoir un compte existant')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        RolesAndPermissions::sync();

        if ($email = $this->option('email')) {
            return $this->promote(mb_strtolower(trim((string) $email)));
        }

        $this->info('Création d’un compte administrateur pour Tsukimori.');

        $pseudo = Pseudo::clean(text(
            label: 'Pseudo',
            required: true,
            validate: fn (string $value): ?string => $this->validatePseudo($value),
        ));

        $email = mb_strtolower(trim(text(
            label: 'Adresse e-mail',
            required: true,
            validate: fn (string $value): ?string => Validator::make(
                ['email' => mb_strtolower(trim($value))],
                ['email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')]],
            )->errors()->first('email') ?: null,
        )));

        $plainPassword = password(
            label: 'Mot de passe (12 caractères minimum)',
            required: true,
            validate: fn (string $value): ?string => Validator::make(
                ['password' => $value],
                ['password' => ['required', Password::min(12)]],
            )->errors()->first('password') ?: null,
        );

        $confirmation = password(label: 'Confirmez le mot de passe', required: true);

        if ($confirmation !== $plainPassword) {
            $this->error('Les deux mots de passe ne correspondent pas. Rien n’a été créé.');

            return self::FAILURE;
        }

        $user = User::create([
            'pseudo' => $pseudo,
            'email' => $email,
            'password' => $plainPassword,
            'age_confirmed_at' => now(),
            'terms_accepted_at' => now(),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles(['administrateur']);

        ActivityLogger::record('compte.cree-en-console', $user, $user->pseudo, causer: $user);

        $this->newLine();
        $this->info("Compte administrateur « {$user->pseudo} » créé.");
        $this->line('Prochaine étape : connectez-vous sur le site, ouvrez « Mon compte » et activez la double authentification.');
        $this->line('Elle est obligatoire pour ouvrir l’administration (/admin).');

        return self::SUCCESS;
    }

    protected function promote(string $email): int
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte n’utilise l’adresse {$email}.");

            return self::FAILURE;
        }

        $user->assignRole('administrateur');

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        ActivityLogger::record('compte.roles-modifies', $user, $user->pseudo.' promu administrateur en console', causer: $user);

        $this->info("« {$user->pseudo} » est désormais administrateur.");

        return self::SUCCESS;
    }

    protected function validatePseudo(string $value): ?string
    {
        $value = Pseudo::clean($value);
        $key = Pseudo::key($value);

        if (mb_strlen($value) < config('hollow.accounts.pseudo_min') || mb_strlen($value) > config('hollow.accounts.pseudo_max')) {
            return 'Le pseudo doit compter entre '.config('hollow.accounts.pseudo_min').' et '.config('hollow.accounts.pseudo_max').' caractères.';
        }

        if (! preg_match(Pseudo::PATTERN, $value) || strlen($key) < 2) {
            return 'Lettres, chiffres, espaces, tirets et apostrophes uniquement.';
        }

        if (User::query()->where('pseudo_key', $key)->exists()) {
            return 'Ce pseudo, ou un pseudo trop proche, est déjà utilisé.';
        }

        return null;
    }
}
