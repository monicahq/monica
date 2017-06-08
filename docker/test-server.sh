#!/bin/sh

ARTISAN=/var/www/monica/artisan

php ${ARTISAN} migrate
php ${ARTISAN} storage:link
httpd -e info -DFOREGROUND
