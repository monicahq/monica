FROM php:7.3-apache

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

RUN apt-get update && \
    apt-get -y --no-install-recommends install \
      git cron netcat apt apt-transport-https \
      locales sudo openssh-client ca-certificates \
      curl unzip zip tar bzip2 supervisor rsync && \
    mkdir /var/log/supervisord /var/run/supervisord && \
    rm -rf /var/lib/apt/lists/*

# Install common extensions
RUN apt-get update && \
    apt-get -y autoremove && \
    apt-get -y --no-install-recommends install \
                libicu-dev \
                zlib1g-dev \
                libzip-dev \
                libpng-dev \
                libxml2-dev \
                libfreetype6-dev \
                libjpeg62-turbo-dev \
                libgmp-dev \
                libsodium-dev && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) \
                intl \
                zip \
                json \
                iconv \
                bcmath \
                gd \
                gmp \
                pdo_mysql \
                mysqli \
                soap \
                sodium \
                mbstring \
                opcache && \
    apt-get -y remove \
                icu-devtools \
                libicu-dev \
                zlib1g-dev \
                libzip-dev \
                libpng-dev \
                libxml2-dev \
                libfreetype6-dev \
                libjpeg62-turbo-dev \
                libgmp-dev \
                libsodium-dev && \
    apt-get -y clean && \
    rm -rf /var/lib/apt/lists/*

# Create a user to own all the code and assets and give them a working
# directory
RUN mkdir -p /usr/src/monica && \
    addgroup --gid 1001 monica && \
    adduser --uid 1001 --ingroup monica --home /usr/src/monica --shell /bin/bash --no-create-home monica && \
    adduser monica www-data && \
    chown -R monica:www-data /usr/src/monica && \
    echo 'monica ALL=NOPASSWD: ALL' >> /etc/sudoers.d/50-monica && \
    echo 'Defaults    env_keep += "DEBIAN_FRONTEND"' >> /etc/sudoers.d/env_keep
WORKDIR /usr/src/monica
VOLUME /var/www/monica


# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY --chown=monica:www-data \
    readme.md \
    CONTRIBUTING.md \
    CHANGELOG.md \
    CONTRIBUTORS \
    LICENSE \
    artisan \
    composer.json \
    composer.lock \
    ./
COPY --chown=monica:www-data app ./app
COPY --chown=monica:www-data bootstrap ./bootstrap
COPY --chown=monica:www-data config ./config
COPY --chown=monica:www-data database ./database
COPY --chown=monica:www-data public ./public
COPY --chown=monica:www-data resources ./resources
COPY --chown=monica:www-data routes ./routes

RUN mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    chown -R monica:www-data bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage
COPY --chown=monica:www-data .env.example .env

# Php
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"
COPY scripts/docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

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
	cd /etc/cron.hourly && \
	{ \
		echo '#!/bin/sh'; \
		echo '/usr/bin/php /var/www/monica/artisan schedule:run -v > /proc/1/fd/1 2> /proc/1/fd/2'; \
	} | tee monica && \
	chmod a+x monica

COPY scripts/docker/upgrade.exclude \
    /usr/local/share/
COPY scripts/docker/entrypoint.sh \
    scripts/docker/php-apache/cron.sh \
    scripts/docker/queue.sh \
    /usr/local/bin/
RUN sed -ri -e 's!monica:apache!monica:www-data!g' /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]

# Apache2
COPY scripts/docker/000-default.conf /etc/apache2/sites-available/
RUN echo 'ServerName "monica"' > /etc/apache2/conf-available/hostname.conf
RUN a2enconf hostname
RUN a2enmod rewrite cache headers

EXPOSE 80

CMD ["apache2-foreground"]
