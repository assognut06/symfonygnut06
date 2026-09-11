#!/bin/bash

set -e

# Répertoires d'écriture nécessaires à l'application
# (les dossiers montés en bind depuis l'hôte ne conservent pas les
#  propriétaires/chmod définis à la construction de l'image)
DIRS_WRITABLE=(
  "var/cache"
  "var/log"
  "var/sessions"
  "public/uploads"
)

echo "==> Configuration des permissions des dossiers inscriptibles (www-data)..."

# 1. Création des dossiers s'ils n'existent pas
for d in "${DIRS_WRITABLE[@]}"; do
  mkdir -p "/var/www/app/${d}"
done

# 2. Donne la propriété et les droits d'écriture à www-data
#    -> www-data peut écrire dans var/ et public/uploads,
#       indépendamment de l'UID/GID du poste hôte.
chown -R www-data:www-data \
  /var/www/app/var \
  /var/www/app/public/uploads

chmod -R ug+rwX,g+s \
  /var/www/app/var \
  /var/www/app/public/uploads

echo "==> Permissions OK, lancement de la commande finale..."

# Relance le CMD (apache2-foreground)
exec "$@"