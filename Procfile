web: vendor/bin/heroku-php-nginx -C nginx_app.conf /public
queue: php artisan queue:work --sleep=10 --timeout=0 --tries=3 --queue=default,migration
release: php artisan monica:update --force -vvv
