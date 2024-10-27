#!/bin/bash

DATABASE=database/database.sqlite

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/../scripts/realpath.sh
ROOT=$(realpath $SELF_PATH/..)

setenv() {
    sed -i "s%$1=.*%$1=$2%" $ROOT/.env
}

set_apache() {
    chgrp -R www-data $ROOT/storage && chmod -R g+w $ROOT/storage
    sudo rm -rf /var/www/html && sudo ln -s "$ROOT/public" /var/www/html
    sudo a2enmod rewrite
    sudo apache2ctl restart
}

set_database() {
    cp $ROOT/.env.example $ROOT/.env && echo "APP_TRUSTED_PROXIES=*" >> $ROOT/.env
    setenv "DB_CONNECTION" "sqlite"
    setenv "DB_DATABASE" "$ROOT/$DATABASE"
    touch $ROOT/$DATABASE && chgrp www-data database $ROOT/$DATABASE && chmod g+w database $ROOT/$DATABASE
}

set_conf() {
    setenv "CACHE_DRIVER" "database"
    setenv "QUEUE_CONNECTION" "sync"
    setenv "SESSION_DRIVER" "database"
    setenv "MAIL_MAILER" "log"
}

composer_install() {
    composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader --working-dir=$ROOT
}

yarn_install() {
    yarn --cwd $ROOT install --immutable
    yarn --cwd $ROOT run build
}

setup() {
    php $ROOT/artisan key:generate --no-interaction
    php $ROOT/artisan monica:setup --force -vvv
}

dummy() {
    php $ROOT/artisan monica:dummy --force -vvv
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
