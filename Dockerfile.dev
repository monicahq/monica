FROM alpine:latest

RUN apk update && apk upgrade; \
    apk add --virtual .build-deps \
        curl openssl; \
    apk add netcat-openbsd \
        #- base
        php7 php7-intl php7-openssl php7-ctype \
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
        #- vinkla/hashids
        php7-bcmath \
        #- sentry/sentry
        php7-curl \
        #- cbor-php (webauthn)
        php7-gmp \
# apache variant
        apache2 php7-apache2;

# Create a user to own all the code and assets and give them a working
# directory
RUN mkdir -p /var/www/monica
RUN grep -q apache /etc/group || addgroup -S apache
RUN adduser -D monica apache -h /var/www/monica
WORKDIR /var/www/monica

# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY . .

RUN mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    chown -R monica:monica .; \
    chgrp -R apache bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage;
COPY .env.dev .env

# Composer installation
RUN scripts/docker/install-composer.sh

# Install composer dependencies
USER monica
RUN composer global require hirak/prestissimo; \
    composer install --no-interaction --no-suggest; \
    composer global remove hirak/prestissimo; \
    composer clear-cache
USER root

# Cleanup
RUN apk del .build-deps && \
    rm -rf /var/cache/apk/*

ENTRYPOINT ["scripts/docker/entrypoint.sh"]

# Apache2
COPY scripts/docker/apache2-foreground /usr/local/sbin/
COPY scripts/docker/000-default.conf /etc/apache2/conf.d/

EXPOSE 80
CMD ["apache2-foreground"]
