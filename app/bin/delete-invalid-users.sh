#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
RUNTIME_ENV="${1:-${APP_ENV:-prod}}"
PHP_BINARY="${PHP_BINARY:-php}"

if [[ $# -gt 1 ]]; then
    echo "Usage: $0 [environnement Symfony]" >&2
    exit 2
fi

if ! command -v "${PHP_BINARY}" >/dev/null 2>&1; then
    echo "Erreur : l'exécutable PHP '${PHP_BINARY}' est introuvable." >&2
    exit 1
fi

if [[ ! -f "${APP_DIR}/bin/console" ]]; then
    echo "Erreur : bin/console est introuvable dans ${APP_DIR}." >&2
    exit 1
fi

cd "${APP_DIR}"

exec "${PHP_BINARY}" bin/console dbal:run-sql \
    'SELECT delete_invalid_users() AS deleted_users' \
    --env="${RUNTIME_ENV}" \
    --no-debug \
    --no-interaction
