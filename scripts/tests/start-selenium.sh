#!/bin/bash

set -evuo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/../realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

if [ -z "${DISPLAY:-}" ]; then
    echo Start Xvfb;
    export DISPLAY=:99.0;
    /sbin/start-stop-daemon --start --quiet --pidfile /tmp/custom_xvfb_99.pid --make-pidfile --background --exec /usr/bin/Xvfb -- :99 -ac -screen 0 1280x1024x24 || true;
fi

if ! $(nc -z localhost 4444); then
    $ROOT/vendor/bin/selenium-server-standalone -role hub -log "$ROOT/selenium-server.log" &
    until $(nc -z localhost 4444); do
        echo Waiting for selenium hub to start...;
        sleep 1;
    done
fi

if ! $(nc -z localhost 8910); then
    java -Dwebdriver.chrome.driver="$ROOT/vendor/bin/chromedriver" -jar "$ROOT/vendor/se/selenium-server-standalone/bin/selenium-server-standalone.jar" -role node -port 8910 -log "$ROOT/selenium-node.log" &
    until $(nc -z localhost 8910); do
        echo Waiting for selenium node to start...;
        sleep 1;
    done
fi
