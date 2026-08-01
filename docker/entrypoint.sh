#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
    # the sqlite file lives in storage/, which is the persisted volume
    sed -i 's#^DB_SQLITE_PATH=.*#DB_SQLITE_PATH=storage/app.sqlite#' .env
fi

php database/migrate.php
php database/seed.php
chown -R www-data:www-data storage database

exec "$@"
