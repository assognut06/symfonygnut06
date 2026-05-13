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
   Vérifiez que le poste de dev n'aie pas déjà un serveur monopolisant se port
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
   A ce niveau, le site devrait être accessible via le navigateur sur https://localhost
   Par contre, la base de donnée est vide

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

## Arrêt

```bash
docker compose stop
```

## Démarrage normal

```bash
docker compose up -d
```

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
