#!/bin/bash

set -exvo pipefail

sed 's/DB_TEST_PASSWORD=/DB_TEST_PASSWORD=root/' scripts/tests/.env.mysql > .env

php artisan migrate --no-interaction -vvv
