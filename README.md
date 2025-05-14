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

1. **Clonez le dépôt**

   ```bash
   git clone https://github.com/username/gnut06.git
   cd gnut06
   ```
2. **Allumage du projet**
   ```bash
   docker compose up --build
   ```

3. **Pour les utilisateurs de Mac M1/M2 (puce Apple Silicon)**

   Certaines images Docker comme mysql:5.7 ne sont pas disponibles pour l’architecture ARM utilisée par les Mac M1/M2.
   Pour éviter les erreurs du type no matching manifest for linux/arm64/v8, il faut forcer l'utilisation d'une architecture x86 :

   ```bash
   DOCKER_DEFAULT_PLATFORM=linux/amd64 docker compose up --build
   ```

   
