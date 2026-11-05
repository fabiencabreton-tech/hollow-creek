#!/usr/bin/env bash
# =====================================================================
# Hollow Creek — retour à la version précédente du code
#
#   cd ~/hollowcreek && ./rollback.sh
#
# Revient au code d'avant le dernier ./deploy.sh. La base de données n'est pas
# modifiée : les mises à jour de Hollow Creek ajoutent des tables ou colonnes
# sans rien supprimer, l'ancienne version fonctionne donc avec la base actuelle.
# Si la base elle-même doit revenir en arrière, utilisez une sauvegarde
# (docs/sauvegardes-et-restauration.md).
# =====================================================================
set -Eeuo pipefail

cd "$(dirname "$0")"

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

if [[ ! -f .deploy-previous ]]; then
    echo "Aucune version précédente enregistrée : ./deploy.sh n'a pas encore été lancé." >&2
    exit 1
fi

PREVIOUS="$(cat .deploy-previous)"
CURRENT="$(git rev-parse HEAD)"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"

echo "Retour de $(git log -1 --format='%h' "$CURRENT") vers $(git log -1 --format='%h — %s' "$PREVIOUS")."

"$PHP" artisan down --render="errors::503" --retry=60 || true

git checkout -B "$BRANCH" "$PREVIOUS"
"$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
"$PHP" artisan filament:assets
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize || true
"$PHP" artisan queue:restart || true
"$PHP" artisan up

echo "$CURRENT" > .deploy-previous
echo "Version précédente rétablie. Pour revenir à la plus récente : ./deploy.sh"
