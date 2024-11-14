#!/bin/sh
set -eu

exec php /var/www/html/artisan schedule:run -v >/proc/1/fd/1 2>/proc/1/fd/2
