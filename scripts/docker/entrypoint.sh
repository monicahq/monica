#!/bin/bash

set -Eeo pipefail

# set environment variables with docker secrets in /run/secrets/*
supportedSecrets=( "DB_PASSWORD"
                   "DATABASE_URL"
                   "APP_KEY"
                   "MAIL_PASSWORD"
                   "REDIS_PASSWORD"
                   "AWS_ACCESS_KEY_ID"
                   "AWS_SECRET_ACCESS_KEY"
                   "AWS_KEY"
                   "AWS_SECRET"
                   "LOCATION_IQ_API_KEY"
                   "WEATHERAPI_KEY"
                   "MAPBOX_API_KEY"
                  )

for secret in "${supportedSecrets[@]}"; do
    envFile="${secret}_FILE"
    if [ -n "${!envFile}" ] && [ -f "${!envFile}" ]; then
        val="$(< "${!envFile}")"
        export "${secret}"="$val"
        echo "${secret} environment variable was set by secret ${envFile}"
    fi
done

if expr "$1" : "apache" 1>/dev/null || [ "$1" = "php-fpm" ]; then

    ROOT=/var/www/html
    ARTISAN="php ${ROOT}/artisan"

    # Ensure storage directories are present
    STORAGE=${ROOT}/storage
    mkdir -p ${STORAGE}/logs
    mkdir -p ${STORAGE}/app/public
    mkdir -p ${STORAGE}/framework/views
    mkdir -p ${STORAGE}/framework/cache
    mkdir -p ${STORAGE}/framework/sessions
    chown -R www-data:www-data ${STORAGE}
    chmod -R g+rw ${STORAGE}

    if [ "${DB_CONNECTION:-sqlite}" == "sqlite" ]; then
        dbPath="${DB_DATABASE:-database/database.sqlite}"
        if [ ! -f "$dbPath" ]; then
            echo "Creating sqlite database at ${dbPath} — make sure it will be saved in a persistent volume."
            touch "$dbPath"
            chown www-data:www-data "$dbPath"
        fi
    fi

    if [ -z "${APP_KEY:-}" ]; then
        ${ARTISAN} key:generate --no-interaction
        key=$(grep APP_KEY .env | cut -c 9-)
        echo "APP_KEY generated: $key — save it for later usage."
    else
        echo "APP_KEY already set."
    fi

    # Run migrations
    ${ARTISAN} waitfordb
    ${ARTISAN} monica:setup --force -vv

    # if [ ! -f "${STORAGE}/oauth-public.key" -o ! -f "${STORAGE}/oauth-private.key" ]; then
    #     echo "Passport keys creation ..."
    #     ${ARTISAN} passport:keys
    #     ${ARTISAN} passport:client --personal --no-interaction
    #     echo "! Please be careful to backup $ROOT/storage/oauth-public.key and $ROOT/storage/oauth-private.key files !"
    # fi

fi

exec "$@"
