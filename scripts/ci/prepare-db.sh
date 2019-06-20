#!/bin/bash

set -exvo pipefail

php artisan migrate --no-interaction -vvv
php artisan db:seed --no-interaction -vvv
