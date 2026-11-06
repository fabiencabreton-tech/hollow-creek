<?php

namespace App\Models;

use App\Support\Pseudo;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['pseudo', 'email', 'password', 'age_confirmed_at', 'terms_accepted_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Le rôle donné automatiquement à chaque nouveau compte.
     */
    public const DEFAULT_ROLE = 'joueur';

    protected static function booted(): void
    {
        // La forme normalisée du pseudo est recalculée à chaque modification :
        // l'index unique de la base interdit les doublons « Élise » / « elise ».
        static::saving(function (User $user): void {
            if ($user->isDirty('pseudo') || blank($user->pseudo_key)) {
                $user->pseudo_key = Pseudo::key((string) $user->pseudo);
            }

            if ($user->isDirty('email')) {
                $user->email = mb_strtolower(trim((string) $user->email));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'age_confirmed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'suspended_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'push_preferences' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Character, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Personnage actif : le choix du joueur s'il est toujours accepté, sinon son
     * premier personnage accepté.
     */
    public function activeCharacter(): ?Character
    {
        $accepted = $this->characters()->accepted()->orderBy('first_name')->get();

        return $accepted->firstWhere('id', $this->active_character_id) ?? $accepted->first();
    }

    /**
     * @return HasMany<SceneParticipant, $this>
     */
    public function sceneParticipations(): HasMany
    {
        return $this->hasMany(SceneParticipant::class);
    }

    /**
     * E-mail voulu pour un type de notification : immediat, quotidien ou aucun.
     */
    public function notificationMode(string $type): string
    {
        $mode = $this->notification_preferences[$type] ?? config("hollow.notifications.types.{$type}.default", 'immediat');

        return array_key_exists($mode, (array) config('hollow.notifications.modes')) ? $mode : 'immediat';
    }

    /**
     * Notification de ce type voulue sur les appareils (oui par défaut).
     */
    public function wantsPush(string $type): bool
    {
        return (bool) ($this->push_preferences[$type] ?? true);
    }

    /**
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function hasBlocked(User $other): bool
    {
        return UserBlock::query()->where('user_id', $this->getKey())->where('blocked_user_id', $other->getKey())->exists();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Membre de l'équipe : toute personne autorisée à ouvrir l'administration.
     */
    public function isStaff(): bool
    {
        return $this->can('administration.acceder');
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff()
            && ! $this->isSuspended()
            && $this->hasVerifiedEmail();
    }

    public function getFilamentName(): string
    {
        return (string) $this->pseudo;
    }
}
