#!/bin/sh

ARTISAN="php /var/www/monica/artisan"

${ARTISAN} migrate --force
${ARTISAN} storage:link
${ARTISAN} db:seed --class ActivityTypesTableSeeder
${ARTISAN} db:seed --class CountriesSeederTable
httpd
while true; do
    sleep 60
    ${ARTISAN} schedule:run
done
