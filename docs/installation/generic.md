# Installing Monica (Generic)

## Prerequisites

If you don't want to use Docker, the best way to setup the project is to use the same configuration that [Homestead](https://laravel.com/docs/homestead) uses. Basically, Monica depends on the following:

* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.1+
* [Composer](https://getcomposer.org/)
* [MySQL](https://www.mysql.com/)
* Optional: Redis or Beanstalk

**Git:** Git should come pre-installed with your server. If it doesn't - use the installation instructions in the link.

**PHP 7.1+:** Install php7.2 minimum, with these extensions:

- json
- iconv
- intl
- opcache
- mbstring
- xml
- mysqli
- pdo_mysql
- bcmath
- curl
- gmp
- zip
- gd

**Composer:** After you're done installing PHP, you'll need the Composer dependency manager. It is not enough to just install Composer, you also need to make sure it is installed globally for Monica's installation to run smoothly:

```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer
php -r "unlink('composer-setup.php');"
```

**Mysql:** Install Mysql 5.7+


### Types of databases

The official Monica installation uses mySQL as the database system and **this is the only official system we support**. While Laravel technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/database#configuration) on that topic if you feel adventurous.

## Installation steps

Once the softwares above are installed:

### 1. Clone the repository

You may install Monica by simply cloning the repository. In order for this to work with Apache, which is often pre-packaged with many common linux instances ([DigitalOcean](https://www.digitalocean.com/) droplets are one example), you need to clone the repository in a specific folder:

```sh
cd /var/www
git clone https://github.com/monicahq/monica.git
```

You should check out a tagged version of Monica since `master` branch may not always be stable. Find the latest official version on the [release page](https://github.com/monicahq/monica/releases).

```sh
cd /var/www/monica
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

`cd /var/www/monica` then run these steps:

1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind.
1. Run `composer install --no-interaction --no-suggest --no-dev` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production -v` to run the migrations, seed the database and symlink folders.

The `setup:production` command will run migrations scripts for database, and flush all cache for config, route, and view, as an optimization process.
As the configuration of the application is cached, any update on the `.env` file will not be detected after that. You may have to run `php artisan config:cache` manually after every update of `.env` file.

### 4. Configure cron job

Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L63).
Basically those crons are needed to send reminder emails and check if a new version is available.
To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

1. Open crontab edit for the apache user:
```sh
crontab -u www-data -e
```
2. Then, in the text editor window you just opened, copy the following:
```
* * * * *   /usr/bin/php /var/www/monica/artisan schedule:run
```

### 5. Configure Apache webserver

1. Give proper permissions to the project directory by running:

```sh
chgrp -R www-data /var/www/monica
chmod -R 775 /var/www/monica/storage
```

2. Enable the rewrite module of the Apache webserver:
```sh
a2enmod rewrite
```

2. Configure a new monica site in apache by doing:

```sh
nano /etc/apache2/sites-available/monica.conf
```

3. Then, in the `nano` text editor window you just opened, copy the following - swapping the `YOUR IP ADDRESS/DOMAIN` with your server's IP address/associated domain:

```html
<VirtualHost *:80>
    ServerName YOUR IP ADDRESS/DOMAIN

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
a2dissite 000-default.conf
a2ensite monica.conf
service apache2 restart
```

<a id="setup-queues"></a>
### 6. Optional: Setup the queues with Redis, Beanstalk or Amazon SQS

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

After configuring the queue, you'll have to run the queue worker, as described in the [Laravel documentation](https://laravel.com/docs/master/queues#running-the-queue-worker).

```sh
php artisan queue:work --sleep=3 --tries=3
```

Some process monitor such as [Supervisor](https://laravel.com/docs/master/queues#supervisor-configuration) could be useful to monitor the queue worker.


<a id="setup-access-tokens"></a>
### 7. Optional: Setup the access tokens to use the API

In order to use the Monica API for your instance, you will have to instanciate encryption keys first.

#### Generate the encryption keys
Run this command:

```sh
php artisan passport:keys
php artisan passport:client --personal --no-interaction
```

This command will generate encryption keys in the `storage` directory.
Be sure to backup the `oauth-private.key` and `oauth-public.key` files to maintain futur access.

#### Optional: Save the encryption keys as variable
Instead of keeping the encryption keys as files, you can add them as environment variable. This is very useful for any environment where you cannot deploy these file in each server (heroku, fortrabbit, etc.).

* Output the private key:

```sh
sed ':a;N;$!ba;s/\n/\\n/g' storage/oauth-private.key
```
   Copy the output to an environment variable called `PASSPORT_PRIVATE_KEY` in your `.env` file

* Do the same thing with the contents of the public key:
```sh
sed ':a;N;$!ba;s/\n/\\n/g' storage/oauth-public.key
```
   Copy ths output to an environment variable called `PASSPORT_PUBLIC_KEY` in your `.env` file


#### Optional: Generate a Password grant client
A [password grant client](https://laravel.com/docs/master/passport#creating-a-password-grant-client) can be generated in order to use the OAuth access (used in the mobile application for instance).

* Run this command to generate a password grant client:
```sh
php artisan passport:client --password --no-interaction
```

* This will display a client ID and secret:
```
Password grant client created successfully.
Client ID: 5
Client secret: zsfOHGnEbadlBP8kLsjOV8hMpHAxb0oAhenfmSqq
```

* Copy the two values into two new environment variables of your `.env` file:
   - The value of `Client ID` in a `MOBILE_CLIENT_ID` variable
   - The value of `Client secret` in a `MOBILE_CLIENT_SECRET` variable

* OAuth login can be access on `http://localhost/oauth/login`.


### Final step

The final step is to have fun with your newly created instance, which should be up and running to `http://localhost`.
