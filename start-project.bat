@echo off
title Weight Project

set NGINX_PORT=8080

echo Demarrage de Docker Desktop...

start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"

echo Attente du demarrage de Docker...

:wait_docker
docker info >nul 2>&1
if errorlevel 1 (
    timeout /t 2 /nobreak >nul
    goto wait_docker
)

echo Docker est pret.
echo Demarrage des containers...

if exist ".env.local" (
    echo Utilisation de .env.local
    set COMPOSE_ENVIRONMENT=--env-file .env.local
) else (
    echo Utilisation de .env
    set COMPOSE_ENVIRONMENT=
)

docker compose %COMPOSE_ENVIRONMENT% up -d --build

if errorlevel 1 (
    echo.
    echo Une erreur est survenue lors du demarrage des containers.
    pause
    exit /b 1
)

echo Preparation de l'application optimisee...
docker compose %COMPOSE_ENVIRONMENT% exec -T php-fpm php bin/console doctrine:migrations:migrate --no-interaction
docker compose %COMPOSE_ENVIRONMENT% exec -T php-fpm chown -R www-data:www-data var
docker compose %COMPOSE_ENVIRONMENT% exec -T --user www-data php-fpm php bin/console cache:clear --env=prod --no-debug
docker compose %COMPOSE_ENVIRONMENT% exec -T php-fpm php bin/console asset-map:compile --env=prod --no-debug

if errorlevel 1 (
    echo.
    echo Une erreur est survenue pendant la preparation de l'application.
    pause
    exit /b 1
)

echo.
echo Projet demarre !
echo.

start "" "http://localhost:%NGINX_PORT%/dashboard"

exit
