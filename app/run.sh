#!/bin/bash

set -e

# Installer les dépendances npm
npm install

# Démarrer le serveur Symfony
symfony server:start --no-tls --port=8000 --dir=public

# Exécuter npm run watch en arrière-plan
npm run watch