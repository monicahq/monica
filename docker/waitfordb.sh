#!/bin/sh

echo "Connecting to ${DB_HOST}:${DB_PORT}"

while true; do
    nc -z ${DB_HOST} ${DB_PORT} && break
    echo "Unable to connect to database; sleeping"
    sleep 1
done

echo "Waiting for database to settle"
sleep 3
