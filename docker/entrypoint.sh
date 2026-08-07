#!/bin/sh
set -e
cd /var/www/html

# Compose owns the configuration. The .env is rendered from scratch on every
# boot so an environment change takes effect on container recreate, with no
# rebuild. Defaults are the production ones — a dev environment asks for itself.
APP_ENV="${APP_ENV:-prod}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost:8000}"
DB_DRIVER="${DB_DRIVER:-sqlite}"
# the sqlite file lives in storage/, which is the persisted volume
DB_SQLITE_PATH="${DB_SQLITE_PATH:-storage/app.sqlite}"
SESSION_NAME="${SESSION_NAME:-finance_session}"

cat > .env <<ENV
APP_ENV=${APP_ENV}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

DB_DRIVER=${DB_DRIVER}
DB_SQLITE_PATH=${DB_SQLITE_PATH}

SESSION_NAME=${SESSION_NAME}
ENV

# session files go on the volume; the default save_path is the container's
# /tmp, which empties on restart and logs everyone out
mkdir -p storage/sessions

php database/migrate.php

# the seed plants a well-known e-mail and password, so it stays out of any
# environment reachable from the internet
if [ "$APP_ENV" = "dev" ]; then
    php database/seed.php
fi

chown -R www-data:www-data storage database

exec "$@"
