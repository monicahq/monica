# Running it on Debian Stretch

#### 1. Install the required packages:

```
sudo apt install apache2 mariadb-server php7.0 php7.0-mysql php7.0-xml \
    php7.0-intl php7.0-mbstring git curl
```

#### 2. Clone the repository

```
sudo git clone https://github.com/monicahq/monica.git /var/www/monica
```

#### 3. Change permissions on the new folder

```
sudo chown -R www-data:www-data /var/www/monica
```

#### 4. Install nodejs (this is needed for npm)

```
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
```
```
sudo apt-get install -y nodejs
```

#### 5. Install composer

Download and install the binary by following the [Command-line installation of composer](https://getcomposer.org/download/).

Move it to the bin directory.
```
sudo mv composer.phar /usr/local/bin/composer
```

#### 6. Setup the database

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

#### 7. Configure Monica

Run `composer install` in the folder the repository has been cloned to.
Which in our case would be /var/www/monica.

Create a copy of the example configuration file `cp .env.example .env`.

Now Update `.env` with your specific needs. If you would run the setup
with the exact commands used above your file should look like this:

```
# Two choices: local|production.
APP_ENV=local

# true if you want to show debug information on error. For production, put this
# to false.
APP_DEBUG=true

# The encryption key. This is the most important part of the application. Keep
# this secure otherwise, everyone will be able to access your application.
# Must be 32 characters long exactly.
# Use `php artisan key:generate` to generate a random key.
APP_KEY=ChangeMeBy32KeyLengthOrGenerated

# The URL of your application.
APP_URL=http://localhost

# Frequency of creation of new log files.
# Possible values: single|daily|syslog|errorlog
APP_LOG=daily

# Database information
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monicadb
DB_USERNAME=monica
DB_PASSWORD=strongpassword
DB_TEST_DATABASE=monica_test
DB_TEST_USERNAME=homestead
DB_TEST_PASSWORD=secret

# Mail credentials used to send emails from the application.
MAIL_DRIVER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=ValidEmailAddress
MAIL_FROM_NAME="Some Name"
APP_EMAIL_NEW_USERS_NOTIFICATION=EmailThatWillSendNotificationsForNewUser

# Default timezone for new users. Users can change this setting inside the
# application at their leisure.
# Must be exactly one of the timezones used in this list:
# https://github.com/monicahq/monica/blob/master/resources/views/settings/index.blade.php#L70
APP_DEFAULT_TIMEZONE=America/New_York
APP_DISABLE_SIGNUP=false

# Specific to the hosted version on .com. You probably don't need those.
GOOGLE_ANALYTICS_APP_ID=
INTERCOM_APP_ID=

# Change this only if you know what you are doing
CACHE_DRIVER=database
SESSION_DRIVER=file
QUEUE_DRIVER=sync
```

The next 5 lines are taken more or less 1:1 from the main generic installation
guide. Mainly because the author was unsure about their purpose.

Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
* `php artisan migrate` to run all migrations.
* `php artisan storage:link` to enable avatar uploads for the contacts.
* `php artisan db:seed --class ActivityTypesTableSeeder` to populate the
activity types.
* `php artisan db:seed --class CountriesSeederTable` to populate the countries
table.
* `php artisan passport:install` to generate the secure asset tokens required
for the API.

#### 8. Configure cron job

As recommended by the generic installation instructions we create a
cronjob which runs `artisan schedule:run` every minute.

For this execute this command:
```
sudo crontab -e
```

And then add this line to the bottom of the window that opens.

```
* * * * * sudo -u www-data php /var/www/html/artisan schedule:run
```

#### 9. Configure Apache webserver

We need to enable the rewrite module of the Apache webserver:

```
sudo a2enmod rewrite
```

Now look for this section in the `/etc/apache2/apache2.conf` file.

```
<Directory /var/www>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
</Directory>
```

and change it to:

```
<Directory /var/www/monica>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
</Directory>
```

Save the apache2.conf file and open `/etc/apache2/sites-enabled/000-default.conf`
and look for this line:

```
DocumentRoot /var/www/html
```
and change it to:
```
DocumentRoot /var/www/monica/public
```

After you save the 000-default.conf file you can finally restart
Apache and are up and running.
```
sudo service apache2 restart
```
