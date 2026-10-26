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

# This directory is created on the bind mount by the root entrypoint. Make it
# writable by Apache/PHP so LiipImagine can generate logo thumbnails.
chown -R www-data:user /var/www/app/public/media

# Keep bind-mount ownership unchanged. The host ACLs grant access to the
# developer and www-data; chown here would replace the host-visible owner with
# the container user (often nobody), forcing sudo for host-side commands.
chmod -R ug+rwX,g+s \
  /var/www/app/var \
  /var/www/app/public/uploads \
  /var/www/app/public/media

echo "==> Permissions configured; starting the final command..."

# Execute the Dockerfile CMD.
exec "$@"
