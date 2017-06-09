#!/bin/sh

ARTISAN=/var/www/monica/artisan

php ${ARTISAN} migrate --force
php ${ARTISAN} storage:link
php ${ARTISAN} db:seed --class ActivityTypesTableSeeder
php ${ARTISAN} db:seed --class CountriesSeederTable
httpd -e info -DFOREGROUND
