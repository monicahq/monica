#!/bin/bash
set -evuo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
ROOT=$(realpath $SELF_PATH/..)
HOST=localhost

$SELF_PATH/start-selenium.sh

if ! $(nc -z $HOST 8000); then
    pushd $ROOT
    php artisan serve --host=$HOST &
    popd
    until $(nc -z $HOST 8000); do
        echo Waiting for laravel serve to start...;
        sleep 1;
    done
fi

pushd $ROOT
$ROOT/vendor/bin/steward run laravel chrome -vv
popd
