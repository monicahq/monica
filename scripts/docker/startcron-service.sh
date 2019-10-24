#!/bin/sh
if [ -f "/etc/init.d/cron" ]; then
    service cron start
fi
