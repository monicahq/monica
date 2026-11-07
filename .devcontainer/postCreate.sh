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

# The reference search engine, the one the suite proves search against.
install_meilisearch() {
    case "$(uname -m)" in
        aarch64|arm64) ARCH=aarch64 ;;
        *) ARCH=amd64 ;;
    esac
    sudo curl -fsSL -o /usr/local/bin/meilisearch "https://github.com/meilisearch/meilisearch/releases/download/v1.56.1/meilisearch-linux-$ARCH"
    sudo chmod +x /usr/local/bin/meilisearch
    setenv "SCOUT_DRIVER" "meilisearch"
    setenv "MEILISEARCH_HOST" "http://127.0.0.1:7700"
    setenv "MEILISEARCH_KEY" "master-key"
}

composer_install() {
    composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader --working-dir=$ROOT
}

bun_install() {
    bun install --cwd=$ROOT --frozen-lockfile
    bun run --cwd=$ROOT build
}

setup() {
    php $ROOT/artisan key:generate --no-interaction
    php $ROOT/artisan monica:setup --force -vvv
    php $ROOT/artisan migrate --force --seed -vvv
}

set_apache
set_database
set_conf
install_meilisearch
composer_install
setup
bun_install
