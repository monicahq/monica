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

Run `composer install` in the folder the repository has been cloned to. Which in our case would be /var/www/monica.

Create a copy of the example configuration file `cp .env.example .env`.

Now update `.env` with your specific needs. If you would run the setup with the exact commands used above your file should look like this:

```
# Two choices: local|production. Use local if you want to install Monica as a
# development version. Use production otherwise.
APP_ENV=local

# true if you want to show debug information on errors. For production, put this
# to false.
APP_DEBUG=false

# The encryption key. This is the most important part of the application. Keep
# this secure otherwise, everyone will be able to access your application.
# Must be 32 characters long exactly.
# Use `php artisan key:generate` to generate a random key.
APP_KEY=ChangeMeBy32KeyLengthOrGenerated

# The URL of your application.
APP_URL=http://localhost

# Database information
# To keep this information secure, we urge you to change the default password
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monica
DB_USERNAME=homestead
DB_PASSWORD=secret
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
APP_DEFAULT_TIMEZONE=US/Eastern

# Ability to disable signups on your instance.
# Can be true or false. Default to false.
APP_DISABLE_SIGNUP=true

# Frequency of creation of new log files. Logs are written when an error occurs.
# Possible values: single|daily|syslog|errorlog
APP_LOG=daily

# Specific to the hosted version on .com. You probably don't need those.
# Let them empty if you don't need them.
GOOGLE_ANALYTICS_APP_ID=
INTERCOM_APP_ID=

# Error tracking. Specific to hosted version on .com. You probably don't need
# those.
SENTRY_SUPPORT=false
SENTRY_DSN=

# Send a daily ping to https://version.monicahq.com to check if a new version
# is available. When a new version is detected, you will have a message in the
# UI, as well as the release notes for the new changes. Can be true or false.
# Default to true.
CHECK_VERSION=true

# Have access to paid features available on https://monicahq.com, for free.
# Can be true or false. Default to false.
# If set to true, that means your users will have to pay to access the paid
# features. We use Stripe to do this.
REQUIRES_SUBSCRIPTION=false

# ONLY NECESSARY IF MONICA REQUIRES A SUBSCRIPTION TO WORK
# Leave blank unless you know what you are doing.
STRIPE_KEY=
STRIPE_SECRET=
paid_plan_monthly_friendly_name=
paid_plan_monthly_id=
paid_plan_monthly_price=

# Change this only if you know what you are doing
CACHE_DRIVER=database
SESSION_DRIVER=file
QUEUE_DRIVER=sync

# Default filesystem to store uploaded files.
# Possible values: public|s3
DEFAULT_FILESYSTEM=public

# AWS keys for S3 when using this storage method
AWS_KEY=
AWS_SECRET=
AWS_REGION=us-east-1
AWS_BUCKET=
AWS_SERVER=
```

Then run the following instructions:
* Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
* Open the file `.env` to set the different variables needed by the project. The file comes with predefined values - you won't have to change most of them.
* Run `php artisan setup:production` to run the migrations, seed the database and symlink folders.
* Optional: run `php artisan passport:install` to generate the secure asset tokens required for the API. If you don't plan to use the API, don't do this step.

#### 8. Configure cron job

As recommended by the generic installation instructions we create a cronjob which runs `artisan schedule:run` every minute.

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

Save the apache2.conf file and open `/etc/apache2/sites-enabled/000-default.conf` and look for this line:

```
DocumentRoot /var/www/html
```
and change it to:
```
DocumentRoot /var/www/monica/public
```

After you save the 000-default.conf file you can finally restart Apache and are up and running.
```
sudo service apache2 restart
```
