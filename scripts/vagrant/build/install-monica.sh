#!/usr/bin/env bash

set -euo pipefail

MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-changeme}
MYSQL_DB_DATABASE=${MYSQL_DB_DATABASE:-monica}
MYSQL_DB_USERNAME=${MYSQL_DB_USERNAME:-monica}
MYSQL_DB_PASSWORD=${MYSQL_DB_PASSWORD:-changeme}
DESTDIR=/var/www/html/monica

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

export DEBIAN_FRONTEND=noninteractive

apt-get update >/dev/null

echo -e "\033[1;32m########################\033[0;40m"
echo -e "\033[1;32mInstalling Monica ${GIT_TAG:-}\033[0;40m"
echo -e "\033[1;32m########################\033[0;40m"

echo -e "\n\033[4;32mInstalling apache\033[0;40m"
apt-get install -y apache2 >/dev/null

echo "ServerName vagrant" >> /etc/apache2/apache2.conf # suppress apache warning

echo -e "\n\033[4;32mInstalling MySQL with default root password\033[0;40m"
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
apt-get install -y mysql-server mysql-client >/dev/null

echo -e "\n\033[4;32mInstalling PHP 7.2\033[0;40m"
#apt install -y curl gnupg2 apt-transport-https apt-transport-https lsb-release ca-certificates
#curl -s https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/php.gpg 
#echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
apt-get update >/dev/null
apt-get install -y php7.2 >/dev/null

echo -e "\n\033[4;32mInstalling git\033[0;40m"
apt-get install -y git >/dev/null

echo -e "\n\033[4;32mInstalling composer\033[0;40m"
apt-get install -y curl php7.2-cli >/dev/null
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo -e "\n\033[4;32mInstalling packages for Monica\033[0;40m"
apt-get install -y php7.2-common php7.2-fpm \
    php7.2-json php7.2-opcache php7.2-mysql php7.2-mbstring php7.2-zip \
    php7.2-bcmath php7.2-intl php7.2-xml php7.2-curl php7.2-gd php7.2-gmp >/dev/null

echo -e "\n\033[4;32mGetting database ready\033[0;40m"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_DB_DATABASE;
CREATE USER '$MYSQL_DB_USERNAME'@'localhost' IDENTIFIED BY '$MYSQL_DB_PASSWORD';
GRANT ALL ON $MYSQL_DB_DATABASE.* TO '$MYSQL_DB_USERNAME'@'localhost';
FLUSH PRIVILEGES;"

echo -e "\n\033[4;32mInstalling Monica\033[0;40m"
git clone https://github.com/monicahq/monica.git $DESTDIR
cd $DESTDIR
if [ -n "${GIT_TAG:-}" ]; then
  git checkout tags/$GIT_TAG
fi
composer install --no-interaction --no-suggest --no-dev --no-progress  >/dev/null
composer clear-cache

echo -e "\n\033[4;32mConfiguring Monica\033[0;40m"
cp .env.example .env
update_setting .env DB_DATABASE "$MYSQL_DB_DATABASE"
update_setting .env DB_USERNAME "$MYSQL_DB_USERNAME"
update_setting .env DB_PASSWORD "$MYSQL_DB_PASSWORD"
update_setting .env APP_DISABLE_SIGNUP "false"
chown -R www-data:www-data .

echo -e "\n\033[4;32mConfiguring cron script\033[0;40m"
{ crontab -l -u www-data; echo "* * * * * /usr/bin/php $DESTDIR/artisan schedule:run"; } | crontab -u www-data - || true

echo -e "\n\033[4;32mConfiguring apache\033[0;40m"
a2enmod rewrite
sed -i "s/\(DocumentRoot\).*/\1 ${DESTDIR//\//\\\/}\/public/" /etc/apache2/sites-enabled/000-default.conf
sed -i "s/\/var\/www\//${DESTDIR//\//\\\/}\/public\//" /etc/apache2/apache2.conf
sed -i "/<Directory ${DESTDIR//\//\\\/}\/public\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/" /etc/apache2/apache2.conf
systemctl restart apache2

echo -e "\n\033[4;32mSystem update\033[0;40m"
apt-get -y upgrade
apt-get -y autoremove
apt-get -y clean
