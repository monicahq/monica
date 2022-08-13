###
### ~ Monica dev Dockerfile
###
### This file is used for dev purpose.
### The standard monica image definition will be found here: https://github.com/monicahq/docker
### This file is based off of the `apache` variant in the above mentioned repo
###

FROM php:8.1-apache

# opencontainers annotations https://github.com/opencontainers/image-spec/blob/master/annotations.md
LABEL org.opencontainers.image.authors="Alexis Saettler <alexis@saettler.org>" \
      org.opencontainers.image.title="MonicaHQ, the Personal Relationship Manager" \
      org.opencontainers.image.description="This is MonicaHQ, your personal memory! MonicaHQ is like a CRM but for the friends, family, and acquaintances around you." \
      org.opencontainers.image.url="https://monicahq.com" \
      org.opencontainers.image.vendor="Monica"

# entrypoint.sh dependencies
RUN set -ex; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        bash \
        busybox-static \
    ; \
    rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        zlib1g-dev \
        libzip-dev \
        libpng-dev \
        libxml2-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libgmp-dev \
        libmemcached-dev \
        libmagickwand-dev \
        libwebp-dev \
    ; \
    \
    debMultiarch="$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    if [ ! -e /usr/include/gmp.h ]; then ln -s /usr/include/$debMultiarch/gmp.h /usr/include/gmp.h; fi;\
    docker-php-ext-configure intl; \
    docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp; \
    docker-php-ext-configure gmp; \
    docker-php-ext-install -j$(nproc) \
        intl \
        zip \
        bcmath \
        gd \
        gmp \
        pdo_mysql \
        mysqli \
        soap \
    ; \
    \
# pecl will claim success even if one install fails, so we need to perform each install separately
    pecl install APCu; \
    pecl install memcached; \
    pecl install redis; \
    \
    docker-php-ext-enable \
        apcu \
        memcached \
        redis \
    ; \
    \
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
        ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
        \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# Set crontab for schedules
RUN set -ex; \
    \
    mkdir -p /var/spool/cron/crontabs; \
    rm -f /var/spool/cron/crontabs/root; \
    echo '*/5 * * * * php /var/www/html/artisan schedule:run -v' > /var/spool/cron/crontabs/www-data

# Opcache
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
RUN set -ex; \
    \
    docker-php-ext-enable opcache; \
    { \
        echo '[opcache]'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
        echo 'opcache.max_accelerated_files=${PHP_OPCACHE_MAX_ACCELERATED_FILES}'; \
        echo 'opcache.memory_consumption=${PHP_OPCACHE_MEMORY_CONSUMPTION}'; \
        echo 'opcache.max_wasted_percentage=${PHP_OPCACHE_MAX_WASTED_PERCENTAGE}'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.fast_shutdown=1'; \
    } > $PHP_INI_DIR/conf.d/opcache-recommended.ini; \
    \
    echo 'apc.enable_cli=1' >> $PHP_INI_DIR/conf.d/docker-php-ext-apcu.ini; \
    \
    echo 'memory_limit=512M' > $PHP_INI_DIR/conf.d/memory-limit.ini

RUN set -ex; \
    \
    a2enmod headers rewrite remoteip; \
    { \
        echo RemoteIPHeader X-Real-IP; \
        echo RemoteIPTrustedProxy 10.0.0.0/8; \
        echo RemoteIPTrustedProxy 172.16.0.0/12; \
        echo RemoteIPTrustedProxy 192.168.0.0/16; \
    } > $APACHE_CONFDIR/conf-available/remoteip.conf; \
    a2enconf remoteip

RUN set -ex; \
    APACHE_DOCUMENT_ROOT=/var/www/html/public; \
    sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" $APACHE_CONFDIR/sites-available/*.conf; \
    sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" $APACHE_CONFDIR/apache2.conf $APACHE_CONFDIR/conf-available/*.conf

WORKDIR /var/www/html


# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY --chown=www-data:www-data . ./

RUN set -ex; \
    \
    mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    chown -R www-data:www-data bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage
COPY --chown=www-data:www-data .env.example .env

# Composer installation
COPY scripts/docker/install-composer.sh /usr/local/sbin/
RUN install-composer.sh

# Install composer dependencies
RUN set -ex; \
    \
    mkdir -p storage/framework/views; \
    composer install --no-interaction --no-progress --no-dev; \
    composer clear-cache; \
    rm -rf .composer

# Install node dependencies
RUN set -ex; \
    \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; \
    apt-get install -y nodejs; \
    npm install -g yarn; \
    yarn run inst; \
    yarn run dev; \
    \
    rm -rf /var/lib/apt/lists/*

COPY scripts/docker/entrypoint.sh \
    scripts/docker/cron.sh \
    scripts/docker/queue.sh \
    /usr/local/bin/

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
