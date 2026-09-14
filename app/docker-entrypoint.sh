#!/bin/bash

set -e

# Writable directories required by the application.
# On Linux bind mounts, chown in this container can change the host-visible UID/GID.
DIRS_WRITABLE=(
  "var/cache"
  "var/log"
  "var/sessions"
  "public/uploads"
)

echo "==> Configuring writable directory permissions (www-data:user)..."

for d in "${DIRS_WRITABLE[@]}"; do
  mkdir -p "/var/www/app/${d}"
done

# Give Apache/PHP write access and preserve the user group for newly created files.
chown -R www-data:user \
  /var/www/app/var \
  /var/www/app/public/uploads

chmod -R ug+rwX,g+s \
  /var/www/app/var \
  /var/www/app/public/uploads

echo "==> Permissions configured; starting the final command..."

# Execute the Dockerfile CMD.
exec "$@"
