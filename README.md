# Gnut06

## Introduction

Bienvenue sur le dépôt GitHub du site de **Gnut06** !

Gnut06 est une association dédiée à l'inclusion des personnes en situation de handicap grâce aux technologies innovantes. Notre mission est d'utiliser des outils tels que la réalité virtuelle et la réalité augmentée pour offrir des expériences concrètes et enrichissantes aux personnes en situation de handicap, favorisant ainsi leur intégration sociale et leur épanouissement.

Nous organisons des visites à l'hôpital et dans les maisons de retraite, où nous proposons aux patients des moments d'évasion et de divertissement à travers l'utilisation de casques de réalité virtuelle. Ces expériences immersives permettent aux personnes en situation de handicap de s'évader de leur environnement médicalisé et de vivre des aventures virtuelles uniques, tout en stimulant leur imagination et leur bien-être.

Parallèlement, nous proposons des stages d'initiation aux nouvelles technologies, ouverts à tous, afin d'aider les personnes en situation de handicap à développer de nouvelles compétences et à découvrir de nouvelles possibilités dans le domaine des technologies. Ces stages leur offrent la possibilité d'acquérir des connaissances pratiques, de renforcer leur estime de soi et de favoriser leur autonomie.

## Technologies Utilisées

Ce projet utilise les technologies suivantes :

- **Symfony 7.1** : Un framework PHP pour construire des applications web robustes.
- **Webpack Encore** : Une abstraction sur Webpack pour une gestion simplifiée des assets (CSS, JavaScript).
- **Docker** : Pour la containerisation de l'application, assurant un environnement de développement et de production cohérent.

## Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre machine :

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Node.js](https://nodejs.org/) et [npm](https://www.npmjs.com/)

## Installation

1. **Ajouter l'authentification ssh au dépot**

   ```bash
   ssh-keygen
   cat <chemin>/<nom_du_fichier_créé_par_keygen(voir_sortie_console)>.pub
   ```
   Copier la sortie console de cat  
   Ouvrir githb dans le navigateur  
   Cliquer Profil (Icône en haut à droite) -> Settings (Paramètres)  
   Cliquer "SSH and GPG keys"  
   Cliquer "New SSH key"  
   Saisir un nom de clé  
   Coller la sortie console de cat  

2. **Clonez le dépôt**

   ```bash
   git clone git@github.com:assognut06/symfonygnut06.git
   cd symfonygnut06
   ```
3. **Résolution de conflit**  
   La solution inclus un container mysql utilisant le port 3306  
   Vérifiez que le poste de dev n'aie pas déjà un serveur monopolisant ce port
   ```bash
   netstat -pnltu | grep -w 3306
   ```
   La sortie console doit être vide, sinon:
   - désinstallez le serveur concurrent
4. **Intégration du projet**

   ```bash
   sudo groupmod -U <username> docker
   docker compose up --build -d
   ```

   Certains éléments de configuration ne peuvent être faits qu'une fois que les images sont démarrées.

   ```bash
   docker exec -it symfony_asso composer install --no-interaction
   docker exec -it symfony_asso npm install
   docker exec -it symfony_asso npm run build
   ```

5. **Initialisation de la base de données**
   ```bash
   docker exec -it symfony_asso php bin/console doctrine:migration:migrate
   ```
A ce niveau, le site devrait être accessible via le navigateur sur https://127.0.0.1  
Il est important de passer par l'IP car il y a un filtre dans les APIs Google avec cette valeur  
 
Par contre, la base de donnée est vide

6. **Initialisation des valeurs de test**
   ```bash
   docker exec -it symfony_asso php bin/console doctrine:fixtures:load --append
   ```

## Importation des données (si nécessaire)

1. **Demander les données nécessaires**
   le répertoire uploads
   le script sql de la base de prod

2. **Importation des uploads**
   Copier le répertoire uploads fourni dans app/public/uploads

3. **Charger le sql**  
   Ouvrir PHPmyAdmin: http://localhost:8080  
   S'authentifier  
   Selectionner la base gnut06  
   Ouvrir l'onglet "Importer"  
   Parcourir pour choisir le fichier .sql ou .sql.gz  
   Décocher "Activer la vérification des clés étrangères"  
   Cliquer sur importer  

4. **Vérifier**
   Ouvrir le site applicatif
   La liste "Nos alliés dans nos actions" doit contenir des logos défilant

## Configuration de XDebug dans VSCode

Ouvrir Run->Add Configuration
Dans le launch.json: coller
   ```bash
   {
    // Use IntelliSense to learn about possible attributes.
    // Hover to view descriptions of existing attributes.
    // For more information, visit: https://go.microsoft.com/fwlink/?linkid=830387
    "version": "0.2.0",
    "configurations": [
    

        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/app": "${workspaceFolder}/app"
            }
        }
    ]
}
   ```
Lors du lancement du debugger, VSCode devrait pouvoir se connecter au XDebug qui tourne dans l'image docker

## Arrêt

```bash
docker compose stop
```

## Démarrage normal

```bash
docker compose up -d
```

## Tests

# All tests

docker exec symfony_asso php vendor/bin/phpunit

# Unit tests only (fast, no DB)

docker exec symfony_asso php vendor/bin/phpunit --testsuite=Unit

# Functional tests only

docker exec symfony_asso php vendor/bin/phpunit --testsuite=Functional

# Specific test file

docker exec symfony_asso php vendor/bin/phpunit tests/Functional/SecurityHeadersTest.php


# Custom PHPStan Rules
   ```bash
   docker exec symfony_asso php vendor/bin/phpunit --testsuite=Rules
   ```

# Run PHPStan
   ```bash 
   docker exec symfony_asso vendor/bin/phpstan analyse --memory-limit 256M
   ```

# How to run coverage

<!--
From the app/ directory (or inside the symfony_asso container, where the app root is usually /var/www/html or similar):

Text summary in the terminal:

docker exec symfony_asso php vendor/bin/phpunit --coverage-text
HTML report (easiest to browse):

docker exec symfony_asso php vendor/bin/phpunit --coverage-html var/coverage
Then open app/var/coverage/index.html in a browser (or the equivalent path inside the container)
 -->

Run coverage now (no rebuild)
Enable coverage for that command only:

docker exec symfony_asso php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-text

HTML report:

docker exec symfony_asso php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage

TO DO : Permanent fix (rebuild image)
In app/Dockerfile, copy the ini into the path PHP actually reads, for example:

COPY ./config/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
In app/config/xdebug.ini, include coverage, e.g.:

xdebug.mode=debug,coverage
Then rebuild and restart:

docker compose build symfony
docker compose up -d symfony
After rebuild, php -i | grep xdebug.mode should show debug,coverage (or at least coverage).

## Trouble shoot

Sur Mac, il peut y avoir une erreur avec l'extension opcache.so
L'extension opcache.so de l'image phpmyadmin/phpmyadmin:latest a un symbole incompatible quand elle tourne en émulation AMD64 sur Apple Silicon. Le fix suivant supprime la config opcache au démarrage du conteneur avant de lancer Apache :
Dans docker-compose.yaml :

```bash
services:
 mysql:
+    platform: linux/amd64
   image: mysql:5.7
   container_name: mysql_gnut
   restart: always
@@ -16,6 +17,7 @@ services:

 phpmyadmin:
   image: phpmyadmin/phpmyadmin:latest
+    platform: linux/amd64
   container_name: phpmyadmin_gnut
   restart: always
   environment:
@@ -25,6 +27,7 @@ services:
     - "8080:80"
   depends_on:
     - mysql
+    entrypoint: ["bash", "-c", "rm -f /usr/local/etc/php/conf.d/*opcache* && /docker-entrypoint.sh apache2-foreground"]
```
