@echo off
title Weight Project

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

docker compose up -d

if errorlevel 1 (
    echo.
    echo Une erreur est survenue lors du demarrage des containers.
    pause
    exit /b 1
)

echo.
echo Projet demarre !
echo.

start "" "http://localhost:8080/dashboard"

exit