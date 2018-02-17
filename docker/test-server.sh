#!/bin/sh

ARTISAN="php /var/www/monica/artisan"

if [[ -z ${APP_KEY:-} || "$APP_KEY" == "ChangeMeBy32KeyLengthOrGenerated" ]]; then
  ${ARTISAN} key:generate --no-interaction
else
  echo "APP_KEY already set"
fi
${ARTISAN} migrate --force
${ARTISAN} storage:link
${ARTISAN} db:seed --class ActivityTypesTableSeeder --force
${ARTISAN} db:seed --class CountriesSeederTable --force

# Ensure storage directories are present
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
chown -R monica:apache storage
chmod -R g+rw storage

# Run cron
crond -b &

# Run apache2
rm -f /run/apache2/httpd.pid
httpd -DFOREGROUND
