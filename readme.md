<p align="center"><img src="https://app.monicahq.com/img/small-logo.png"></p>
<h1 align="center">Monica</h1>

<p align="center">
<a href="https://travis-ci.org/monicahq/monica"><img src="https://travis-ci.org/monicahq/monica.svg?branch=master" alt="Build Status"></a>
<a href="https://github.com/djaiss/monica/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-AGPL-blue.svg" alt="License"></a>
</p>

## Introduction

Monica is an open-source web application to manage your personal relationships.
Think of it as a CRM for your friends or family. This is what it currently
looks like:

![screenshot of the application](https://app.monicahq.com/img/screenshot.png)

### Purpose

Monica allows people to keep track of everything that's important about their
friends and family. Like the activities done with them.
When you last called someone. What you talked about. It will help you remember
the name and the age of the kids. It can also remind you to call someone you
haven't talked to in a while.

### What Monica isn't

Monica is not a social network. It's not meant to be social. In fact, it's for
your eyes only. Monica is also not a smart assistant - it won't guess what you
want to do. In fact it's pretty dumb: it will send you emails only for the things
you asked to be reminded of.

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

### The 5 years vision

Monica is the simplest, yet most complete, open-source tool to manage your
personal relationships. It's available on any platform, is easy to contribute to
and has a robust API to talk to other systems.

## Get started

We provide a hosted version of this application on https://monicahq.com.

If you prefer to, you can simply clone the repository and set it up yourself on
any hosting provider, for free. I'm just asking that you don't try to make
money out of it yourself.

To update your own instance, follow the instructions below.

## Setup the project on your server or locally

The best way to setup the project locally is to use [Homestead](https://laravel.com/docs/5.3/homestead).
This is what is used to develop Monica and will provide a common base for
everyone who wants to contribute to the project. Once Homestead is installed,
you can pull the repository and start setup Monica.

1. `composer install`
1. `cp .env.example .env` to configure Monica
1. `npm install` to install bower and gulp.
1. `bower install` to install front-end dependencies in the `vendor` folder.
1. Create 2 databases: `monica` and `monica_test`
1. `php artisan migrate` to run all migrations
1. `php artisan storage:link` to access the avatars.

Optional step:

This step is to populate the instance with fake data, so you can test with real
data instead of lorem ipsum.

1. `php artisan db:seed` to load all seeds.

Note that the seeders will create two accounts.

* First account is `admin@admin.com` with the password `admin`. This account
contains a lot of fake data that will let you play with the product.
* Second account is `blank@blank.com` with the password `blank`. This account
does not contain any data and shall be used to check all the blank states.

### Setup the testing environment

Monica uses the testing capabilities of Laravel to do unit and functional
testing. While all code will have to go through to Travis before being merged,
tests can still be executed locally before pushing them. In fact, we encourage
you strongly to do it first.

To setup the test environment, create a separate testing database locally. Smart
defaults are provided in `.env.example`.

You need to setup the test database and seeds before running your tests:

* `php artisan migrate --database testing`
* `php artisan db:seed --database testing`

To run the tests, use the `phpunit` command.

If you use TravisCI to test the application, it is setup to automatically do
these actions.

Each time you change the schema of the database, you need to run again the
migrations and the seeders by running the two commands above.

## Update your local instance (or your server if you run it on production)

There is no concept of releases at the moment. If you run the project locally,
or if you have installed Monica on your own server, to update it, you need to
follow these steps below, **every single time**, or you will run into problems.

```
composer update
git pull origin master
php artisan migrate
```

That should be it.

## Front-end

### Bower

We use Bower to manage front-end dependencies. The first time you install the
project, you need to `bower install` in the root of the project. When you want
to update the dependencies, it's `bower update`.

To install a new package, use `bower install jquery -S`. The `-S` option is to
update bower.json to lock the specific version.

All the assets are stored in `resources/vendor`.

### Watching and compiling assets

CSS is written in SASS and therefore needs to be compiled before being used by
the application. To compile those front-end assets, use `gulp`.

To monitor changes and compile assets on the fly, use `gulp watch`.

### Bootstrap 4

At the current time, we are using Bootstrap 4 Alpha 2. Not everything though -
we do use only what we need. I would have wanted to use something completely
custom, but why reinvent the wheel? Anyway, make sure you don't update this
dependency with Bower. If you do, make sure that everything is thorougly tested
as when Bootstrap changes version, a lot of changes are introduced.

## Backend

### Email testing

Emails are an important of Monica. Emails are still the most significant means
of communication and people like receiving them when they are relevant. That
being said, you will need to test emails to make sure they contain what they
should contain.

For development purposes, you have two choices:
1. You can use [Mailtrap](https://mailtrap.io/). This is an amazing service that
provides a free plan that is plenty enough to test all the emails that are sent.
1. If you use Homestead to code on your local machine, you can use
[mailhog](https://github.com/mailhog/MailHog) that is built-in. To use it, you
first need to start mailhog (`sudo service mailhog restart`). Then, head up to
http://localhost:8025 in your browser to load Mailhog's UI.

#### Email reminders

Reminders are generated and sent using an Artisan command
`monica:sendnotifications`. This command is scheduled to be triggered every hour
in `app/console/Kernel.php`.

### Statistics

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
