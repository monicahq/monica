#!/bin/bash

DATABASE=database/database.sqlite

setenv() {
    sed -i "s%$1=.*%$1=$2%" .env
}

set_apache() {
    chgrp -R www-data storage && chmod -R g+w storage
    chmod a+x /root && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)/public" /var/www/html
    a2enmod rewrite
    service apache2 restart
}

set_database() {
    cp .env.example .env && echo "APP_TRUSTED_PROXIES=*" >> .env
    setenv "DB_CONNECTION" "sqlite"
    setenv "DB_DATABASE" "$(pwd)/$DATABASE"
    touch $DATABASE && chgrp www-data database $DATABASE && chmod g+w database $DATABASE
}

set_conf() {
    setenv "CACHE_DRIVER" "database"
    setenv "QUEUE_CONNECTION" "sync"
    setenv "SESSION_DRIVER" "database"
    setenv "MAIL_MAILER" "log"
    setenv "MAIL_FROM_ADDRESS" "from@mail.com"
    setenv "MAIL_REPLY_TO_ADDRESS" "reply@mail.com"
}

composer_install() {
    composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader
}

yarn_install() {
    yarn install --frozen-lockfile
    yarn run build
}

setup() {
    php artisan key:generate --no-interaction
    php artisan monica:setup --force -vvv
}

dummy() {
    php artisan monica:dummy --force -vvv
}

set_apache
set_database
set_conf
composer_install
setup
yarn_install

case "$1" in
    "dummy")
        dummy
        ;;
esac
