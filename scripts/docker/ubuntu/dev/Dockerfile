FROM ubuntu:latest

RUN apt-get update; \
    apt-get -y install curl gnupg2 apt-transport-https ca-certificates; \
    ln -fs /usr/share/zoneinfo/America/New_York /etc/localtime; \
    export DEBIAN_FRONTEND=noninteractive; \
    apt-get --force-yes -y install netcat-openbsd \
        #- base
        php7.2 php7.2-common php7.2-opcache \
        php7.2-intl php7.2-zip \
        #- laravel/cashier
        php7.2-gd \
        #- composer
        php7.2-json \
        #- laravel/framework sabre/vobject
        php7.2-mbstring \
        #- sabre/vobject sabre/xml
        php7.2-xml \
        #- mysql
        php7.2-mysql \
        #- vinkla/hashids
        php7.2-bcmath \
        #- sentry/sentry
        php7.2-curl \
        #- cbor-php (webauthn)
        php7.2-gmp \
# apache variant
        apache2 php7.2-fpm; \
    dpkg-reconfigure --frontend noninteractive tzdata

# Create a user to own all the code and assets and give them a working
# directory
RUN mkdir -p /var/www/monica; \
    adduser --ingroup www-data --home /var/www/monica --no-create-home monica;\
    echo "ServerName monica" >> /etc/apache2/apache2.conf
WORKDIR /var/www/monica

# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
COPY . .

RUN mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    chown -R monica:www-data .; \
    chgrp -R www-data bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage; \
    mkdir -p results/coverage; \
    touch .sentry-release;
COPY scripts/tests/.env.mysql .env

# Composer installation
RUN scripts/docker/install-composer.sh

# Install composer dependencies
USER monica
RUN composer global require hirak/prestissimo; \
    composer install --no-interaction --no-suggest; \
    composer global remove hirak/prestissimo; \
    composer clear-cache
USER root

# Install dev components
RUN scripts/docker/ubuntu/install-yarn.sh; \
# phpdbg package
    apt-get install php7.2-phpdbg;

# Cleanup
RUN apt-get -y remove curl gnupg2; \
    apt-get -y autoremove; \
    apt-get -y clean

ENTRYPOINT ["scripts/docker/entrypoint.sh"]

# Apache2
RUN a2enmod proxy_fcgi setenvif; \
    a2enconf php7.2-fpm; \
    ln -sf /usr/sbin/apache2ctl /usr/local/sbin/httpd
COPY scripts/docker/apache2-foreground /usr/local/sbin/
COPY scripts/docker/000-default.conf /etc/apache2/conf.d/

RUN set -ex && \
	cd /etc/php/7.2/fpm/pool.d && \
	{ \
		echo '[global]'; \
		echo 'error_log = /dev/stderr'; \
		echo; \
		echo '[www]'; \
		echo 'access.log = /dev/stdout'; \
		echo 'catch_workers_output = yes'; \
	} | tee docker.conf && \
	{ \
		echo '[global]'; \
		echo 'daemonize = no'; \
		echo; \
		echo '[www]'; \
		echo 'group = www-data'; \
		echo 'listen = 9000'; \
	} | tee zz-docker.conf

EXPOSE 80
CMD ["apache2-foreground"]
