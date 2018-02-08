FROM alpine:3.6

EXPOSE 80:80

RUN apk update && apk add apache2 curl git make netcat-openbsd openssl php7 php7-apache2 php7-ctype php7-dom php7-fileinfo php7-gd php7-iconv php7-intl php7-json php7-mbstring php7-mysqli php7-openssl php7-pdo_mysql php7-phar php7-session php7-simplexml php7-tokenizer php7-xml php7-xmlreader php7-xmlwriter php7-zip php7-zlib php7-pgsql php7-pdo_pgsql php7-curl

RUN mkdir -p /run/apache2

# Create a user to own all the code and assets and give them a working
# directory
RUN adduser -D monica && addgroup apache monica
WORKDIR /var/www/monica

# Copy the local (outside Docker) source into the working directory,
# copy system files into their proper homes, and set file ownership
# correctly
ADD . .
RUN cp docker/000-default.conf /etc/apache2/conf.d \
    && cp .env.example .env \
    && chown -R monica:monica . \
    && chgrp -R apache bootstrap/cache storage \
    && chmod -R g+w bootstrap/cache storage

# Install composer dependencies and prepare permissions for Apache
USER monica
RUN docker/install-composer.sh && ./composer.phar install --no-interaction --prefer-dist --no-suggest --no-dev
USER root

# This is the command that the container will run by default
ENTRYPOINT ["make", "-f", "/var/www/monica/docker/Makefile"]
