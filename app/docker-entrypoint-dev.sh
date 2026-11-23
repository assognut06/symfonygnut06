#!/bin/bash
set -e

# These generated directories are on the container's Linux filesystem.
# Give Apache write access without changing ownership of Windows bind mounts.
mkdir -p /var/www/app/var /var/www/app/public/build
chown -R www-data:www-data /var/www/app/var /var/www/app/public/build
chmod -R ug+rwX,g+s /var/www/app/var /var/www/app/public/build

exec /usr/local/bin/docker-entrypoint.sh "$@"
