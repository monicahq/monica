# Running it on Debian Stretch

1. Install the required packages:

```
sudo apt install apache2 mariadb php7.0 php7.0-mysql php7.0-xml \
    php7.0-intl php7.0-mbstring git curl
```

2. Clone the repository
```
sudo git clone https://github.com/monicahq/monica.git /var/www/
```

3. Change permissions on the new folder
```
sudo chown -R www-data:www-data /var/www/monica
```

4. Install nodejs (this is needed for npm)
```
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
```
```
sudo apt-get install -y nodejs
```

5. Install composer

```
sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
sudo php composer-setup.php
sudo php -r "unlink('composer-setup.php');"
```

The first set of commands downloaded the binary and this command
renames it moves it to the bin directory.
```
sudo mv composer.phar /usr/local/bin/composer
```

6. Setup the database

First we're making the database a bit more secure.
```
sudo mysql_secure_installation
```

Next log in with the root account to configure the database.
```
sudo mysql -uroot -p
```

This command creates a database called monicadb
```
create database monicadb;
```

while this commands creates a database user called monica and its
password 'strongpassword'.

```
CREATE USER 'monica'@'localhost' IDENTIFIED BY 'strongpassword';
```

We have to authorize the new user on the monicadb so that he is allowed to
change the database.

```
GRANT ALL ON monicadb.* TO 'monica'@'localhost';
```

And finally we apply the changes and exit the database.
```
FLUSH PRIVILEGES;
exit
```

7. Configure Monica


Run `composer install` in the folder the repository has been cloned to.
Which in our case would be /var/www/monica.

Create a copy of the example configuration file `cp .env.example .env`.

Now Update `.env` with your specific needs. If you would run the setup
with the exact commands used above your file should look like this:

```
APP_ENV=local
APP_DEBUG=true
APP_KEY=SomeRandomString
APP_URL=http://localhost
APP_LOG=daily
APP_EMAIL_NEW_USERS_NOTIFICATION=EmailThatWillSendNotificationsForNewUser
APP_DISABLE_SIGNUP=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monicadb
DB_USERNAME=monica
DB_PASSWORD=strongpassword
DB_TEST_DATABASE=monica_test
DB_TEST_USERNAME=homestead
DB_TEST_PASSWORD=secret

CACHE_DRIVER=database
SESSION_DRIVER=file
QUEUE_DRIVER=database

MAIL_DRIVER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=ValidEmailAddress
MAIL_FROM_NAME="Some Name"

GOOGLE_ANALYTICS_APP_ID=
INTERCOM_APP_ID=
```

Make sure that you change the APP_KEY variable to something witth length of
32 characters otherwise the next step will fail.

The next 5 lines are taken more or less 1:1 from the main generic installation
guide. Mainly because the author was unsure about their purpose.

Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
`php artisan migrate` to run all migrations.
`php artisan storage:link` to enable avatar uploads for the contacts.
`php artisan db:seed --class ActivityTypesTableSeeder` to populate the
activity types.
`php artisan db:seed --class CountriesSeederTable` to populate the countries
table.

8. Configure cron job

as recommended by the generic installation instructions we create a
cronjob which runs `artisan schedule:run` every minute.
For this execute this command:
```
sudo crontab -e
```

And then add this line to the bottom of the window that opens.
```
* * * * * sudo -u www-data php /var/www/html/artisan schedule:run
```

9. Configure Websever

We need to enable the rewrite module of the Apache webserver:

```
sudo a2enmod rewrite
```

And since we currently only have the default webroot we need to remove it
and symlink monica's public folder to `/var/www/html`
```
sudo rm -r /var/www/html
```
```
sudo ln -s /var/www/monica/public /var/www/html
```

Finally we can restart Apache and are up and running.
```
sudo service apache2 restart
```
