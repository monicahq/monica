#!/bin/sh

while true; do
    nc -z mysql 3306 && break
    echo 'Unable to connect to MySQL; sleeping.'
    sleep 1
done

echo 'Waiting for MySQL to settle'
sleep 3
