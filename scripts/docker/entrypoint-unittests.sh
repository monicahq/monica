#!/bin/bash

set -Eeo pipefail

MONICADIR=/var/www/html
ARTISAN="php ${MONICADIR}/artisan"

dbPath="${DB_DATABASE:-database/database.sqlite}"
if [ ! -f "$dbPath" ]; then
    echo "Creating sqlite database at ${dbPath}."
    touch "$dbPath"
    chown www-data:www-data "$dbPath"
fi
${ARTISAN} key:generate --no-interaction
${ARTISAN} monica:setup --force -vv

exec "$@"
