<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Journal des actions de l'équipe : qui a fait quoi, et quand.
 */
#[Table('activity_log')]
#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'description', 'properties', 'created_at'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Libellés lisibles des actions enregistrées.
     *
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            'connexion.equipe' => 'Connexion d’un membre de l’équipe',
            'compte.cree-en-console' => 'Compte administrateur créé en console',
            'compte.suspendu' => 'Compte suspendu',
            'compte.reactive' => 'Compte réactivé',
            'compte.roles-modifies' => 'Rôles modifiés',
            'compte.modifie' => 'Compte modifié',
            'compte.email-valide' => 'Adresse e-mail validée par l’équipe',
            'compte.2fa-reinitialisee' => 'Double authentification réinitialisée',
            'role.cree' => 'Rôle créé',
            'role.modifie' => 'Rôle modifié',
            'role.supprime' => 'Rôle supprimé',
            'page.modifiee' => 'Page officielle modifiée',
            'contenu.cree' => 'Contenu créé',
            'contenu.modifie' => 'Contenu modifié',
            'contenu.supprime' => 'Contenu supprimé',
            'fiche.acceptee' => 'Fiche acceptée',
            'fiche.modifications' => 'Modifications demandées sur une fiche',
            'fiche.refusee' => 'Fiche refusée',
            'fiche.archivee' => 'Fiche archivée',
            'bottin.reservation-acceptee' => 'Réservation d’avatar acceptée',
            'bottin.reservation-refusee' => 'Réservation d’avatar refusée',
            'bottin.liberation' => 'Avatar libéré',
            'reglages.modifies' => 'Réglages modifiés',
            'sauvegarde.demandee' => 'Sauvegarde demandée',
            'sauvegarde.telechargee' => 'Sauvegarde téléchargée',
            'sauvegarde.supprimee' => 'Sauvegarde supprimée',
        ];
    }

    public function actionLabel(): string
    {
        return static::actionLabels()[$this->action] ?? $this->action;
    }
}
