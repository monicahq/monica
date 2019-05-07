# Installing Monica on Debian

<img alt="Logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Debian-OpenLogo.svg/109px-Debian-OpenLogo.svg.png" width="96" height="127" />

Monica can run on Debian Stretch.

## Prerequisites

1. Install the required packages:

```sh
sudo apt install apache2 mariadb-server php7.1 php7.1-cli php7.1-common \
    php7.1-json php7.1-opcache php7.1-mysql php7.1-mbstring php7.1-mcrypt \
    php7.1-zip php7.1-fpm php7.1-bcmath php7.1-intl php7.1-simplexml \
    php7.1-dom php7.1-curl php7.1-gd php7.1-gmp git curl
```

2. Install composer

Download and install the binary by following the [Command-line installation of composer](https://getcomposer.org/download/).

Move it to the bin directory.
```sh
sudo mv composer.phar /usr/local/bin/composer
```

## Installation steps

Once the softwares above are installed:

### 1. Clone the repository

You may install Monica by simply cloning the repository. Consider cloning the repository into any folder, example here in `/var/www/monica` directory:
```sh
sudo git clone https://github.com/monicahq/monica.git /var/www/monica
```

You should check out a tagged version of Monica since `master` branch may not always be stable.
Find the latest official version on the [release page](https://github.com/monicahq/monica/releases)
```sh
cd /var/www/monica
# Clone the desired version
sudo git checkout tags/v1.6.2
```

### 2. Setup the database

First make the database a bit more secure.
```sh
sudo mysql_secure_installation
```

Next log in with the root account to configure the database.
```sh
sudo mysql -uroot -p
```

Create a database called 'monica'.
```sql
CREATE DATABASE monica;
```

Create a user called 'monica' and its password 'strongpassword'.
```sql
CREATE USER 'monica'@'localhost' IDENTIFIED BY 'strongpassword';
```

We have to authorize the new user on the `monica` db so that he is allowed to change the database.
```sql
GRANT ALL ON monica.* TO 'monica'@'localhost';
```

And finally we apply the changes and exit the database.
```sql
FLUSH PRIVILEGES;
exit
```

### 3. Configure Monica

`cd /var/www/monica` then run these steps with `sudo`:

1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind.
1. Run `composer install --no-interaction --no-suggest --no-dev --ignore-platform-reqs` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production` to run the migrations, seed the database and symlink folders.
1. Optional: run `php artisan passport:install` to create the access tokens required for the API (Optional).

### 4. Configure cron job

Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33).
Basically those crons are needed to send reminder emails and check if a new version is available.
To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

1. Open the crontab edit:
```sh
sudo crontab -e
```
2. Then, in the text editor window you just opened, copy the following:
```
* * * * * sudo -u www-data php /var/www/monica/artisan schedule:run
```

### 5. Configure Apache webserver

Give proper permissions to the project directory by running
```sh
sudo chown -R www-data:www-data /var/www/monica
```

Enable the rewrite module of the Apache webserver:
```sh
sudo a2enmod rewrite
```

Edit `/etc/apache2/sites-enabled/000-default.conf` file.

* Update `DocumentRoot` property to:
```
DocumentRoot /var/www/monica/public
```
* and add a new `Directory` directive:
```
<Directory /var/www/monica/public>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Finally restart Apache.
```sh
sudo systemctl restart apache2
```


### Final step

The final step is to have fun with your newly created instance, which should be up and running to `http://localhost`.
