FROM monicahq/centralperk:php-7.2-apache-node

USER root

RUN echo "ServerName monica" >> /etc/apache2/apache2.conf

COPY . .

RUN mkdir -p bootstrap/cache; \
    mkdir -p storage; \
    mkdir -p results/coverage; \
    chgrp -R www-data bootstrap/cache storage; \
    chmod -R g+w bootstrap/cache storage; \
    touch .sentry-release;
RUN sed 's/DB_TEST_PORT=.*/DB_TEST_PORT=3307/' scripts/tests/.env.mysql > .env

# Install composer dependencies
USER monica
RUN composer install --no-interaction --no-suggest && \
    composer clear-cache
