# The image the application runs in: Apache serving public/, PHP with the extensions the
# application needs, and the application itself with its dependencies installed and its assets
# built. A container built from it serves on its own. compose.yaml mounts the checkout over the
# copy baked in here, and docker-entrypoint.sh brings that checkout to the same state.
FROM php:8.4-apache

# entrypoint.sh dependencies
RUN set -ex; \
    \
    apt-get update --quiet; \
    apt-get install --yes --quiet --no-install-recommends \
        bash \
        busybox-static \
        unzip \
    ; \
    rm -rf /var/lib/apt/lists/*

# gd needs the image libraries, intl needs ICU, zip needs libzip. git and unzip are what Composer
# reads a package from a dist archive with.
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
        libpq-dev \
        libxml2-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libgmp-dev \
        libmemcached-dev \
        libssl-dev \
        libwebp-dev \
        libcurl4-openssl-dev \
    ; \
    \
    debMultiarch="$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    if [ ! -e /usr/include/gmp.h ]; then ln -s /usr/include/$debMultiarch/gmp.h /usr/include/gmp.h; fi;\
    docker-php-ext-configure intl; \
    docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp; \
    docker-php-ext-configure gmp; \
    docker-php-ext-install --jobs "$(nproc)" \
        intl \
        zip \
        bcmath \
        gd \
        gmp \
        pdo_mysql \
        mysqli \
        pdo_pgsql \
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
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# bun is the package manager and task runner the project declares, at the version package.json
# pins. The Vite binary carries a node shebang, so node is what runs the asset build, at the
# version the Static analysis workflow pins.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:26-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=oven/bun:1.4.5-debian /usr/local/bin/bun /usr/local/bin/bun
RUN ln --symbolic bun /usr/local/bin/bunx

# Set crontab for schedules
RUN set -ex; \
    \
    mkdir -p /var/spool/cron/crontabs; \
    rm -f /var/spool/cron/crontabs/root; \
    echo '* * * * * php /var/www/html/artisan schedule:run -v' > /var/spool/cron/crontabs/www-data; \
    chmod 600 /var/spool/cron/crontabs/www-data

# Opcache
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
# Limits
ENV PHP_MEMORY_LIMIT="512M" \
    PHP_UPLOAD_LIMIT="512M"
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
    { \
        echo 'memory_limit=${PHP_MEMORY_LIMIT}'; \
        echo 'upload_max_filesize=${PHP_UPLOAD_LIMIT}'; \
        echo 'post_max_size=${PHP_UPLOAD_LIMIT}'; \
    } > $PHP_INI_DIR/conf.d/limits.ini;

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

# set apache config LimitRequestBody
ENV APACHE_BODY_LIMIT=1073741824
RUN set -ex; \
    \
    { \
        echo 'LimitRequestBody ${APACHE_BODY_LIMIT}'; \
    } > $APACHE_CONFDIR/conf-available/apache-limits.conf; \
    a2enconf apache-limits

RUN set -ex; \
    APACHE_DOCUMENT_ROOT=/var/www/html/public; \
    sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" $APACHE_CONFDIR/sites-available/*.conf; \
    sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" $APACHE_CONFDIR/apache2.conf $APACHE_CONFDIR/conf-available/*.conf

# Development mounts the checkout over the application, so the container writes vendor,
# node_modules, storage and .env into it. Matching the identifiers of the user outside leaves
# those files theirs. A group already carrying the identifier is left as it is, since all the
# user needs is to belong to it.
ARG UID=1000
ARG GID=1000
RUN groupadd --gid "${GID}" monica || true; \
    useradd --uid "${UID}" --gid "${GID}" --create-home monica \
    && mkdir --parents /var/www/html \
    && chown monica:monica /var/www/html

RUN mkdir --parents /var/run/apache2 /var/lock/apache2 \
    && chown --recursive monica:monica /var/run/apache2 /var/lock/apache2 /var/log/apache2

# envvars reads the two below with := , so an environment already carrying them wins. Apache cannot
# change user once it has started as one, and telling it which user that is keeps it from warning.
ENV APACHE_RUN_USER=monica
ENV APACHE_RUN_GROUP=monica
ENV HOME=/home/monica
WORKDIR /var/www/html
USER monica

# Each manifest and its lock file on their own first, so editing the application does not
# reinstall its dependencies on the next build. The autoloader is left for below, because the
# scripts that generate it read the application. The image carries what the application needs to
# serve and nothing else, so the test suite and the tooling are not in it: docker-entrypoint.sh
# adds them where a development checkout asks for them.
COPY --chown=monica:monica composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader

COPY --chown=monica:monica package.json bun.lock ./
RUN bun install --frozen-lockfile --silent

# The application, then the environment it runs on when nothing supplies one.
COPY --chown=monica:monica . .
RUN cp .env.docker .env

RUN composer dump-autoload --no-dev --no-interaction \
    && bun run build

RUN set -ex; \
    { \
        echo '#!/bin/sh'; \
        echo 'set -eu'; \
        echo 'exec busybox crond -f -l 0 -L /proc/1/fd/1'; \
    } > ./cron.sh; \
    chmod +x ./cron.sh
RUN set -ex; \
    { \
        echo '#!/bin/sh'; \
        echo 'set -eu'; \
        echo 'exec php /var/www/html/artisan queue:work --sleep=10 --timeout=0 --tries=3 --queue=high,default,low >/proc/1/fd/1 2>/proc/1/fd/2'; \
    } > ./queue.sh; \
    chmod +x ./queue.sh

# The entrypoint is read from the application rather than copied somewhere of its own, so that
# editing it does not reinstall the dependencies, and needs no rebuild at all under compose, where
# the checkout is mounted over this copy.
ENTRYPOINT ["./docker-entrypoint.sh"]
CMD ["apache2-foreground"]
