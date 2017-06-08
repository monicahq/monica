#!/bin/sh

ARTISAN=/var/www/monica/artisan

php ${ARTISAN} migrate
php ${ARTISAN} storage:link
apachectl -d /etc/apache2 -f apache2.conf -e info -DFOREGROUND
