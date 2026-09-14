#!/bin/bash
#
# Brings the application to a state it can serve from, then starts whatever the container was
# asked to run. Every step is safe to repeat, so this runs on every start.

set -Eeo pipefail

if expr "$1" : "apache" 1>/dev/null || [ "$1" = "php-fpm" ]; then

    # Ensure storage directories are present
    mkdir -p storage/{logs,app/public,framework/views,framework/cache,framework/sessions}

    if [ "${DB_CONNECTION:-sqlite}" == "sqlite" ]; then
        database_path="${DB_DATABASE:-database/database.sqlite}"
        if [ ! -f "$database_path" ]; then
            echo "Creating sqlite database at ${database_path} — make sure it will be saved in a persistent volume."
            touch "$database_path"
        fi
    fi

    if [ -z "${APP_KEY:-}" ]; then
        php artisan key:generate --force
    fi


    # compose waits for the healthcheck of the database before it starts this container. A plain
    # `docker run` does not, so wait here as well rather than fall over on the first query.
    attempt=1
    until php artisan db:show > /dev/null 2>&1; do
        if [ "${attempt}" -ge 30 ]; then
            echo "The database did not answer after ${attempt} attempts. Giving up."
            exit 1
        fi

        attempt=$((attempt + 1))
        sleep 2
    done

    # The database step comes first, against the order the command itself runs the two in. Caching the
    # configuration is refused while the cache table is missing, and on a database nobody has migrated
    # yet, that table only appears once the migrations have run.
    php artisan monica:setup --deploy --force -vvv
    php artisan monica:setup --build --force -vvv

fi

exec "$@"
