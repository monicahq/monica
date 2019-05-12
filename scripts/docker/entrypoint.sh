#!/bin/sh

waitfordb() {
    echo "Connecting to ${DB_HOST}:${DB_PORT}"

    attempts=0
    max_attempts=30
    while [ $attempts -lt $max_attempts ]; do
        nc -z "${DB_HOST}" "${DB_PORT}" && break
        echo "Waiting for ${DB_HOST}:${DB_PORT} ..."
        sleep 1
        let "attempts=attempts+1"
    done

    if [ $attempts -eq $max_attempts ]; then
        echo "Unable to contact your database at ${DB_HOST}:${DB_PORT}"
        exit 1
    fi

    echo "Waiting for database to settle ..."
    sleep 3
}

if expr "$1" : "apache" 1>/dev/null || [ "$1" = "php-fpm7" ]; then

    MONICADIR=/var/www/monica
    ARTISAN="php ${MONICADIR}/artisan"

    # Ensure storage directories are present
    STORAGE=${MONICADIR}/storage
    mkdir -p ${STORAGE}/logs
    mkdir -p ${STORAGE}/app/public
    mkdir -p ${STORAGE}/framework/views
    mkdir -p ${STORAGE}/framework/cache
    mkdir -p ${STORAGE}/framework/sessions
    chown -R monica:apache ${STORAGE}
    chmod -R g+rw ${STORAGE}

    if [[ -z "${APP_KEY:-}" || "$APP_KEY" == "ChangeMeBy32KeyLengthOrGenerated" ]]; then
        ${ARTISAN} key:generate --no-interaction
    else
        echo "APP_KEY already set"
    fi

    # Run migrations
    waitfordb
    ${ARTISAN} monica:update --force -v

    if [[ -n "${SENTRY_SUPPORT:-}" && "$SENTRY_SUPPORT" == "true" && -z "${SENTRY_NORELEASE:-}" && -n "${SENTRY_ENV:-}" ]]; then
        commit=$(cat .sentry-commit)
        release=$(cat .sentry-release)
        ${ARTISAN} sentry:release --release="$release" --commit="$commit" --environment="$SENTRY_ENV" -v || true
    fi

    # Run cron
    crond -b -l 0 -L /dev/stdout

fi

exec "$@"
