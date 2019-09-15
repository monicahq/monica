# Installing Monica on Ubuntu

<img alt="Ubuntu" src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Logo-ubuntu_cof-orange-hex.svg/120px-Logo-ubuntu_cof-orange-hex.svg.png" width="120" height="120" />

Monica can run on [Ubuntu 18.04 (Bionic Beaver)](http://releases.ubuntu.com/18.04/).

## Prerequisites

Monica depends on the following:

* [Apache httpd webserver](https://httpd.apache.org/) 
* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.3+
* [Composer](https://getcomposer.org/)
* [MySQL](https://support.rackspace.com/how-to/installing-mysql-server-on-ubuntu/)

**Apache:** If it doesn't come pre-installed with your server, follow the [instructions here](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-16-04#step-1-install-apache-and-allow-in-firewall) to setup Apache and config the firewall.

**Git:** Git should come pre-installed with your server. If it's not, install it with:

```sh
sudo apt update
sudo apt install -y git
```

**PHP 7.3+:**

First add this PPA repository:
```sh
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
```

Then install php 7.3 with these extensions:
```sh
sudo apt update
sudo apt install -y php7.3 php7.3-cli php7.3-common php7.3-fpm \
    php7.3-json php7.3-opcache php7.3-mysql php7.3-mbstring php7.3-zip \
    php7.3-bcmath php7.3-intl php7.3-xml php7.3-curl php7.3-gd php7.3-gmp
```

**Composer:** After you're done installing PHP, you'll need the [Composer](https://getcomposer.org/download/) dependency manager.

```sh
cd /tmp
curl -s https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer
rm -f composer-setup.php
```
(or you can follow instruction on [getcomposer.org](https://getcomposer.org/download/) page)

**Mysql:** Install Mysql 5.7. Note that this only installs the package, but does not setup Mysql. This is done later in the instructions:

```sh
sudo apt update
sudo apt install -y mysql-server
```

### Types of databases

The official Monica installation uses Mysql as the database system and **this is the only official system we support**. While Laravel technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/database#configuration) on that topic if you feel adventurous.

## Installation steps

Once the softwares above are installed:

### 1. Clone the repository

You may install Monica by simply cloning the repository. In order for this to work with Apache, you need to clone the repository in a specific folder:

```sh
cd /var/www
git clone https://github.com/monicahq/monica.git
```

You should check out a tagged version of Monica since `master` branch may not always be stable. Find the latest official version on the [release page](https://github.com/monicahq/monica/releases):

```sh
cd /var/www/monica
git checkout tags/v2.2.1
```

### 2. Setup the database

Log in with the root account to configure the database.
```sh
mysql -u root -p
```

Create a database called 'monica'.
```sql
CREATE DATABASE monica;
```

Create a user called 'monica' and its password 'strongpassword'.
```sql
CREATE USER 'monica'@'localhost' IDENTIFIED BY 'strongpassword';
```

We have to authorize the new user on the monica db so that he is allowed to change the database.
```sql
GRANT ALL ON monica.* TO 'monica'@'localhost';
```

And finally we apply the changes and exit the database.
```sql
FLUSH PRIVILEGES;
exit
```

### 3. Configure Monica

`cd /var/www/monica` then run these steps:

1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind. You'll need to configure a [mailserver](/docs/installation/mail.md) for registration & reminders to work correctly.
1. Run `composer install --no-interaction --no-suggest --no-dev` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production -v` to run the migrations, seed the database and symlink folders.
1. *Optional*: Setup the queues with Redis, Beanstalk or Amazon SQS: see optional instruction of [generic installation](generic.md#setup-queues)
1. *Optional*: Setup the access tokens to use the API follow optional instruction of [generic installation](generic.md#setup-access-tokens)

### 4. Configure cron job

Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33).
Basically those crons are needed to send reminder emails and check if a new version is available.
To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

Create a new `/etc/cron.d/monica` file with:
```sh
echo "* * * * * sudo -u www-data php /var/www/monica/artisan schedule:run" | sudo tee /etc/cron.d/monica
```

### 5. Configure Apache webserver

1. Give proper permissions to the project directory by running:

```sh
sudo chown -R www-data:www-data /var/www/monica
sudo chmod -R 775 /var/www/monica/storage
```

2. Enable the rewrite module of the Apache webserver:
```sh
sudo a2enmod rewrite
```

3. Configure a new monica site in apache by doing:

```sh
sudo nano /etc/apache2/sites-available/monica.conf
```

Then, in the `nano` text editor window you just opened, copy the following - swapping the `**YOUR IP ADDRESS/DOMAIN**` with your server's IP address/associated domain:

```html
<VirtualHost *:80>
    ServerName **YOUR IP ADDRESS/DOMAIN**

    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/monica/public

    <Directory /var/www/monica/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

4. Apply the new `.conf` file and restart Apache. You can do that by running:

```sh
sudo a2dissite 000-default.conf
sudo a2ensite monica.conf

# Enable php7.3 fpm, and restart apache
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php7.3-fpm
sudo service php7.3-fpm restart
sudo service apache2 restart
```

### Final step

The final step is to have fun with your newly created instance, which should be up and running to `http://localhost`.
