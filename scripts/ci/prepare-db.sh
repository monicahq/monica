#!/bin/bash

set -evo pipefail

php artisan migrate --no-interaction -vvv
php artisan db:seed --no-interaction -vvv
