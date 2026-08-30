# Weight tool
## Objectif
J'ai développé cet outil uniquement dans le but de m'aider à suivre 
l'évolution de mon poids. Je le fais dans l'objectif de réapprendre à 
manger correctement tout en conservant le plaisir de manger. 

Il n'est en aucun cas possible de se servir de cet outil pour remplacer 
un médecin. Il est uniquement là pour faciliter un possible suivi.
## Installation
Pour utiliser cet outil, il vous faut avant tout installer [docker](https://www.docker.com/products/docker-desktop/). 

Rendez-vous ensuite dans le dossier du projet.

### Configuration

Le fichier `.env` contient la configuration par défaut du projet.

Rendez-vous ensuite dans le fichier `.env` et modifiez les variables d'environnement suivante:
- `APP_ENV` ("dev" ou "prod")
- `APP_SECRET` (Vous pouvez mettre ici une chaine de caractères)
- `DATABASE_USER` (Nom d'utilisateur de la base de données)
- `DATABASE_PASSWORD` (Le mot de passe pour l'utilisateur de la base de donées)
- `DATABASE_HOST` (Le nom du service Docker pour la base de données)
- `DATABASE_PORT` (Le port interne du service Docker)
- `DATABASE_EXTERNAL_PORT` (Le port exposé du service Docker)
- `DATABASE_NAME` (Le nom de la base de données)
- `DATABASE_SERVER_VERSION` (La version de mysql ou mariaDB que vous utilisez)
- `DATABASE_CHARSET` (La table de caractère que vous utilisez dans la base de données)
- `TZ` (La timezone que vous souhaitez utiliser)
- `NGINX_EXTERNAL_PORT` (Le port sur lequel l'outil sera disponible)
- `NGINX_INTERNAL_PORT` (Le port interne du service Docker)

Exemple de fichier .env adapté au fichier `compose.yaml` actuel
```dotenv
###> timezone ###
TZ="Europe/Paris"
###< timezone ###

###> NGINX ###
NGINX_EXTERNAL_PORT=8080
NGINX_INTERNAL_PORT=80
###< NGINX ###

###> symfony/framework-bundle ###
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=8b19f42926f377eae70da7dcff5fbcb4
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_USER=weight
DATABASE_PASSWORD=weight
DATABASE_HOST=database
DATABASE_PORT=3306
DATABASE_EXTERNAL_PORT=3306
DATABASE_NAME=weight_project
DATABASE_SERVER_VERSION=10.11.2-MariaDB
DATABASE_CHARSET=utf8mb4

DATABASE_URL="mysql://${DATABASE_USER}:${DATABASE_PASSWORD}@${DATABASE_HOST}:${DATABASE_PORT}/${DATABASE_NAME}?serverVersion=${DATABASE_SERVER_VERSION}&charset=${DATABASE_CHARSET}"
###< doctrine/doctrine-bundle ###

###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
###< symfony/messenger ###
```

>Attention : l'exemple ci-dessus utilise des valeurs simples pour les mots de passe et le APP_SECRET. Ces valeurs doivent être remplacées dans un environnement réel.

### Démarrage

Dans le dossier du projet, ouvrez un terminal.

>Astuce : Sous windows, vous pouvez ouvrir un terminal en faisant un 
> clic droit sur le dossier du projet et en choisissant "Ouvrir un terminal".

Entrez la commande suivante :
```bash
docker compose up -d \
&& docker compose exec php-fpm composer install \
&& docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction
```

Une fois ceci effectué, vous pouvez vous rendre sur l'adresse localhost au port que vous avez défini dans le fichier `.env`.
>Note : Si vous avez conservé le fichier `.env` en exemple, vous pouvez vous rendre sur [localhost:8080](http://localhost:8080)

Par défaut, le conteneur web utilise l'environnement Symfony `prod` afin d'obtenir de meilleurs temps d'affichage. Pour lancer temporairement l'application en mode développement, ajoutez ces variables dans `.env.local`, puis recréez le conteneur PHP :

```dotenv
APP_RUNTIME_ENV=dev
APP_RUNTIME_DEBUG=1
```

### Reprendre le profil historique

Après la migration vers le système de comptes, l'ancien profil reste intact mais sans propriétaire. Pour créer votre compte local et lui rattacher automatiquement l'unique profil existant, lancez :

```bash
docker compose --env-file .env.local exec php-fpm php bin/console project:user:adopt-existing-profile
```

La commande demande l'adresse e-mail et le mot de passe de façon interactive. Elle refuse de continuer s'il n'existe pas exactement un profil sans propriétaire, afin d'éviter tout rattachement accidentel.

### Connexion Google

Créez un client OAuth 2.0 de type « application Web » dans Google Cloud, avec cette URL de redirection en local :

```text
http://localhost:8080/connect/google/check
```

Ajoutez ensuite les identifiants dans `.env.local` :

```dotenv
GOOGLE_CLIENT_ID=votre-identifiant
GOOGLE_CLIENT_SECRET=votre-secret
```

Puis recréez le service PHP avec `docker compose --env-file .env.local up -d`.

### Astuce
Maintenant que le projet a été lancé une première fois et si vous avez 
conservé le fichier `.env` en exemple, vous pouvez maintenant lancer le 
projet à l'aide du fichier `start-project.bat`. Ce fichier lancera 
automatiquement Docker, lancera également les containers, puis ouvrira 
l'outil dans votre navigateur.

### Arrêter l'outil
Lorsque vous n'avez plus besoin de l'outil, fermez simplement 
Docker ou entrez la commande 
```bash 
docker compose down
```

### Déploiement en production

Renseignez les variables de production dans le fichier `.env`, puis lancez :

```bash
chmod +x deploy.sh
./deploy.sh
```

Le script démarre les conteneurs, installe les dépendances dans `php-fpm`, applique les migrations et vide le cache Symfony.

## Technologies utilisées
- PHP
- Symfony
- Doctrine ORM
- MariaDB
- Nginx
- Docker
- Docker Compose

## Licence
Ce projet est un projet personnel développé pour mon propre suivi.

Il n'a pas vocation à remplacer un accompagnement médical ou professionnel.
