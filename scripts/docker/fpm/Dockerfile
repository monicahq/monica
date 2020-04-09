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

RUN apk update && apk upgrade
RUN apk add --virtual .build-deps \
        curl openssl bash
RUN apk add supervisor netcat-openbsd rsync \
        #- base
        php7 php7-intl php7-openssl php7-ctype \
        php7-zip php7-zlib php7-opcache \
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
        #- web-token/jwt-signature-algorithm-eddsa
        php7-sodium

# Create a user to own all the code and assets and give them a working
# directory
RUN mkdir -p /usr/src/monica; \
    grep -q apache /etc/group || addgroup -S apache; \
    adduser -D monica apache -h /usr/src/monica
WORKDIR /usr/src/monica
VOLUME /var/www/monica


# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY --chown=monica:monica \
    readme.md \
    CONTRIBUTING.md \
    CHANGELOG.md \
    CONTRIBUTORS \
    LICENSE \
    artisan \
    composer.json \
    composer.lock \
    ./
COPY --chown=monica:monica app ./app
COPY --chown=monica:monica bootstrap ./bootstrap
COPY --chown=monica:monica config ./config
COPY --chown=monica:monica database ./database
COPY --chown=monica:monica public ./public
COPY --chown=monica:monica resources ./resources
COPY --chown=monica:monica routes ./routes

RUN mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    chown -R monica:apache bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage
COPY --chown=monica:monica .env.example .env

# Php
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
COPY scripts/docker/opcache.ini /usr/php7/conf.d/opcache.ini

# Sentry
RUN echo $VCS_REF > .sentry-release; \
    echo $COMMIT > .sentry-commit; \
    mkdir -p /root/.local/bin && \
    curl -sL https://sentry.io/get-cli/ | INSTALL_DIR=/root/.local/bin bash

# Composer installation
COPY scripts/docker/install-composer.sh /usr/local/sbin/
RUN install-composer.sh

# Install composer dependencies
USER monica
RUN composer global require hirak/prestissimo; \
    composer install --no-interaction --no-suggest --no-progress --no-dev --ignore-platform-reqs; \
    composer global remove hirak/prestissimo; \
    composer clear-cache; \
    rm -rf .composer

USER root
WORKDIR /var/www/monica

# Set crontab for schedules
RUN set -ex && \
	cd /etc/periodic/hourly/ && \
	{ \
		echo '#!/bin/sh'; \
		echo '/usr/bin/php /var/www/monica/artisan schedule:run -v > /proc/1/fd/1 2> /proc/1/fd/2'; \
	} | tee monica && \
	chmod a+x monica

# Cleanup
RUN apk del .build-deps && \
    rm -rf /var/cache/apk/*

COPY scripts/docker/upgrade.exclude \
    /usr/local/share/
COPY scripts/docker/entrypoint.sh \
    scripts/docker/cron.sh \
    scripts/docker/queue.sh \
    /usr/local/bin/
ENTRYPOINT ["entrypoint.sh"]

# FPM
RUN apk add php7-fpm
RUN set -ex && \
	cd /etc/php7/php-fpm.d && \
	{ \
		echo '[global]'; \
		echo 'error_log = /dev/stderr'; \
		echo; \
		echo '[www]'; \
		echo 'access.log = /dev/stdout'; \
		echo 'catch_workers_output = yes'; \
        echo 'clear_env = no'; \
	} | tee docker.conf && \
	{ \
		echo '[global]'; \
		echo 'daemonize = no'; \
		echo; \
		echo '[www]'; \
		echo 'group = apache'; \
		echo 'listen = 9000'; \
	} | tee zz-docker.conf

# Override stop signal to stop process gracefully
# https://github.com/php/php-src/blob/17baa87faddc2550def3ae7314236826bc1b1398/sapi/fpm/php-fpm.8.in#L163
STOPSIGNAL SIGQUIT

EXPOSE 9000

CMD ["php-fpm7"]
