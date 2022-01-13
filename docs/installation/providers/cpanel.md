# Installing Monica (cPanel Shared Hosting) <!-- omit in toc -->

- [Prerequisites](#prerequisites)
- [Installation steps](#installation-steps)
  - [1. Download the repository](#1-download-the-repository)
  - [2. Setup the database](#2-setup-the-database)
  - [3. Configure Monica](#3-configure-monica)
  - [4. Configure cron job](#4-configure-cron-job)
  - [5. Configure cPanel webserver](#5-configure-cpanel-webserver)
  - [Final step](#final-step)

## Prerequisites

Monica can be configured in shared hosting environments with a little differences that we can remedy easily. In this scenario, Monica depends on the following:

-   A shared cPanel Server
-   PHP 7.4+
-   [Composer](https://getcomposer.org/)
-   [MySQL](https://www.mysql.com/)
-   SSH Access for an accont on the cPanel server

**Git:** Git should come pre-installed with your server. If it doesn't - use the installation instructions in the link.

**PHP:** Install php7.4 minimum. Generally cPanel will have a PHP 7 version installed, verify under the 'PHP Version' section from the cPanel section. Make sure these extensions are enabled:

-   bcmath
-   curl
-   dom
-   gd
-   gmp
-   iconv
-   intl
-   json
-   mbstring
-   mysqli
-   opcache
-   pdo_mysql
-   redis
-   sodium
-   xml
-   zip

In most cases, this will be under the section called 'PHP Version' in cPanel where you can enable and disable modules. 

**Composer:** After you're done installing PHP, you'll need the Composer dependency manager. Generally on most capable shared hosts, this is already installed. If it is not, please reference the below:

```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer
php -r "unlink('composer-setup.php');"
```

**MySQL:** Almost every cPanel environment includes this by default, and this will be our desired DB


## Installation steps

Once the softwares above are installed:

### 1. Download the repository

You may install Monica by simply downloading the repository. You can download it by using the download button at the main repo page for Monica. Some people may want to use Git, and when you have properly logged into the cPanel server, issue the following commands:

```sh
cd /public_html/[subdomain you wish to install monica on]
git clone https://github.com/monicahq/monica.git
```

You should check out a tagged version of Monica since `main` branch may not always be stable. Find the latest official version on the [release page](https://github.com/monicahq/monica/releases).

```sh
cd /var/www/monica
# Get latest tags from GitHub
git fetch
# Clone the desired version
git checkout tags/v2.18.0
```

### 2. Setup the database

Use the cPanel database wizard to create a new database. 
<ol>
<li>Search for 'Database Wizard' in the cPanel GUI. Click on that item. </li>
<li>Create a database name and click next. </li>
<li>Create a user name and password for the user to access the database. Click Next</li>
<li>Assign All Permissions to the user account.</li>
<li>Save the password to be referenced later</li>


### 3. Configure Monica

Open the cPanel file manager and navigate to the directory in which you want to install Monica. Then run these steps:

1. Duplicate `.env.example` to a file called `.env` to create your own version of all the environment variables needed for the project to work.
2. Update `.env` to your specific needs
    - set `DB_USERNAME` and `DB_PASSWORD` with the settings used above.
    - DO NOT set a database prefix, as you will overrun the limit of table and constraint names. 
    - configure a [mailserver](/docs/installation/mail.md) for registration & reminders to work correctly. Generally you can configure a SMTP account within cPanel and be fine. 
    - set the `APP_ENV` variable to `production`, `local` is only used for the development version. Beware: setting `APP_ENV` to `production` will force HTTPS. Skip this if you're running Monica locally.
3. Log into the cPanel server via SSH and navigate to the directory in which you want to install Monica.
4. Run `composer install --no-interaction --no-dev` to install all packages.
5. Run `yarn install` to install frontend packages, then `yarn run production` to build the assets (js, css).
6. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
7. Run `php artisan setup:production -v` to run the migrations, seed the database and symlink folders.

The `setup:production` command will run migrations scripts for database, and flush all cache for config, route, and view, as an optimization process.
As the configuration of the application is cached, any update on the `.env` file will not be detected after that. You may have to run `php artisan config:cache` manually after every update of `.env` file.

### 4. Configure cron job

Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/main/app/Console/Kernel.php#L63).
Basically those crons are needed to send reminder emails and check if a new version is available.
To do this, setup a cron that runs every minute that triggers the following command `php artisan schedule:run`.

1. Navigate to 'Cron Jobs' in the cPanel GUI:


2. On that screen add the following:

Under common settings, select 'Once Per Minute'

Paste the following in the 'Command' section
```
php /var/www/monica/artisan schedule:run >> /dev/null 2>&1
```

### 5. Configure cPanel webserver

1. Navigate to the 'Subdomain' section in the cPanel GUI:


2. Update the path of the domain you wish to assign to Monica to the following:

```sh
/public_html/[subdomain you installed the monica folders on]/public
```

### Final step

The final step is to have fun with your newly created instance, which should be up and running to `http://[domain you installed Monica on]`.

From there you will be able to create an account and use the platform as normal. 
