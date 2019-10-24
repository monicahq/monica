#!/bin/sh
if [ -f "/usr/sbin/crond" ]; then
    crond -b -l 0 -L /dev/stdout
fi
