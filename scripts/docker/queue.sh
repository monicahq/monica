#!/bin/sh
set -eu

/usr/bin/php /var/www/monica/artisan queue:work --sleep=10 --timeout=0 --tries=3 --queue=default,migration >/proc/1/fd/1 2>/proc/1/fd/2
