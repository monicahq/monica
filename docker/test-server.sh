#!/bin/sh

ARTISAN="php /var/www/monica/artisan"

${ARTISAN} key:generate --no-interaction
${ARTISAN} migrate --force
${ARTISAN} storage:link
${ARTISAN} db:seed --class ActivityTypesTableSeeder --force
${ARTISAN} db:seed --class CountriesSeederTable --force
httpd
while true; do
    sleep 60
    ${ARTISAN} schedule:run
done
