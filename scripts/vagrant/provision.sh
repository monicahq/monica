#!/usr/bin/env bash

#
# Author: Daniel Pellarini
# GitHub profile: dp87 - https://github.com/dp87/
# Date: 11/12/2017
#
# Provisioning script to install Monica Personal Relationship Manager on a Ubuntu 16.04 box
#

set -euo pipefail

MYSQL_ROOT_PASSWORD=changeme
MYSQL_MONICA_DATABASE=monica
MYSQL_MONICA_USER=monica
MYSQL_MONICA_PASSWORD=changeme
DESTDIR=/var/www/html/monica

MONICA_USER=admin@admin.com
MONICA_PASSWORD=admin

function update_setting() {
  file=$1
  name=$2
  value=$3
  if $(grep -q "$name" $file); then
    sed -i "s/\($name\).*/\1=$value/" $file;
  else
    echo -e "\n$name=$value" | tee -a $file;
  fi
}

echo "########################"
echo "Installing prerequisites"
echo "########################"

echo "Installing apache"
apt-get update
apt-get install -y apache2

echo "ServerName vagrant" >> /etc/apache2/apache2.conf # suppress apache warning

echo "Installing MySQL with default root password"
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
apt-get install -y mysql-server mysql-client

#echo "Installing PHP"
#apt-get install -y php libapache2-mod-php php-mcrypt php-mysql >/dev/null 2>&1

echo "Installing PHP 7.1"
add-apt-repository -y ppa:ondrej/php
apt-get update >/dev/null
apt-get install -y php7.1 >/dev/null

echo "Installing git"
apt-get install -y git >/dev/null

echo "Installing composer"
apt-get install -y curl php7.1-cli git
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "Installing packages for Monica"
apt-get install -y php7.1-intl php7.1-simplexml php7.1-gd php7.1-mbstring php7.1-curl php7.1-zip php7.1-mysql

echo "Getting database ready"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_MONICA_DATABASE;
CREATE USER '$MYSQL_MONICA_USER'@'localhost' IDENTIFIED BY '$MYSQL_MONICA_PASSWORD';
GRANT ALL ON $MYSQL_MONICA_DATABASE.* TO '$MYSQL_MONICA_USER'@'localhost';
FLUSH PRIVILEGES;"

echo "Installing Monica"
git clone https://github.com/monicahq/monica.git $DESTDIR
cd $DESTDIR
composer install --no-interaction --prefer-dist --no-suggest --optimize-autoloader --no-dev --no-progress

echo "Configuring Monica"
cp .env.example .env
update_setting .env DB_DATABASE "$MYSQL_MONICA_DATABASE"
update_setting .env DB_USERNAME "$MYSQL_MONICA_USER"
update_setting .env DB_PASSWORD "$MYSQL_MONICA_PASSWORD"
update_setting .env APP_DISABLE_SIGNUP "false"
chown -R www-data:www-data .
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan setup:production --force --email=$MONICA_USER --password=$MONICA_PASSWORD
sudo -u www-data php artisan passport:install

echo "Configuring cron script"
{ crontab -l -u www-data; echo "* * * * * /usr/bin/php $DESTDIR/artisan schedule:run"; } | crontab -u www-data - || true

echo "Configuring apache"
a2enmod rewrite
sed -i "s/\(DocumentRoot\).*/\1 ${DESTDIR//\//\\\/}\/public/" /etc/apache2/sites-enabled/000-default.conf
sed -i "s/\/var\/www\//${DESTDIR//\//\\\/}\/public\//" /etc/apache2/apache2.conf
sed -i "/<Directory ${DESTDIR//\//\\\/}\/public\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/" /etc/apache2/apache2.conf

echo "Restarting apache"
systemctl restart apache2

echo "Done! You can access Monica by visiting http://localhost:8080 from your host machine"
