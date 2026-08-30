# Weight Project

Weight Project est une application web de suivi personnel conçue pour centraliser les données qui accompagnent une évolution de poids : pesées, habitudes quotidiennes, hydratation, sommeil, alimentation et activité physique.

L’application aide à observer ses tendances et à mesurer sa progression dans le temps. Elle ne remplace pas l’avis, le diagnostic ou l’accompagnement d’un professionnel de santé.

## Fonctionnalités

- Création de compte avec une adresse e-mail et un mot de passe.
- Inscription et connexion avec Google OAuth.
- Association d’un compte Google à un compte existant.
- Profil de suivi personnel rattaché à chaque utilisateur.
- Enregistrement et consultation des pesées.
- Suivi de l’hydratation, du sommeil, des repas et de l’activité physique.
- Bilans quotidiens pour regrouper les informations d’une journée.
- Création de jalons et validation automatique lors de l’ajout d’une pesée.
- Tableau de bord synthétique.
- Graphiques dédiés au poids, à l’hydratation, au sommeil, aux repas et aux bilans quotidiens.
- Génération de rapports sur une période donnée.
- Export des rapports aux formats JSON et PDF.
- Sauvegarde complète des données du profil au format JSON.
- Import d’une sauvegarde JSON avec validation du fichier avant remplacement.
- Gestion du compte : modification de l’adresse e-mail et du mot de passe, puis suppression du compte.
- Mode sombre enregistré dans les préférences de l’utilisateur.
- Interface responsive avec navigation adaptée aux appareils mobiles.
- Suite de tests automatisés avec génération d’un rapport HTML.

## Déploiement

Le déploiement repose sur Docker et Docker Compose. Le fichier `.env` du serveur doit contenir les valeurs propres à l’environnement de production, notamment les accès à la base de données, les secrets Symfony et les identifiants Google OAuth.

Les variables spécifiques à l’authentification Google et au reverse proxy doivent notamment être renseignées ainsi :

```dotenv
GOOGLE_CLIENT_ID=votre-identifiant-google
GOOGLE_CLIENT_SECRET=votre-secret-google
GOOGLE_REDIRECT_URI=https://votre-domaine/connect/google/check
TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
```

L’URI indiquée dans `GOOGLE_REDIRECT_URI` doit être ajoutée à la liste des URI de redirection autorisés du client OAuth dans Google Cloud.

Une fois le fichier `.env` de production configuré, lancer le script de déploiement depuis la racine du projet :

```bash
chmod +x deploy.sh
./deploy.sh
```

Le script :

1. démarre les conteneurs Docker ;
2. attend que MariaDB soit disponible ;
3. installe les dépendances PHP dans le conteneur `php-fpm` ;
4. exécute les migrations Doctrine ;
5. vide le cache Symfony.

Lorsque l’application est placée derrière un reverse proxy HTTPS, celui-ci doit transmettre les en-têtes `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Proto` et `X-Forwarded-Port`.

## Fonctionnalités envisagées

- Notifications et rappels personnalisables pour les saisies quotidiennes.
- Objectifs plus détaillés avec indicateurs de progression.
- Comparaison de plusieurs périodes dans les graphiques et les rapports.
- Statistiques complémentaires pour mieux visualiser les tendances à long terme.
- Expérience mobile enrichie, avec la possibilité d’installer l’application sur son appareil.

Cette feuille de route est indicative et pourra évoluer avec les besoins du projet.

## Technologies utilisées

- PHP
- Symfony
- Doctrine ORM
- PHPUnit
- MariaDB
- Twig
- JavaScript
- CSS
- Nginx
- Docker
- Docker Compose
- Google OAuth 2.0

## Licence

Ce projet est un outil personnel de suivi. Il n’a pas vocation à remplacer un accompagnement médical ou professionnel.
