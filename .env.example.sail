# Example configuration file to use with Laravel sail
# Copy this file to .env and adjust the values to your needs

# Initial setup:
#  cp .env.example.sail .env
#  docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install --ignore-platform-reqs
#  ./vendor/bin/sail up -d

APP_NAME=Monica
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=monica
DB_USERNAME=monica
DB_PASSWORD=password

LOG_CHANNEL=stack

CACHE_STORE=memcached
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_REPLY_TO_ADDRESS=hello@example.com
MAIL_REPLY_TO_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
MEILISEARCH_NO_ANALYTICS=false
