# Installing Monica on Debian

<img alt="Logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/Debian-OpenLogo.svg/109px-Debian-OpenLogo.svg.png" width="96" height="127" />

Monica can run on Debian Buster.

## Prerequisites

Monica depends on the following:

* A Web server, like [Apache httpd webserver](https://httpd.apache.org/) 
* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.2+
* [Composer](https://getcomposer.org/)
* MySQL / MariaDB

An editor like vim or nano should be useful too.

**Apache:** Install Apache with:

```sh
sudo apt update
sudo apt install -y apache2
```

**Git:** Install Git with:

```sh
sudo apt install -y git
```

**PHP:** 

Install PHP 7.3 with these extensions:

```sh
sudo apt install -y php php-bcmath php-gd php-gmp php-curl php-intl \
    php-mbstring php-mysql php-xml php-zip
```

**Composer:** After you're done installing PHP, you'll need the Composer dependency manager.

```sh
sudo apt install -y composer
```

**MariaDB:** Install MariaDB. Note that this only installs the package, but does not setup Mysql. This is done later in the instructions:

```sh
sudo apt install -y mariadb-server
```

## Installation steps

Once the softwares above are installed:

### 1. Clone the repository

You may install Monica by simply cloning the repository. Consider cloning the repository into any folder, example here in `/var/www/monica` directory:
```sh
cd /var/www/
sudo git clone https://github.com/monicahq/monica.git
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
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind. You'll need to configure a [mailserver](/docs/installation/mail.md) for registration & reminders to work correctly.
1. Run `composer install --no-interaction --no-suggest --no-dev --ignore-platform-reqs` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production -v` to run the migrations, seed the database and symlink folders.
1. *Optional*: Setup the queues with Redis, Beanstalk or Amazon SQS: see optional instruction of [generic installation](generic.md#setup-queues)
1. *Optional*: Setup the access tokens to use the API follow optional instruction of [generic installation](generic.md#setup-access-tokens)

### 4. Configure cron job

Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L63).
Basically those crons are needed to send reminder emails and check if a new version is available.
To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

Run the crontab command:
```sh
crontab -u www-data -e
```

Then, in the `crontab` editor window you just opened, paste the following at the end of the document:
```sh
* * * * * php /var/www/monica/artisan schedule:run
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

4. Apply the new `.conf` file and reload Apache. You can do that by running:

```sh
sudo a2dissite 000-default.conf
sudo a2ensite monica.conf
sudo systemctl reload apache2
```


### Final step

The final step is to have fun with your newly created instance, which should be up and running to `http://localhost`.
