#!/bin/sh

ARTISAN=/var/www/monica/artisan

php ${ARTISAN} migrate --force
php ${ARTISAN} storage:link
httpd -e info -DFOREGROUND
