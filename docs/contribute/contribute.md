# Contribute as a developer

The best way to contribute to Monica is to use
[Homestead](https://laravel.com/docs/5.3/homestead), which is an official,
pre-packaged Vagrant box that provides you a wonderful development environment
without requiring you to install PHP, a web server, and any other server
software on your local machine. The big advantage is that it runs on any
Windows, Mac, or Linux system.

This is what is used to develop Monica and will provide a common base for
everyone who wants to contribute to the project. Once Homestead is installed,
you can pull the repository and start setup Monica.

The official Monica installation uses mySQL as the database system. While
Laravel technically supports Postgre and other database types, we can't
guarantee that it will work fine with Monica.

1. `composer install` in the folder the repository has been cloned.
1. `cp .env.example .env`
1. Update `.env` to your specific needs.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. `npm install`.
1. Install Bower `npm install -g bower`.
1. Install Gulp `npm install --global gulp-cli`.
1. `bower install` to install front-end dependencies in the `vendor` folder.
1. Create a database called `monica` in your mySQL instance.
1. `php artisan key:generate` to generate a random APP_KEY
1. `php artisan migrate` to run all migrations and create the database structure.
1. `php artisan storage:link` to access the avatars.
1. `php artisan passport:install` to create the access tokens required for the API.
1. `php artisan db:seed --class ActivityTypesTableSeeder` to populate the
activity types.
1. `php artisan db:seed --class CountriesSeederTable` to populate the countries
table.

**Optional step**: Seeding the database with fake data

This step is to populate the instance with fake data, so you can test with real
data instead of lorem ipsum.

1. `php artisan db:seed --class FakeContentTableSeeder` to load all seeds.

Note that this will create two accounts:

* First account is `admin@admin.com` with the password `admin`. This account
contains a lot of fake data that will let you play with the product.
* Second account is `blank@blank.com` with the password `blank`. This account
does not contain any data and shall be used to check all the blank states.

### Setup the testing environment

Monica uses the testing capabilities of Laravel to do unit. While all code will
have to go through to Travis before being merged, tests can still be executed
locally before pushing them. In fact, we encourage you strongly to do it first.

To setup the test environment, create a separate testing database locally:

* Create a database called `monica_test`

Then you need to run the migrations specific to the testing database and runs
the seeders to populate it:

* `php artisan migrate --database testing`
* `php artisan db:seed --database testing`

Once this is done, you have to use `phpunit` command every time you want to run
the test suite.

Each time the schema of the database changes, you need to run again the
migrations and the seeders by running the two commands above.

If you want to connect directly to Monica's MySQL instance read [_Connecting to MySQL inside of a Docker container_](./docs/database/connecting.md).

#### Setup functional testing

We use Laravel Dusk to do functional testing. The most important is the unit
tests - but functional testing is a very nice to have and we are happy to
provide support for it. However, setting up the functional testing environment
is **really painful**. Laravel Dusk should work fine if you use standard PHP,
not in a VM (like Homestead), but I haven't tested it. If you do, please report
and update this document.

The following setup instructions are for Homestead, which we recommend to
contribute to Monica. Instructions come from [this
article](http://www.jesusamieiro.com/using-laravel-dusk-with-vagrant-homestead/).

* Setup Google Chrome Headless and XVFB in your VM

```bash
$ wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
$ sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list'
$ sudo apt-get update && sudo apt-get install -y google-chrome-stable
$ sudo apt-get install -y xvfb
```

* Start Chrome Driver in your VM. This instruction will open a port - let it
open.

```bash
$ ./vendor/laravel/dusk/bin/chromedriver-linux --port=8888
```

* Add your project in `/etc/hosts` in your vagrant machine

```bash
127.0.0.1 your-project.app
```

* Open another SSH connection to the Vagrant Homestead machine and execute the
following to run the xvfb server

```bash
$ Xvfb :0 -screen 0 1280x960x24 &
```

* On your first console, press CTLR+C and run the functional tests

```bash
php artisan dusk
```

You are done. It's horrible.

### Front-end

#### Bower

We use Bower to manage front-end dependencies. The first time you install the
project, you need to `bower install` in the root of the project. When you want
to update the dependencies, run `bower update`.

To install a new package, use `bower install jquery -S`. The `-S` option is to
update `bower.json` to lock the specific version.

All the assets are stored in `resources/vendor`.

#### Watching and compiling assets

CSS is written in SASS and therefore needs to be compiled before being used by
the application. To compile those front-end assets, use `gulp`.

To monitor changes and compile assets on the fly, use `gulp watch`.

#### Bootstrap 4

At the current time, we are using Bootstrap 4 Alpha 2. Not everything though -
we do use only what we need. I would have wanted to use something completely
custom, but why reinvent the wheel? Anyway, make sure you don't update this
dependency with Bower. If you do, make sure that everything is thoroughly tested
as when Bootstrap changes version, a lot of changes are introduced.

### Backend

#### Email testing

Emails are an important part of Monica. Emails are still the most significant mean
of communication and people like receiving them when they are relevant. That
being said, you will need to test emails to make sure they contain what they
should contain.

For development purposes, you have two choices to test emails:

1. You can use [Mailtrap](https://mailtrap.io/). This is an amazing service that
provides a free plan that is plenty enough to test all the emails that are sent.
1. If you use Homestead to code on your local machine, you can use
[mailhog](https://github.com/mailhog/MailHog) that is built-in. To use it, you
first need to start mailhog (`sudo service mailhog restart`). Then, head up to
http://localhost:8025 in your browser to load Mailhog's UI.

If you want to use mailhog, you need the following settings in your `.env` file:

```
MAIL_DRIVER=smtp
MAIL_HOST=0.0.0.0
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
```

#### Email reminders

Reminders are generated and sent using an Artisan command
`monica:sendnotifications`. This command is scheduled to be triggered every hour
in `app/console/Kernel.php`.

### Statistics

Monica calculates every night (ie once per day) a set of metrics to help you
understand how the instance is being used by users. That will also allow to
measure growth over time.

Statistics are generated by the Artisan command `monica:calculatestatistics`
every night at midnight and this cron is defined in `app/console/Kernel.php`.
