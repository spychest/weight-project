#!/usr/bin/env sh

set -e

cd "$(dirname "$0")"

echo "Démarrage des conteneurs…"
docker compose up -d

echo "Installation des dépendances…"
docker compose exec -T php-fpm composer install

echo "Exécution des migrations…"
docker compose exec -T php-fpm php bin/console doctrine:migrations:migrate --no-interaction

echo "Vidage du cache…"
docker compose exec -T php-fpm php bin/console ca:cl

echo "Déploiement terminé."
