#!/usr/bin/env sh

set -eu

PROJECT_DIRECTORY=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd "$PROJECT_DIRECTORY"

PRODUCTION_ENV_FILE=${1:-${DEPLOY_ENV_FILE:-.env.prod.local}}
COMPOSE_PROJECT_FILE=${COMPOSE_FILE:-compose.yaml}
PHP_SERVICE_NAME=php-fpm
WEB_SERVICE_NAME=nginx
DATABASE_SERVICE_NAME=database

print_step()
{
    printf '\n\033[1;36m==> %s\033[0m\n' "$1"
}

print_error()
{
    printf '\n\033[1;31mErreur : %s\033[0m\n' "$1" >&2
}

run_compose()
{
    docker compose --env-file "$PRODUCTION_ENV_FILE" -f "$COMPOSE_PROJECT_FILE" "$@"
}

show_failure_context()
{
    exit_code=$?

    if [ "$exit_code" -ne 0 ]; then
        print_error "Le déploiement a échoué. Les dernières lignes des journaux sont affichées ci-dessous."
        run_compose ps || true
        run_compose logs --tail=80 "$PHP_SERVICE_NAME" "$WEB_SERVICE_NAME" "$DATABASE_SERVICE_NAME" || true
    fi

    exit "$exit_code"
}

trap show_failure_context EXIT INT TERM

if ! command -v docker >/dev/null 2>&1; then
    print_error "Docker n'est pas installé ou n'est pas disponible dans le PATH."
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    print_error "Le moteur Docker n'est pas démarré ou l'utilisateur courant ne peut pas y accéder."
    exit 1
fi

if [ ! -f "$PRODUCTION_ENV_FILE" ]; then
    print_error "Le fichier $PRODUCTION_ENV_FILE est introuvable. Crée-le avec les variables de production, ou passe son chemin en premier argument."
    printf 'Exemple : ./deploy.sh /chemin/vers/.env.production\n' >&2
    exit 1
fi

if grep -Eq '^APP_RUNTIME_ENV=(dev|test)([[:space:]]*)$' "$PRODUCTION_ENV_FILE"; then
    print_error "APP_RUNTIME_ENV doit être défini sur prod pour un déploiement."
    exit 1
fi

if grep -q '\[CHANGE_ME\]' "$PRODUCTION_ENV_FILE"; then
    print_error "Le fichier d'environnement contient encore au moins une valeur [CHANGE_ME]."
    exit 1
fi

print_step "Validation de la configuration Docker"
run_compose config --quiet

print_step "Construction et démarrage des services"
run_compose up -d --build "$DATABASE_SERVICE_NAME" "$PHP_SERVICE_NAME" "$WEB_SERVICE_NAME"

print_step "Attente de la base de données"
database_is_ready=false
attempt=1
while [ "$attempt" -le 30 ]; do
    if run_compose exec -T "$DATABASE_SERVICE_NAME" sh -c \
        'mariadb-admin ping -h 127.0.0.1 -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" --silent' >/dev/null 2>&1; then
        database_is_ready=true
        break
    fi

    printf 'Base indisponible, nouvelle tentative (%s/30)…\n' "$attempt"
    attempt=$((attempt + 1))
    sleep 2
done

if [ "$database_is_ready" != true ]; then
    print_error "La base de données n'est pas devenue disponible dans le délai prévu."
    exit 1
fi

print_step "Installation des dépendances PHP de production"
run_compose exec -T \
    -e APP_ENV=prod \
    -e APP_DEBUG=0 \
    -e COMPOSER_ALLOW_SUPERUSER=1 \
    "$PHP_SERVICE_NAME" \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --classmap-authoritative \
        --no-scripts

print_step "Application des migrations de base de données"
run_compose exec -T "$PHP_SERVICE_NAME" \
    php bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction --allow-no-migration

print_step "Compilation des ressources publiques"
run_compose exec -T "$PHP_SERVICE_NAME" \
    php bin/console importmap:install --env=prod --no-debug
run_compose exec -T "$PHP_SERVICE_NAME" \
    php bin/console asset-map:compile --env=prod --no-debug

print_step "Préparation du cache et des permissions"
run_compose exec -T "$PHP_SERVICE_NAME" sh -c \
    'mkdir -p var/cache var/log public/assets && chown -R www-data:www-data var public/assets'
run_compose exec -T --user www-data "$PHP_SERVICE_NAME" \
    php bin/console cache:clear --env=prod --no-debug
run_compose exec -T --user www-data "$PHP_SERVICE_NAME" \
    php bin/console cache:warmup --env=prod --no-debug

print_step "Redémarrage du service PHP"
run_compose restart "$PHP_SERVICE_NAME"

print_step "Contrôle HTTP final"
application_is_ready=false
attempt=1
while [ "$attempt" -le 20 ]; do
    if run_compose exec -T "$WEB_SERVICE_NAME" wget -q --spider http://localhost/ >/dev/null 2>&1; then
        application_is_ready=true
        break
    fi

    printf 'Application indisponible, nouvelle tentative (%s/20)…\n' "$attempt"
    attempt=$((attempt + 1))
    sleep 2
done

if [ "$application_is_ready" != true ]; then
    print_error "Les services sont démarrés, mais le contrôle HTTP final a échoué."
    exit 1
fi

trap - EXIT INT TERM

printf '\n\033[1;32mDéploiement terminé avec succès.\033[0m\n'
run_compose ps
