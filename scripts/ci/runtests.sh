#!/bin/bash

set -exvo pipefail

mkdir -p results/coverage
sed 's/DB_TEST_PASSWORD=/DB_TEST_PASSWORD=root/' scripts/tests/.env.mysql > .env
touch .sentry-release

phpdbg -dmemory_limit=4G -qrr vendor/bin/phpunit -c phpunit.xml --log-junit ./results/junit/unit/results${SYSTEM_JOBPOSITIONINPHASE}.xml --coverage-clover ./results/coverage${SYSTEM_JOBPOSITIONINPHASE}.xml --testsuite $TESTSUITE
