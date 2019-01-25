FROM alpine:latest

# Build-time metadata as defined at http://label-schema.org
ARG BUILD_DATE
ARG VCS_REF
ARG COMMIT
ARG VERSION
LABEL org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.name="MonicaHQ, the Personal Relationship Manager" \
      org.label-schema.description="This is MonicaHQ, your personal memory! MonicaHQ is like a CRM but for the friends, family, and acquaintances around you." \
      org.label-schema.url="https://monicahq.com" \
      org.label-schema.vcs-ref=$VCS_REF \
      org.label-schema.vcs-url="https://github.com/monicahq/monica" \
      org.label-schema.vendor="Monica" \
      org.label-schema.version=$VERSION \
      org.label-schema.schema-version="1.0"

EXPOSE 80:80

RUN apk update && apk upgrade
RUN apk add --virtual .build-deps \
        curl openssl bash
RUN apk add apache2 make netcat-openbsd \
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
        #- vinkla/hashids
        php7-bcmath \
        #- sentry/sentry
        php7-curl
# Create apache2 dir needed for httpd
RUN mkdir -p /run/apache2

# Create a user to own all the code and assets and give them a working
# directory
RUN adduser -D monica apache -h /var/www/monica
WORKDIR /var/www/monica

# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY readme.md \
    CONTRIBUTING.md \
    CHANGELOG \
    CONTRIBUTORS \
    LICENSE \
    .env.example \
    artisan \
    composer.json \
    composer.lock \
    package.json \
    app.json \
    nginx_app.conf \
    webpack.mix.js \
    yarn.lock \
    ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY scripts ./scripts

RUN echo $VCS_REF > .sentry-release
RUN echo $COMMIT > .sentry-commit
RUN mkdir -p bootstrap/cache
RUN mkdir -p storage
COPY .env.example .env
RUN chown -R monica:monica .
RUN chgrp -R apache bootstrap/cache storage
RUN chmod -R g+w bootstrap/cache storage

# Sentry
RUN mkdir -p /root/.local/bin
RUN curl -sL https://sentry.io/get-cli/ | INSTALL_DIR=/root/.local/bin bash

# Apache2 conf
COPY scripts/docker/000-default.conf /etc/apache2/conf.d/
# Composer installation
RUN scripts/docker/install-composer.sh
# Set crontab for schedules
RUN echo '* * * * * /usr/bin/php /var/www/monica/artisan schedule:run' | crontab -u monica -
# Cleanup
RUN apk del .build-deps && rm -rf /var/cache/apk/*

# Install composer dependencies and prepare permissions for Apache
USER monica
RUN composer install --no-interaction --no-suggest --no-dev
RUN composer clear-cache
USER root

# This is the command that the container will run by default
ENTRYPOINT ["make", "-f", "/var/www/monica/scripts/docker/Makefile"]
