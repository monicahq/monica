#!/bin/bash

SYSTEM_JOBPOSITIONINPHASE=$1
TESTSUITE=$2

set -exvo pipefail

php artisan passport:keys --no-interaction -vvv

phpdbg -dmemory_limit=4G -qrr vendor/bin/phpunit -c phpunit.xml --log-junit ./results/junit/unit/results${SYSTEM_JOBPOSITIONINPHASE}.xml --coverage-clover ./results/coverage${SYSTEM_JOBPOSITIONINPHASE}.xml --testsuite $TESTSUITE
