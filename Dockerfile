FROM alpine:3.6

EXPOSE 80:80

RUN apk update && apk upgrade; \
    apk add --virtual .build-deps \
        curl openssl; \
    apk add apache2 make netcat-openbsd \
        #- base
        php7 php7-apache2 php7-intl php7-openssl php7-ctype \
        php7-zip php7-zlib \
        php7-redis \
        #- Authentication Guards
        php7-session php7-tokenizer \
        #- laravel/cashier sabre/vobject sabre/xml
        php7-dom \
        #- intervention/image
        php7-fileinfo \
        #- laravel/cashier
        php7-gd \
        #- composer
        php7-phar php7-json php7-iconv \
        #- laravel/framework sabre/vobject
        php7-mbstring \
        #- league/flysystem-aws-s3-v3 
        php7-simplexml \
        #- sabre/vobject sabre/xml
        php7-xml php7-xmlreader php7-xmlwriter \
        #- mysql
        php7-mysqli php7-pdo_mysql \
        #- pgsql
        php7-pgsql php7-pdo_pgsql \
        #- sentry/sentry
        php7-curl; \
    # Create apache2 dir needed for httpd
    mkdir -p /run/apache2

# Create a user to own all the code and assets and give them a working
# directory
RUN adduser -D monica apache -h /var/www/monica
WORKDIR /var/www/monica

# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
ADD . .
RUN cp .env.example .env; \
    chown -R monica:monica . && \
    chgrp -R apache bootstrap/cache storage && \
    chmod -R g+w bootstrap/cache storage && \
    # Apache2 conf
    cp scripts/docker/000-default.conf /etc/apache2/conf.d/; \
    # Composer installation
    scripts/docker/install-composer.sh; \
    # Set crontab for schedules
    echo '* * * * * /usr/bin/php /var/www/monica/artisan schedule:run' | crontab -u monica -; \
    # Cleanup
    apk del .build-deps && rm -rf /var/cache/apk/*

# Install composer dependencies and prepare permissions for Apache
USER monica
RUN composer install --no-interaction --no-suggest --no-dev && \
    composer clear-cache
USER root

# This is the command that the container will run by default
ENTRYPOINT ["make", "-f", "/var/www/monica/scripts/docker/Makefile"]
