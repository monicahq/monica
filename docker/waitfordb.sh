#!/bin/sh

echo "Connecting to ${DB_HOST}"

while true; do
    nc -z ${DB_HOST} 3306 && break
    echo "Unable to connect to MySQL; sleeping"
    sleep 1
done

echo "Waiting for MySQL to settle"
sleep 3
