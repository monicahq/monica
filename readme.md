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
* [Get started](#get-started)
   * [Update your server](#update-your-server)
   * [Importing vCards (CLI only)](#importing-vcards-cli-only)
   * [Importing SQL from the exporter feature](#importing-sql-from-the-exporter-feature)
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
* [Vision, goals and strategy](#vision-goals-and-strategy)
   * [Vision](#vision)
   * [Goals](#goals)
   * [Strategy](#strategy)
   * [Monetization](#monetization)
      * [The API](#the-api)
   * [Why Open Source?](#why-open-source)
* [License](#license)

## Introduction

Monica is an open-source web application to organize the interactions with your
loved ones. Think of it as a [CRM](https://en.wikipedia.org/wiki/Customer_relationship_management)
(a popular tool used in companies) for your friends or family. This is what it
currently looks like:

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
won't guess what you want to do. It's pretty dumb: it will send you
emails only for the things you asked to be reminded of.

## Get started

There are multiple ways of getting started with Monica.

1. You can use our hosted-version (this is the simplest way to use the product)
on [https://monicahq.com](https://monicahq.com).
1. You can run it with Docker ([instructions](docs/installation/docker.md)).
1. You can install it on your server
([generic instructions](docs/installation/generic.md)).
1. You can install it from scratch on Debian Stretch
([instructions](docs/installation/debian.md)).
1. You can deploy to Heroku ([instructions](docs/installation/heroku.md)).

You have the liberty to clone the repository and set it up yourself on any
hosting provider, for free. I'm just asking that you don't try to make money out
of it yourself.

### Update your server

There is no concept of releases at the moment. If you run the project locally,
or if you have installed Monica on your own server, you need to follow these
steps below to update it, **every single time**, or you will run into problems.

1. Always make a backup of your data before upgrading.
1. Check that your backup is valid.
1. Read the [release notes](https://github.com/monicahq/monica/blob/master/CHANGELOG)
to check for breaking changes.
1. Run the following commands:

```
git pull origin master
composer update
php artisan migrate
```

That should be it.

### Importing vCards (CLI only)

**Note**: this is only possible if you install Monica on your server or locally.

You can import your contacts in vCard format in your account with one simple
CLI command:
`php artisan import:vcard {email user} {filename}.vcf`

where `{email user}` is the email of the user in your Monica instance who will
be associated the new contacts to, and `{filename}` being the name of your .vcf file.
The .vcf file has to be in the root of your Monica installation (in the same directory
where the artisan file is).

Example: `php artisan import:vcard john@doe.com contacts.vcf`

The `.vcf` can contain as many contacts as you want.

### Importing SQL from the exporter feature

Monica allows you to export your data in SQL, under the Settings panel. When you
export your data in SQL, you'll get a file called `monica.sql`.

To import it into your own instance, you need to make sure that the database of
your instance is completely empty (no tables, no data).

Then, follow the steps:

* `php artisan migrate`
* `php artisan db:seed --class ActivityTypesTableSeeder`
* `php artisan db:seed --class CountriesSeederTable`
* Then import `monica.sql` into your database. Tools like phpmyadmin or Sequel
Pro might help you with that.
* Finally, sign in with the same credentials as the ones used on
https://monicahq.com and you are good to go.

There is one caveat with the SQL exporter: you can't get the photos you've
uploaded for now.

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
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. `npm install` to install bower and gulp.
1. `bower install` to install front-end dependencies in the `vendor` folder.
1. Create a database called `monica`.
1. `php artisan key:generate` to generate a random APP_KEY
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

There are several ways to help this project to move forward:
* Unlike Fight Club, the best way to help is to actually talk about the project
as much as you can.
* You can answer questions in the issue tracker to help other community members.
* If you are a developer:
   * Read our [Contribution Guide](/CONTRIBUTING.md).
   * Look for [issues labelled bugs](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Abug)
     if you are looking to have an immediate impact on the project.
   * Look for [issues labelled enhancements](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Aenhancement)
     These are issues that you can solve relatively easily.
   * If you are an advanced developer, you can try to tackle
     [issues labelled feature requests](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3A%22feature+request%22).
     Beware though - they are harder to do and will require a lot of back and
     forth with the repository administrator in order to make sure we are going
     to the right direction with the product.
   * Finally, and most importantly, we are looking for people willing to write
     tests for the existing features.

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
* Create a smart recommendation system for gifts. For instance, if my nephew is
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

### Monetization

The big topic. Yes, we plan to make money out of this tool to sustain it on the
long run. We are a big fan of [Sentry](https://sentry.io), Wordpress and GitLab
and we believe this kind of business model is inspiring, where everyone wins.

* On https://monicahq.com, Monica will be offered in two versions:
  * a free plan (called **Joe**):
    * No limits of contacts
    * Importers/exporters
    * Email reminders
  * a paid plan (called **Chandler**):
    * Advanced features
    * People who contribute to the GitHub repository (with a pull request that
    adds value, that gets merged (not a typo fix, for instance) will also have
    access to the Paid version for free.
* You can also **run it yourself**. This is the **Ross** version. This is
sometimes also called on-premise. Download the code, run it on Heroku, with
Docker. The choice is yours.
  * The downloadable version will always be the most complete version - the same
  offered on the paid plan on `.com`.
  * This version will be completely free with no strings attached and you will
  be in complete control.
* There is a [Patreon account](https://www.patreon.com/monicahq) for those who
still want to support the tool. Keep in mind that the best way to support it is
to actually talk about it around you.

There is currently not, and will never be, ads on the platform. I will never
resell your data on `.com`. I'm like you: I hate big corporations that do not
have at heart the best thing for their users, even if they say otherwise. The only
way, therefore, to sustain the development of the product is to actually make
money in a good-old fashioned way.

#### The API

The API will be opened to everyone, for both on `.com` and on-premises.

### Why Open Source?

Why is Monica open source? Is it risky? Will someone steal my code and do a
for-profit business that will kill my own business? Why reveal my strategy to
the world? This is the kind of questions we've received by email already.

The answer to these questions is simple: yes, you can fork the project and do a
competing project, make money out of it (even if the license is not super
friendly to achieve that) and I'll never know. But it's ok, I don't mind.

I wanted to open source this project for several reasons:

* I believe, perhaps naively, that this project can really change people's
lives. While I aim to make money out of it, I also want everyone to benefit
from it. Open sourcing a project like this will help Monica become much bigger
than what I imagine myself. While I strongly believe that the project has to
follow the vision I have for it, I need to be humble enough to know that ideas
come from everywhere, and people have much better ideas than what I can have.
* You can't do something great alone. While Monica could become a company and
hire a bunch of super smart people to work on it, you can't beat the manpower of
an entire community. Open sourcing the product means bugs will be fixed faster,
features will be developed faster, and more importantly, developers will be able
to contribute to the project that changes either their own lives, or other
people's lives.
* Doing things in a transparent manner, like it's the case when you open source
something, lead to formidable things. People respect the project more. You can't
hide nasty piece of code. You can't do things behind the back of your users.
It's a major driving force that motivates you to keep doing what's right.
* I believe that once you have created a community of passionate developers
around your project, you've won - because developers are very powerful
influencers. Developers will create apps around your product, talk about it on
forums, and tell about the project to their friends. Cherish the developers -
users will follow.

### Patreon

You can support the development of this tool
[on Patreon](https://www.patreon.com/monicahq). Thanks for your help.

## Contact

If you need to talk, you can contact me at regis AT monicahq DOT com. You can
also reach me [on Twitter](https://twitter.com/djaiss).

## License

Copyright (c) 2016-2017 Regis Freyd

Licensed under the AGPL License
