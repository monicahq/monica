#!/bin/sh
set -eu

exec busybox crond -f -l 0 -L /proc/1/fd/1
