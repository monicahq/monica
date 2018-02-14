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
chown -R monica:apache /var/www/monica/storage/app/public/
chmod -R g+rw /var/www/monica/storage/app/public/

# Run apache2
rm -f /run/apache2/httpd.pid
httpd -DFOREGROUND &

# Run cron
crond -b &
