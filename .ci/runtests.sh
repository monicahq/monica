#!/bin/bash
SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && pwd -P)

if [ -z "${DISPLAY:-}" ]; then
    echo Start Xvfb;
    export DISPLAY=:99.0;
    /sbin/start-stop-daemon --start --quiet --pidfile /tmp/custom_xvfb_99.pid --make-pidfile --background --exec /usr/bin/Xvfb -- :99 -ac -screen 0 1280x1024x24;
fi

if ! $(nc -z localhost 4444); then
    $SELF_PATH/../vendor/bin/selenium-server-standalone -role hub -log $SELF_PATH/../selenium-server.log -enablePassThrough false &
    until $(nc -z localhost 4444); do
        echo Waiting for selenium hub to start...;
        sleep 1;
    done
fi

export PATH="$SELF_PATH/../vendor/bin:$PATH"
if ! $(nc -z localhost 8910); then
    java -Dwebdriver.chrome.driver="$SELF_PATH/../vendor/bin/chromedriver" -jar $SELF_PATH/../vendor/se/selenium-server-standalone/bin/selenium-server-standalone.jar -role node -port 8910 -log $SELF_PATH/..selenium-node.log &
    until $(nc -z localhost 8910); do
        echo Waiting for selenium node to start...;
        sleep 1;
    done
fi

if ! $(nc -z localhost 8000); then
    pushd $SELF_PATH/..
    php laravel serve &
    popd
    until $(nc -z localhost 8000); do
        echo Waiting for laravel serve to start...;
        sleep 1;
    done
fi

$SELF_PATH/../vendor/bin/steward run laravel chrome -vvv
