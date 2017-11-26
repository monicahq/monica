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

### Front-end

#### Mix

We use [mix](https://laravel.com/docs/5.5/mix) to manage the front-end and its
dependencies, and also to compile and/watch the assets. Please note that we
should do our best to not introduce new dependencies if we can prevent it.

Mix should be available if you have installed Monica locally and ran
`npm install` in the first place.

If you need to add a new dependency (make sure it's absolutely necessary first),
update `package.json` to add it. Also, make sure you commit `package-lock.json`
once `package.json` is updated.

#### Watching and compiling assets

CSS is written in SASS and therefore needs to be compiled before being used by
the application. To compile those front-end assets, use `npm run dev`.

To monitor changes and compile assets on the fly, use `npm run watch`.

#### CSS

At the current time, we are using a mix of Bootstrap 4 and [Tachyons](https://tachyons.io).
We aim to use a maximum of [Atomic CSS](https://adamwathan.me/css-utility-classes-and-separation-of-concerns/)
in place of having bloated, super hard to maintain CSS files. This is why,
over time, we'll get rid of Bootstrap entirely.

#### JS and Vue

We are also using [Vue.js](https://vuejs.org/) in some places of the
application, and we'll use it more and more over time. Vue is very simple to
learn and use, and with [Vue Components](https://vuejs.org/v2/guide/components.html),
we can easily create isolated, reusable components in the app. If you want to
add a new feature, you don't need to use Vue.js - you can use plain HTML views
served by the backend. But with Vue.js, it'll be a nicer experience.

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
