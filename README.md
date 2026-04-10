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
   copier la sortie console de cat dans les paramètres du projet git avec le navigateur
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
   - changez le port dans le fichier docker-compose.yaml en remplaçant tous les 3306 par un autre port libre (déconseillé car il y a un risque d'archiver le changement)
4. **Allumage du projet**
   ```bash
   sudo groupmod -U <username> docker
   docker compose up --build
   ```
e
   
