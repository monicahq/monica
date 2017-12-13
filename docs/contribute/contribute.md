<!-- This uses the MarkdownTOC's Sublime Text plugin to autogenerate the TOC -->
<!-- MarkdownTOC autolink="true" depth="4" -->

- [Contribute as a developer][contribute-as-a-developer]
    - [Testing environment][testing-environment]
        - [Setup][setup]
        - [Run the test suite][run-the-test-suite]
    - [Backend][backend]
        - [Things to consider when adding new code][things-to-consider-when-adding-new-code]
            - [Add a new table to the database schema][add-a-new-table-to-the-database-schema]
            - [Manipulating data during a migration][manipulating-data-during-a-migration]
        - [Email testing][email-testing]
        - [Email reminders][email-reminders]
        - [Statistics][statistics]
    - [Database][database]
        - [Connecting to mySQL][connecting-to-mysql]
    - [Front-end][front-end]
        - [Mix][mix]
        - [Watching and compiling assets][watching-and-compiling-assets]
        - [CSS][css]
        - [JS and Vue][js-and-vue]

<!-- /MarkdownTOC -->

# Contribute as a developer

Are you interested in giving a hand? We can't be more excited about it. Thanks in advance! Here are some guidelines that could help you to get started quickly.

The best way to contribute to Monica is to use [Homestead](https://laravel.com/docs/5.3/homestead) as a development environment, which is an official, pre-packaged Vagrant box that provides you a wonderful development environment without requiring you to install PHP, a web server, and any other server software on your local machine. The big advantage is that it runs on any Windows, Mac, or Linux system.

This is what is used to develop Monica and what will provide a common base for everyone who wants to contribute to the project. Once Homestead is installed, you can pull the repository and start setting up Monica.

Note: the official Monica installation uses mySQL as the database system. While Laravel technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/5.5/database#configuration) on that topic if you feel adventurous.

Steps to install Monica:

1. `composer install` in the folder the repository has been cloned.
1. `cp .env.example .env`
1. Update `.env` to your specific needs.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. `npm install`.
1. Create a database called `monica` in your mySQL instance.
1. `php artisan key:generate` to generate a random APP_KEY
1. `php artisan setup:test` setup the database.
    1. By default this command will also populate the database with fake data.
    1. Use the `-- skipSeed` option to skip the process of adding fake data in your dev environment.
1. `php artisan storage:link` to access the avatars.
1. `php artisan passport:install` to create the access tokens required for the API.

If you haven't skipped the seeding of fake data, we will create two accounts by default:

* First account is `admin@admin.com` with the password `admin`. This account contains a lot of fake data that will let you play with the product. * Second account is `blank@blank.com` with the password `blank`. This account does not contain any data and shall be used to check all the blank states.

## Testing environment

We try to cover most features and new methods with unit and functional tests. Any pull request submitted on GitHub will have to go through Travis and pass before being merged. Moreover, we **strongly** encourage adding unit tests for every new method added to the codebase to ensure code stability, and we will probably refuse a pull request if there is no tests for it.

### Setup

To setup the test environment:

* Create a database called `monica_test`
* `php artisan migrate --database testing`
* `php artisan db:seed --database testing`

### Run the test suite

To run the test suite:

* `phpunit` or `./vendor/bin/phpunit`

## Backend

### Things to consider when adding new code

#### Add a new table to the database schema

If you add a new table, make sure there is a column called `account_id` in this new table. That way, we will make sure that the script responsible for resetting or deleting a user account will go take this new table into consideration while running.

#### Manipulating data during a migration

Sometimes you need to manipulate and move data around when you decide to change the database structure. In that case, as much as possible, do not use Eloquent to change the data. Use either [raw SQL queries](https://laravel.com/docs/5.5/database#running-queries) or the [Query builder](https://laravel.com/docs/5.5/queries) to do it. This is due to the fact that objects might change overtime or even be deleted, which would break the migrations entirely.

### Email testing

Emails are an important part of Monica. Emails are still the most significant mean of communication and people like receiving them when they are relevant. That being said, you will need to test emails to make sure they contain what they should contain.

For development purposes, you have two choices to test emails:

1. You can use [Mailtrap](https://mailtrap.io/). This is an amazing service that provides a free plan that is plenty enough to test all the emails that are sent. 1. If you use Homestead to code on your local machine, you can use [mailhog](https://github.com/mailhog/MailHog) that is built-in. To use it, you first need to start mailhog (`sudo service mailhog restart`). Then, head up to http://localhost:8025 in your browser to load Mailhog's UI.

If you want to use mailhog, you need the following settings in your `.env` file:

```
MAIL_DRIVER=smtp
MAIL_HOST=0.0.0.0
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
```

### Email reminders

Reminders are generated and sent using an Artisan command `monica:sendnotifications`. This command is scheduled to be triggered every hour in `app/console/Kernel.php`.

### Statistics

Monica calculates every night (ie once per day) a set of metrics to help you understand how the instance is being used by users. That will also allow to measure growth over time.

Statistics are generated by the Artisan command `monica:calculatestatistics` every night at midnight and this cron is defined in `app/console/Kernel.php`.

## Database

As said above, Monica uses mySQL by default. While Laravel supports multiple DBMS, we can't assure you it will work with any other DBMS than mySQL.

### Connecting to mySQL

If you want to connect directly to Monica's MySQL instance read [_Connecting to MySQL inside of a Docker container_](./docs/database/connecting.md).

## Front-end

### Mix

We use [mix](https://laravel.com/docs/5.5/mix) to manage the front-end and its dependencies, and also to compile and/watch the assets. **Please note that we should do our best to prevent introducing new dependencies if we can prevent it**.

Mix should be available in your development environment if you have installed Monica locally and ran `npm install` in the first place.

If you need to add a new dependency, update `package.json` to add it and make sure you commit `package-lock.json` once `package.json` is updated.

### Watching and compiling assets

CSS is written in SASS and therefore needs to be compiled before being used by the application. To compile those front-end assets, use `npm run dev`.

To monitor changes and compile assets on the fly, use `npm run watch`.

### CSS

At the current time, we are using a mix of Bootstrap 4 and [Tachyons](https://tachyons.io). We aim to use a maximum of [Atomic CSS](https://adamwathan.me/css-utility-classes-and-separation-of-concerns/) instead of having bloated, super hard to maintain CSS files. We'll get rid of Bootstrap entirely over time.

This means that we should add new CSS classes only if it's absolutely necessary.

### JS and Vue

We are using [Vue.js](https://vuejs.org/) in some parts of the application, and we'll use it more and more over time. Vue is very simple to learn and use, and with [Vue Components](https://vuejs.org/v2/guide/components.html), we can easily create isolated, reusable components in the app. If you want to add a new feature, you don't need to use Vue.js - you can use plain HTML views served by the backend. But with Vue.js, it'll be a nicer experience.
