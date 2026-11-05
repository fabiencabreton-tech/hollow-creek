#!/usr/bin/env bash
# =====================================================================
# Tsukimori — mise à jour du site
#
#   cd ~/hollowcreek && ./deploy.sh            # dernière version de la branche main
#   cd ~/hollowcreek && ./deploy.sh --premiere # première installation (voir le guide)
#
# Le site passe en maintenance, récupère la nouvelle version, installe les
# dépendances, met la base à jour, reconstruit les caches puis revient en ligne.
# En cas d'échec, le site reste en maintenance et ./rollback.sh ramène
# la version précédente.
# =====================================================================
set -Eeuo pipefail

cd "$(dirname "$0")"

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"
BRANCH="${DEPLOY_BRANCH:-main}"
FIRST_INSTALL=false

if [[ "${1:-}" == "--premiere" ]]; then
    FIRST_INSTALL=true
fi

etape() { printf '\n\033[1;33m▸ %s\033[0m\n' "$1"; }
erreur() { printf '\n\033[1;31m✖ %s\033[0m\n' "$1" >&2; }

en_cas_d_echec() {
    erreur "La mise à jour a échoué (voir le message ci-dessus)."
    if [[ "$FIRST_INSTALL" == false ]]; then
        echo "Le site reste en maintenance pour protéger les données."
        echo "  • pour revenir à la version précédente : ./rollback.sh"
        echo "  • pour réessayer après correction      : ./deploy.sh"
    fi
}
trap en_cas_d_echec ERR

if [[ ! -f .env ]]; then
    erreur "Le fichier .env est introuvable. Copiez .env.example en .env et remplissez-le (voir docs/installation-alwaysdata.md)."
    exit 1
fi

if ! "$PHP" -m | grep -qi '^intl$'; then
    erreur "L'extension PHP « intl » n'est pas activée. Ajoutez « extension = intl.so » dans Environnement › PHP (php.ini) chez Alwaysdata."
    exit 1
fi

if [[ "$FIRST_INSTALL" == false ]]; then
    etape "Mise en maintenance"
    "$PHP" artisan down --render="errors::503" --retry=60 || true
fi

if [[ -d .git ]]; then
    etape "Récupération de la nouvelle version ($BRANCH)"
    git rev-parse HEAD > .deploy-previous
    git fetch --prune origin "$BRANCH"
    git checkout -B "$BRANCH" "origin/$BRANCH"
    echo "Version : $(git log -1 --format='%h — %s (%cd)' --date=format:'%d/%m/%Y %H:%M')"
else
    echo "(Pas de dépôt Git : les fichiers déjà présents sont utilisés tels quels.)"
fi

etape "Installation des dépendances PHP"
if [[ -f composer.lock ]]; then
    "$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
else
    echo "composer.lock absent : résolution complète des versions (plus long, la première fois seulement)."
    COMPOSER_MEMORY_LIMIT=-1 "$COMPOSER" update --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    etape "Création de la clé de chiffrement"
    "$PHP" artisan key:generate --force
fi

etape "Mise à jour de la base de données"
"$PHP" artisan migrate --force

etape "Rôles, permissions et pages officielles"
"$PHP" artisan db:seed --force

etape "Fichiers publics"
if [[ ! -e public/storage ]]; then
    "$PHP" artisan storage:link
fi
"$PHP" artisan filament:assets

etape "Reconstruction des caches"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize || true
"$PHP" artisan queue:restart || true

etape "Remise en ligne"
"$PHP" artisan up

trap - ERR
printf '\n\033[1;32m✔ Tsukimori est à jour.\033[0m\n'
if [[ "$FIRST_INSTALL" == true ]]; then
    echo "Étape suivante : créez le compte administrateur avec « php artisan hollow:admin »."
fi
