<p align="center"><img src="https://app.monicahq.com/img/small-logo.png"></p>
<h1 align="center">Monica</h1>

<p align="center">
<a href="https://travis-ci.org/monicahq/monica"><img src="https://travis-ci.org/monicahq/monica.svg?branch=master" alt="Build Status"></a>
<a href="https://github.com/djaiss/monica/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-AGPL-blue.svg" alt="License"></a>
</p>

* [Introduction](#introduction)
   * [Purpose](#purpose)
   * [Who is it for?](#who-is-it-for)
   * [What Monica isn't](#what-monica-isnt)
* [Vision, goals and strategy](#vision-goals-and-strategy)
   * [Vision](#vision)
   * [Goals](#goals)
   * [Strategy](#strategy)
   * [Values](#values)
* [Get started](#get-started)
   * [Running with Docker](#running-with-docker)
      * [Use docker-compose to run a pre-built image](#use-docker-compose-to-run-a-pre-built-image)
      * [Use docker-compose to build and run your own image](#use-docker-compose-to-build-and-run-your-own-image)
      * [Use Docker directly to run with your own database](#use-docker-directly-to-run-with-your-own-database)
   * [Setup the project on your server](#setup-the-project-on-your-server)
   * [Update your server](#update-your-server)
* [Contribute as a developer](#contribute-as-a-developer)
   * [Setup Monica](#setup-monica)
   * [Setup the testing environment](#setup-the-testing-environment)
   * [Front-end](#front-end)
      * [Bower](#bower)
      * [Watching and compiling assets](#watching-and-compiling-assets)
      * [Bootstrap 4](#bootstrap-4)
   * [Backend](#backend)
      * [Email testing](#email-testing)
      * [Email reminders](#email-reminders)
   * [Statistics](#statistics)
* [Contributing](#contributing)
   * [How the community can help](#how-the-community-can-help)
* [License](#license)

## Introduction

Monica is an open-source web application to manage your personal relationships.
Think of it as a CRM for your friends or family. This is what it currently
looks like:

![screenshot of the application](https://app.monicahq.com/img/screenshot.png)

### Purpose

Monica allows people to keep track of everything that's important about their
friends and family. Like the activities done with them. When you last called
someone. What you talked about. It will help you remember the name and the age
of the kids. It can also remind you to call someone you haven't talked to in a
while.

### Who is it for?

This project is for people who have hard time remembering details about other
people's lives - especially the ones they care about. Yes, you can still use
Facebook to achieve this, but you will only be able to see what people do and
post - and if they are not on Facebook, you are stuck anyway.

I originally built this tool to help me in my private life: I've been living
away of my own country for a long time now. I want to keep notes and remember
the life of my friends in my home country and be able to ask the relevant
questions when I email them or talk to them over the phone. Moreover, as a
foreigner in my new country, I met a lot of other foreigners - and most come
back to their countries. I still want to remember the names or ages of their
kids. Call it cheating - I call it caring.

We've already received numerous feedback of users who suffer from Asperger's
syndrome who use this application on a daily basis. It helps them have better
social interactions.

### What Monica isn't

Monica is not a social network and never will be. It's not meant to be social.
In fact, it's for your eyes only. Monica is also not a smart assistant - it
won't guess what you want to do. In fact it's pretty dumb: it will send you
emails only for the things you asked to be reminded of.

## Vision, goals and strategy

We want to use technology in a way that does not harm human relationships, like
big social networks can do.

### Vision

Monica's vision is to **help people have more meaningful relationships**.

### Goals

We want to provide a platform that is:

* **really easy to use**: we value simplicity over anything else.
* **open-source**: we believe everyone should be able to contribute to this
tool, and see for themselves that nothing nasty is done behind the scenes that
would go against the best interests of the users. We also want to leverage the
community to build attractive features and do things that would not be possible
otherwise.
* **easy to contribute to**: we want to keep the codebase as simple as possible.
This has two big advantages: anyone can contribute, and it's easily maintainable
on the long run.
* **available everywhere**: Monica should be able to run on any desktop OS
or mobile phone easily. This will be made possible by making sure the tool is
easily installable by anyone who wants to either contribute or host the platform
themselves.
* **robust API**: the platform will have a robust API so it can communicate both
ways to other systems.

### Strategy

To reach this ambitious vision, we'll use technology in a way that does not harm
human relationships, like big social networks can do.

We think Monica has to become a platform more than an application, so people can
build on it.

Here what we should do in order to realize our vision:
* Build an API in order to create an ecosystem. The ecosystem is what will make
Monica a successful platform.
* Build importers and exporters of data. We don't want to have any vendor
lock-ins. Data is the property of the users and they should be able to do
whatever they want with it.
* Create mobile apps.
* Build great reports so people can have interesting insights.
* Create a smart recommandation system for gifts. For instance, if my nephew is
soon 6 years old in a month, I will be able to receive an email with a list of
5 potential gifts I can offer to a 6 year old boy.
* Add more ways of being reminded: Telegram, SMS,...
* Create Chrome extensions to load Monica's data in a sidebar when viewing a
contact on Facebook, letting us take additional notes as we see them on Facebook.
* Add modules that can be activated on demand. One would be for instance, for
the people who wants to use Monica for dating purposes (yes, we've received this
kind of feedback already).
* Add functional and unit tests so the main features are tested. Stability is
key.

## Get started

We provide a hosted version of this application on https://monicahq.com.

If you prefer to, you can simply clone the repository and set it up yourself on
any hosting provider, for free. I'm just asking that you don't try to make
money out of it yourself.

To update your own instance, follow the instructions below.

### Running with Docker

You can use [Docker](https://www.docker.com) and
[docker-compose](https://docs.docker.com/compose/) to pull or build
and run a Monica image, complete with a self-contained MySQL database.
This has the nice properties that you don't have to install lots of
software directly onto your system, and you can be up and running
quickly with a known working environment.

Before you start, you need to get and edit a `.env` file. If you've already
cloned the [Monica Git repo](https://github.com/monicahq/monica), run:

`$ cp .env.example .env`

to create it. If not, you can fetch it from GitHub like:

`$ curl https://raw.githubusercontent.com/monicahq/monica/master/.env.example > .env`

Then open `.env` in an editor and update it for your own needs:

- Set `APP_KEY` to a random 32-character string. For example, if you
  have the `pwgen` utility installed, you could copy and paste the
  output of `pwgen -s 32 1`.
- Edit the `MAIL_*` settings to point to your own mailserver.

Now select one of these methods to be up and running quickly:

#### Use docker-compose to run a pre-built image

This is the easiest and fastest way to try MonicaHQ! Use this process
if you want to download the newest image from Docker Hub and run it
with a pre-packaged MySQL database.

Edit `.env` again to set `DB_HOST=mysql` (as `mysql` is the creative name of
the MySQL container).

```shell
$ docker-compose pull
$ docker-compose up
```

#### Use docker-compose to build and run your own image

Use this process if you want to modify Monica source code and build
your image to run.

Edit `.env` again to set `DB_HOST=mysql` (as `mysql` is the creative name of
the MySQL container).

Then run:

```shell
$ docker-compose build
$ docker-compose up
```

#### Use Docker directly to run with your own database

Use this process if you're a developer and want complete control over
your Monica container.

Edit `.env` again to set the `DB_*` variables to match your
database. Then run:

```shell
$ docker build -t monicahq/monicahq .
$ docker run --env-file .env -p 80:80 monicahq/monicahq    # to run MonicaHQ
# ...or...
$ docker run --env-file .env -it monicahq/monicahq shell   # to get a prompt
```

Note that uploaded files, like avatars, will disappear when you
restart the container. Map a volume to
`/var/www/monica/storage/app/public` if you want that data to persist
between runs. See `docker-compose.yml` for examples.

### Setup the project on your server

If you don't want to use Docker, the best way to setup the project is to use the
same configuration that [Homestead](https://laravel.com/docs/5.3/homestead)
uses. Basically, Monica depends on the following:

* PHP 7.0+
* MySQL, SQLite or Postgre
* Git
* Composer
* Optional: Redis or Beanstalk

The preferred OS distribution is Ubuntu 16.04, simply because all the
development is made on it and we know it works. However, any OS that lets you
install the above packages should work.

Once the softwares above are installed, clone the repository and proceed as
follow:

1. `composer install` in the folder the repository has been cloned.
1. `cp .env.example .env` to configure Monica.
1. Update `.env` with your specific needs.
1. Create a database called `monica`.
1. `php artisan migrate` to run all migrations.
1. `php artisan storage:link` to enable avatar uploads for the contacts.
1. `php artisan db:seed --class ActivityTypesTableSeeder` to populate the
activity types.
1. `php artisan db:seed --class CountriesSeederTable` to populate the countries
table.
1. In order for the reminders to be sent (reminders are created inside the
  application and associated to contacts), you need to setup a cron that runs
  every minute with the following command `php artisan schedule:run`.

**Optional**: Setup the queues with Redis, Beanstalk or Amazon SQS

Monica can work with a queue mechanism to handle different events, so we don't
block the main thread while processing stuff that can be run asynchronously,
like sending emails. By default, Monica does not use a queue mechanism but can
be setup to do so.

There are three choices for the queue mechanism:
* Database (this will use the database used by the application to act as a queue)
* Redis
* Beanstalk
* Amazon SQS

The simplest queue is the database driver. To set it up, simply change in your
`.env` file the following `QUEUE_DRIVER=sync` by `QUEUE_DRIVER=database`.

To configure the other queues, refer to the
[official Laravel documentation](https://laravel.com/docs/5.4/queues#driver-prerequisites)
on the topic.

### Update your server

There is no concept of releases at the moment. If you run the project locally,
or if you have installed Monica on your own server, you need to follow these
steps below to update it, **every single time**, or you will run into problems.

```
git pull origin master
composer update
php artisan migrate
```

That should be it.

## Contribute as a developer

You want to help build Monica? That's awesome. We can't thank you enough.

### Setup Monica

The best way to contribute to Monica is to use
[Homestead](https://laravel.com/docs/5.3/homestead), which is an official,
pre-packaged Vagrant box that provides you a wonderful development environment
without requiring you to install PHP, a web server, and any other server
software on your local machine. The big advantage is that it runs on any
Windows, Mac, or Linux system.

This is what is used to develop Monica and will provide a common base for
everyone who wants to contribute to the project. Once Homestead is installed,
you can pull the repository and start setup Monica.

1. `composer install` in the folder the repository has been cloned.
1. `cp .env.example .env`
1. Update `.env` to your specific needs.
1. `npm install` to install bower and gulp.
1. `bower install` to install front-end dependencies in the `vendor` folder.
1. Create a database called `monica`.
1. `php artisan migrate` to run all migrations.
1. `php artisan storage:link` to access the avatars.
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

Monica uses the testing capabilities of Laravel to do unit and functional
testing. While all code will have to go through to Travis before being merged,
tests can still be executed locally before pushing them. In fact, we encourage
you strongly to do it first.

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

Emails are an important of Monica. Emails are still the most significant mean
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

## Contributing

We welcome contributions of all kinds from anyone. We do however have rules.

* Monica is written with a great framework, Laravel. We care deeply about
keeping Monica very simple on purpose. The simpler the code is, the simpler it
will be to maintain it and debug it when needed. That means we don't want to
make it a one page application, or add any kind of complexities whatsoever.
* That means we won't accept pull requests that add too much complexity, or
written in a way we don't understand. Again, the number 1 priority should be to
simplify the maintenance on the long run.
* It's better to move forward fast by shipping good features, than waiting for
months and ship a perfect feature.
* Our product philosophy is simple. Things do not have to be perfect. They just
need to be shipped. As long as it works and aligns with the vision, you should
ship as soon as possible. Even if it's ugly, or very small, that does not
matter.

### How the community can help

I'm looking for people willing to write tests for the existing features.

## License

Copyright (c) 2016-2017 Regis Freyd

Licensed under the AGPL License
