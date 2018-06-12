# Installing Monica on Ubuntu

<img alt="Ubuntu" src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Logo-ubuntu_cof-orange-hex.svg/120px-Logo-ubuntu_cof-orange-hex.svg.png" width="120" height="120" />

The official Monica installation uses mySQL as the database system and **this is the only official system we support**. While Laravel (our PHP framework) technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/5.5/database#configuration) on that topic if you feel adventurous.

## Installation steps

### 0. Requirements

Monica depends on the following:

* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.1+
* [Composer](https://getcomposer.org/)
* [MySQL](https://support.rackspace.com/how-to/installing-mysql-server-on-ubuntu/)
* Optional: Redis or Beanstalk

**Git:** Git should come pre-installed with your server. If it doesn't - use the installation instructions in the link.

**PHP 7.1+:** The required version of PHP for Monica is included in the default `apt` package repository at the moment. One way to overcome this is to add a new PPA and pull the required version of PHP (along with all the neccessary modules) from there:

    sudo add-apt-repository ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install php7.1 php7.1-cli php7.1-common php7.1-json php7.1-opcache php7.1-mysql php7.1-mbstring php7.1-mcrypt php7.1-zip php7.1-fpm php7.1-bcmath php7.1-intl php7.1-simplexml php7.1-dom php7.1-curl php7.1-gd

**Composer:** After you're done installing PHP, you'll need the Composer dependency manager. It is not enough to just install Composer, you also need to make sure it is installed globally for Monica's installation to run smoothly:

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer

**Mysql:** Note that this only installs the package, but does not setup MySQL. This is done later in the instructions:

    sudo apt-get update
    sudo apt-get install mysql-server

The preferred OS distribution is Ubuntu 16.04, simply because all the development is made on it and we know it works. However, any OS that lets you install the above packages should work.

### 1. Clone the repository

You may install Monica by simply closing the repository. In order for this to work with Apache, which is often pre-pacakged with many common linux instances ([DigitalOcean](https://www.digitalocean.com/) droplets are one example), you need to clone the repository in a specific folder:

```sh
cd /var/www/html
git clone https://github.com/monicahq/monica.git
```

You should check out a tagged version of Monica since `master` branch may not always be stable. Find the latest official version on the [release page](https://github.com/monicahq/monica/releases):

```sh
cd /var/www/html/monica
git checkout tags/v2.2.1
```

### 2. Setup the database

Log in with the root account to configure the database.
```sh
mysql -uroot -p
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

`cd /var/www/html/monica` then run these steps:

1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind.
1. Run `composer install --no-interaction --no-suggest --no-dev` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production` to run the migrations, seed the database and symlink folders.
1. Optional: run `php artisan passport:install` to create the access tokens required for the API (Optional).
1. Finally, Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33). Basically those crons are needed to send reminder emails and check if a new version is available. To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

### 4. Configure Apache

`cd /var/www/html` then follow these steps:

1. Give proper permissions to the project directory by running:

```sh
sudo chgrp -R www-data /monica
sudo chmod -R 775 /monica/storage
```
2. Configure our new monica installation in apache by doing:

```sh
cd /etc/apache2/sites-available
sudo nano laravel.conf
```

3. Then, in the `nano` text editor window you just opened, copy the following - swapping the `YOUR IP ADDRESS/DOMAIN` with your server's IP address/associated domain:

```html
<VirtualHost *:80>
    ServerName YOUR IP ADDRESS/DOMAIN

    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/monica/public

    <Directory /var/www/html/monica>
        AllowOverride All
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```


4. Now we need to wrap up by applying the new `.conf` file and restarting Apache. You can do that by running:

```sh
sudo a2dissite 000-default.conf
sudo a2ensite laravel.conf
sudo a2enmod rewrite
sudo service apache2 restart
```

### 5. **Optional**: Setup the queues with Redis, Beanstalk or Amazon SQS

Monica can work with a queue mechanism to handle different events, so we don't block the main thread while processing stuff that can be run asynchronously, like sending emails. By default, Monica does not use a queue mechanism but can be setup to do so.

We recommend that you do not use a queue mechanism as it complexifies the overall system and can make debugging harder when things go wrong.

This is why we suggest to use `QUEUE_DRIVER=sync` in your .env file. This will bypass the queues entirely and will process requests as they come. In practice, unless you have thousands of users, you don't need to use an asynchronous queue.

That being said, if you still want to make your life more complicated, here is what you can do.

There are several choices for the queue mechanism:
* Database (this will use the database used by the application to act as a queue)
* Redis
* Beanstalk
* Amazon SQS

The simplest queue is the database driver. To set it up, simply change in your `.env` file the following `QUEUE_DRIVER=sync` by `QUEUE_DRIVER=database`.

To configure the other queues, refer to the [official Laravel documentation](https://laravel.com/docs/master/queues#driver-prerequisites) on the topic.

### Final step

The final step is to have fun with your newly created instance.
