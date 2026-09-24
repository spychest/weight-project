#!/usr/bin/env sh

set -e

cd "$(dirname "$0")"

echo "Démarrage des conteneurs…"
docker compose up -d

echo "Installation des dépendances…"
docker compose exec -T php-fpm composer install

echo "Préparation des permissions Symfony…"
docker compose exec -T php-fpm chown -R www-data:www-data var

echo "Exécution des migrations…"
docker compose exec -T --user www-data php-fpm php bin/console doctrine:migrations:migrate --no-interaction

echo "Import du catalogue d'ingrédients…"
docker compose exec -T --user www-data php-fpm php bin/console project:ingredient-catalog:import

echo "Reclassement des listes de courses actives…"
docker compose exec -T --user www-data php-fpm php bin/console project:shopping-list:reclassify

echo "Vidage du cache…"
docker compose exec -T --user www-data php-fpm php bin/console ca:cl

echo "Déploiement terminé."
