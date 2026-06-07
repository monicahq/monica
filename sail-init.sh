#!/bin/bash
#
# Run this script after first cloning the repository to be ready to use the sail command.
#
# taken from https://laravel.com/docs/11.x/sail#installing-composer-dependencies-for-existing-projects
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -e COMPOSER_HOME=/tmp \
    -e CACHE_STORE=array \
    -e CACHE_DRIVER=array \
    -e SESSION_DRIVER=array \
    -e QUEUE_CONNECTION=sync \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
ln -sf ./vendor/bin/sail sail
