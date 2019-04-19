#!/bin/sh

echo "Connecting to ${DB_HOST}:${DB_PORT}"

attempts=0
max_attempts=30
while [ $attempts -lt $max_attempts ]; do
    nc -z "${DB_HOST}" "${DB_PORT}" && break
    echo "Waiting for ${DB_HOST}:${DB_PORT} ..."
    sleep 1
    let "attempts=attempts+1"
done

if [ $attempts -eq $max_attempts ]; then
  echo "Unable to contact your database at ${DB_HOST}:${DB_PORT}"
  exit 1
fi

echo "Waiting for database to settle ..."
sleep 3
