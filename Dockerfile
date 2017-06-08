FROM ubuntu

EXPOSE 80:80

RUN apt-get update

# Required system tools
RUN apt-get install -y git netcat

# PHP
RUN apt-get install -y composer npm php7.0 php7.0-gd php7.0-intl php7.0-mbstring php7.0-mysql php7.0-xml php7.0-zip

# Apache
RUN apt-get install -y apache2 libapache2-mod-php7.0

# Handy troubleshooting and experimentation tools
RUN apt-get install -y telnet vim-tiny

# Apache needs mod_rewrite
RUN a2enmod rewrite

# Configure Node and Bower
RUN cd /usr/bin && ln -s nodejs node
RUN npm install -g bower

# Create a user to own all the code and assets and give them a working directory
RUN useradd -m monica
RUN usermod -a -G monica www-data
WORKDIR /var/www/monica

# As an optimization, run install Node stuff early in the process so
# that it gets cached. That way we don't have to rerun all of this
# every time we change a config file or edit some CSS. Yes, this is
# ugly, but it shaves a few minutes off repeated build times.
ADD package.json .
RUN chown -R monica .
USER monica
RUN npm install
USER root

# Copy the local (outside Docker) source into the working directory,
# link system files into their proper homes, and set file ownership
# correctly
ADD . .
RUN cp docker/000-default.conf /etc/apache2/sites-available/
RUN chown -R monica:monica .
RUN chmod -R g+w storage
RUN chmod -R g+w bootstrap/cache

# Install composer dependencies and prepare permissions for Apache
USER monica
RUN composer install
RUN bower install
USER root

# This is the script that the container will run by default
ENTRYPOINT ["make", "-f", "/var/www/monica/docker/Makefile"]
