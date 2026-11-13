#!/bin/bash

set -e

# Writable directories required by the application.
# On Linux bind mounts, chown in this container can change the host-visible UID/GID.
DIRS_WRITABLE=(
  "var/cache"
  "var/log"
  "var/sessions"
  "var/tihcv"
  "var/tihattest"
  "public/uploads"
  "public/uploads/bordereau"
  "public/uploads/cv"
  "public/uploads/logo_entreprises"
  "public/uploads/profilePictures"
  "public/uploads/tih"
  "public/media/cache"
)

echo "==> Configuring writable directory permissions..."

for d in "${DIRS_WRITABLE[@]}"; do
  mkdir -p "/var/www/app/${d}"
done

# mkdir runs as root, so fix ownership on mounted directories before PHP starts.
chown -R www-data:user \
  /var/www/app/var \
  /var/www/app/public/uploads \
  /var/www/app/public/media

chmod -R ug+rwX,g+s \
  /var/www/app/var \
  /var/www/app/public/uploads \
  /var/www/app/public/media

echo "==> Permissions configured; starting the final command..."

# Execute the Dockerfile CMD.
exec "$@"
