# Contribute as a developer

<!-- TOC -->

- [Contribute as a developer](#contribute-as-a-developer)
  - [Considerations](#considerations)
  - [Design rules](#design-rules)
  - [Install Monica locally](#install-monica-locally)
    - [Homestead (macOS, Linux, Windows)](#homestead-macos-linux-windows)
    - [Valet (macOS)](#valet-macos)
    - [Instructions](#instructions)
  - [Testing environment](#testing-environment)
    - [Setup](#setup)
    - [Run the test suite](#run-the-test-suite)
    - [Run browser tests](#run-browser-tests)
    - [Mocking HTTP calls](#mocking-http-calls)
  - [Coding guidelines](#coding-guidelines)
    - [Feature branch](#feature-branch)
    - [Conventional commits](#conventional-commits)
  - [Backend](#backend)
    - [Things to consider when adding new code](#things-to-consider-when-adding-new-code)
      - [Add a new table to the database schema](#add-a-new-table-to-the-database-schema)
      - [Manipulating data during a migration](#manipulating-data-during-a-migration)
    - [Email testing](#email-testing)
    - [Email reminders](#email-reminders)
    - [Statistics](#statistics)
  - [Database](#database)
    - [Connecting to mySQL](#connecting-to-mysql)
  - [Front-end](#front-end)
    - [Considerations](#considerations-1)
    - [Mix](#mix)
    - [Watching and compiling assets](#watching-and-compiling-assets)
    - [CSS](#css)
    - [JS and Vue](#js-and-vue)
    - [Localization (i18n)](#localization-i18n)
      - [Application](#application)
        - [Laravel](#laravel)
        - [VueJS](#vuejs)

<!-- /TOC -->

Are you interested in giving a hand? We can't be more excited about it. Thanks in advance!

Notes:
* _we are doing everything we can to review pull requests submitted by the community as soon as possible. It can take days (or weeks) to finalize a review, going through rounds of changes, etc... This is why we kindly ask you to be patient during this process._
* _no changes are too small. If you want to contribute, even fixing a typo will help._

Here are some guidelines that could help you to get started quickly.

<a id="markdown-considerations" name="considerations"></a>
## Considerations

* Monica is written with a great framework, [Laravel](https://github.com/laravel/laravel). We care deeply about keeping Monica very simple on purpose. The simpler the code is, the simpler it will be to maintain it and debug it when needed. That means we don't want to make it a one page application, or add any kind of complexities whatsoever.
* That means we won't accept pull requests that add too much complexity, or written in a way we don't understand. Again, the number 1 priority should be to simplify the maintenance on the long run.
* It's better to move forward fast by shipping good features, than waiting for months and ship a perfect feature.
* Our product philosophy is simple. Things do not have to be perfect. They just need to be shipped. As long as it works and aligns with the vision, you should ship as soon as possible. Even if it's ugly, or very small, that does not matter.

<a id="markdown-design-rules" name="design-rules"></a>
## Design rules

* **Keep it simple**. No new options, please. Options are evil. It creates a bloated software. Not everything should be configurable.
* **Use what already exists in the current stack**. When adding a feature, do not introduce a new software in the existing stack. For instance, at the moment, the current version does not require Redis to be used. If we do create a feature that (for some reasons) depends on Redis, we will need all existing instances to install Redis on top of all the other things people have to setup to install Monica (there are thousands of them). We can't afford to do that.
* **Always think about the API**. When introducing new classes and concepts in the app, your changes should always be implemented as well. Everything that we do should be accessible through the API.

<a id="markdown-install-monica-locally" name="install-monica-locally"></a>
## Install Monica locally

<a id="markdown-homestead-macos-linux-windows" name="homestead-macos-linux-windows"></a>
### Homestead (macOS, Linux, Windows)

The best way to contribute to Monica is to use [Homestead](https://laravel.com/docs/homestead) as a development environment, which is an official, pre-packaged Vagrant box that provides you a wonderful development environment without requiring you to install PHP, a web server, and any other server software on your local machine. The big advantage is that it runs on any Windows, Mac, or Linux system.

This is what is used to develop Monica and what will provide a common base for everyone who wants to contribute to the project. Once Homestead is installed, you can pull the repository and start setting up Monica.

Note: the official Monica installation uses mySQL as the database system. While Laravel technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/5.5/database#configuration) on that topic if you feel adventurous.

<a id="markdown-valet-macos" name="valet-macos"></a>
### Valet (macOS)

We've installed the development version with [Valet](https://laravel.com/docs/valet), which is a Laravel development environment for Mac minimalists. It works really well and is extremely fast, much faster than Homestead.

<a id="markdown-instructions" name="instructions"></a>
### Instructions

**Prerequisites**:
* Git
* [Node](https://nodejs.org/en/)
* PHP 7.2+
* [Composer](https://getcomposer.org/)
* GNU Make

**Steps to install Monica**

Once the above softwares are installed (or if you've finished the installation of Homestead/Valet):

1. Create a database called `monica` in your mySQL instance.
    1. From Homestead directory: `sudo scripts/create-mysql.sh monica` or `mysql -e "CREATE DATABASE 'monica'";` inside mySQL.
    1. If you use Homestead (which uses Vagrant under the hood), `vagrant ssh` will let you login as root inside your VM.
1. Run `make install` in the folder the repository has been cloned. This will run :
    1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
    1. `composer install --no-interaction --no-suggest --ignore-platform-reqs` to install all packages.
    1. `yarn install` to install all the front-end dependencies and tools needed to compile assets.
    1. `yarn run dev` to compile js and css assets.
    1. `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
    1. `php artisan setup:test` to setup the database.
       - By default this command will also populate the database with fake data.
       - Use the `--skipSeed` option to skip the process of adding fake data in your dev environment.
    1. `php artisan passport:install` to create the access tokens required for the API (Optional).
1. Update `.env` to your specific needs.

If you haven't skipped the seeding of fake data, two accounts are created by default:

* First account is `admin@admin.com` with the password `admin0`. This account contains a lot of fake data that will let you play with the product.
* Second account is `blank@blank.com` with the password `blank0`. This account does not contain any data and shall be used to check all the blank states.

To update a current installation with the latest dependencies, just run `make update` to run
  1. `composer install --no-interaction --no-suggest --ignore-platform-reqs`
  1. `yarn upgrade`
  1. `yarn run dev`
  1. `php artisan migrate`

<a id="markdown-testing-environment" name="testing-environment"></a>
## Testing environment

We try to cover most features and new methods with unit and functional tests. Any pull request submitted on GitHub will have to go through Travis and pass before being merged. Moreover, we **strongly** encourage adding unit tests for every new method added to the codebase to ensure code stability, and we will probably refuse a pull request if there is no tests for it.

<a id="markdown-setup" name="setup"></a>
### Setup

To setup the test environment:

* Create a database called `monica_test`
* `php artisan migrate --database testing`
* `php artisan db:seed --database testing`

<a id="markdown-run-the-test-suite" name="run-the-test-suite"></a>
### Run the test suite

The test suite uses Phpunit. It's mainly used to perform unit tests or quick, small functional tests.

To run the test suite:

* `phpunit` or `./vendor/bin/phpunit` in the root of the folder containing Monica's code from GitHub.

<a id="markdown-run-browser-tests" name="run-browser-tests"></a>
### Run browser tests

Browsers tests simulate user interactions in a live browser.

* To run browser tests, first you need to install chrome
```
curl -sS -o - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add
echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google-chrome.list
sudo apt -y update
sudo apt -y -f install google-chrome-stable fonts-liberation libappindicator1
```
* Then you can run the test suite:
`php artisan dusk`

<a id="markdown-mocking-http-calls" name="mocking-http-calls"></a>
### Mocking HTTP calls

You should never make real HTTP calls in your unit tests - like querying an external API that is not linked to Monica.

You can mock http calls by mocking calls made by Guzzl.

You can find an example of how mocking is done in the `GetWeatherInformationTest.php` file.

You need to provide a sample response of the external call that you are mocking in the Fixtures folder.

<a id="markdown-coding-guidelines" name="coding-guidelines"></a>
## Coding guidelines

<a id="markdown-feature-branch" name="feature-branch"></a>
### Feature branch

We follow [GitHub Flow](https://guides.github.com/introduction/flow/) to manage the development of features.

<a id="markdown-conventional-commits" name="conventional-commits"></a>
### Conventional commits

We follow the [conventional commit message](https://conventionalcommits.org/) syntax for our commits. For instance, `feat: allow provided config object to extend other configs` or `feat(lang): added polish language`.

Every feature branch that is squashed onto master must follow these rules.

The benefits are:
* a standard way of writing commit messages for every contributor,
* a way to quickly see and understand what the commit does and what it affects,
* automatic changelog creation based on those keywords.

The keywords that support (heavily inspired by [config-conventional](https://github.com/marionebl/commitlint/tree/master/%40commitlint/config-conventional)):
* `ci`,
* `chore`,
* `docs`,
* `feat`,
* `fix`,
* `perf`,
* `refactor`,
* `revert`,
* `style`,
* `test`.

Moreover, every commit message needs to be written in lowercase.
* ✅  feat(lang): added polish language
* ❌  feat(lang): Added polish language

All the commits in a pull request are squashed when merged into master. That means *only the commit message of the squashed branch needs to follow this commit message convention*. That also means that you don't need to follow this convention for commits within a branch, which will usually contains a lot of commits with a `wip` title.

<a id="markdown-backend" name="backend"></a>
## Backend

The project follows strict [object calisthenics](http://www.slideshare.net/guilhermeblanco/object-calisthenics-applied-to-php), as much as possible and more and more over time. We will soon implement those rules in the Linters and will block a pull request for the code that does not follow those guidelines.
Here are the rules (adapted for PHP):
* Only one indentation level per method,
* Do not use the "else" keyword,
* Do not chain different objects, unless if the execution includes getters and setters,
* Keep your entities small: 100 lines per class and no more than 15 classes per package,
* Any class that contains a collection (or array) cannot use any other properties,
* Document your code.

<a id="markdown-things-to-consider-when-adding-new-code" name="things-to-consider-when-adding-new-code"></a>
### Things to consider when adding new code

<a id="markdown-add-a-new-table-to-the-database-schema" name="add-a-new-table-to-the-database-schema"></a>
#### Add a new table to the database schema

If you add a new table, make sure there is a column called `account_id` in this new table. That way, we will make sure that the script responsible for resetting or deleting a user account will go take this new table into consideration while running.

<a id="markdown-manipulating-data-during-a-migration" name="manipulating-data-during-a-migration"></a>
#### Manipulating data during a migration

Sometimes you need to manipulate and move data around when you decide to change the database structure. In that case, as much as possible, do not use Eloquent to change the data. Use either [raw SQL queries](https://laravel.com/docs/5.5/database#running-queries) or the [Query builder](https://laravel.com/docs/5.5/queries) to do it. This is due to the fact that objects might change overtime or even be deleted, which would break the migrations entirely.

<a id="markdown-email-testing" name="email-testing"></a>
### Email testing

Emails are an important part of Monica. Emails are still the most significant mean of communication and people like receiving them when they are relevant. That being said, you will need to test emails to make sure they contain what they should contain.

For development purposes, you have two choices to test emails:

1. You can use [Mailtrap](https://mailtrap.io/). This is an amazing service that provides a free plan that is plenty enough to test all the emails that are sent.
1. You can use [mailhog](https://github.com/mailhog/MailHog) to test locally. On macOS, you can install via Homebrew (`brew install mailhog`). Then, run `mailhog` and point the browser to `http://127.0.0.1:8025` ([more complete instructions](https://github.com/maijs/homebrew-mailhog)).
1. If you use [Homestead](https://laravel.com/docs/homestead), [mailhog](https://github.com/mailhog/MailHog) is actually built-in. To use it, you first need to start mailhog (`sudo service mailhog restart`) inside your Vagrant. Then, head up to [http://localhost:8025](http://localhost:8025) in your local browser to load Mailhog's UI.

Note: if you want to use mailhog, you need the following settings in your `.env` file:

```
MAIL_DRIVER=smtp
MAIL_HOST=0.0.0.0
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
```

<a id="markdown-email-reminders" name="email-reminders"></a>
### Email reminders

Monica sends two types of emails: reminders, and notifications. Notifications are sent 7 and 30 days before an event happens, while reminders are sent the day the event happens.
Reminders are generated and sent using an Artisan command `send:reminders`. Notifications are sent using `send:notifications`. Those commands are scheduled to be triggered every hour in `app/console/Kernel.php`.

<a id="markdown-statistics" name="statistics"></a>
### Statistics

Monica calculates every night (ie once per day) a set of metrics to help you understand how the instance is being used by users. That will also allow to measure growth over time.

Statistics are generated by the Artisan command `monica:calculatestatistics` every night at midnight and this cron is defined in `app/console/Kernel.php`.

<a id="markdown-database" name="database"></a>
## Database

As said above, Monica uses mySQL by default. While Laravel supports multiple DBMS, we can't assure you it will work with any other DBMS than mySQL.

<a id="markdown-connecting-to-mysql" name="connecting-to-mysql"></a>
### Connecting to mySQL

If you want to connect directly to Monica's MySQL instance read [_Connecting to MySQL inside of a Docker container_](./docs/database/connecting.md).

<a id="markdown-front-end" name="front-end"></a>
## Front-end

<a id="markdown-considerations-1" name="considerations-1"></a>
### Considerations

* If your contribution involves a change in the UI (even if it's very small), please ping @djaiss in an issue *before* you start working on it, explaining what you want to achieve, why and how. We want to maintain a high level of visual quality in the software and we will dismiss all pull requests that change the front end that have not been discussed before-hand.
* That being said, we'll probably receive pull requests that change the front end before any previous discussion on the topic. In this case, we do not guarantee that we'll accept the pull request, but in order to increase the chances that it will:
    * Make sure to follow the current visual style and layout.
    * Make sure you do not introduce new colors in the UI.
    * Make sure the user experience is consistent with the rest of the application (ie buttons behave the same, modals are like other modals,...).
    * Make sure you don't introduce new CSS classes, unless they are absolutely necessary. Use the classes provided by [Tachyons](tachyons.io) which is the functional CSS framework we currently use.
    * Do not use Jquery. When needed, use VueJS.

The above comments can seem harsh and we apologize in advance. However you have to understand that we deeply care about providing the best user experience to our users. Features that are purely backend do not have the same impact as the ones that the user interacts with. Features that modify the front end will have a tremendous impact on how users perceive the software. Therefore we want to make sure that anything that touches the frontend is perfect and aligned with our vision.

<a id="markdown-mix" name="mix"></a>
### Mix

We use [mix](https://laravel.com/docs/5.5/mix) to manage the front-end and its dependencies, and also to compile and/watch the assets. **Please note that we should do our best to prevent introducing new dependencies if we can prevent it**.

Mix should be available in your development environment if you have installed Monica locally and ran `yarn install` in the first place.

If you need to add a new dependency, update `package.json` to add it and make sure you commit `package-lock.json` once `package.json` is updated.

<a id="markdown-watching-and-compiling-assets" name="watching-and-compiling-assets"></a>
### Watching and compiling assets

CSS is written in SASS and therefore needs to be compiled before being used by the application. To compile those front-end assets, use `yarn run dev`.

To monitor changes and compile assets on the fly, use `yarn run watch`.

<a id="markdown-css" name="css"></a>
### CSS

At the current time, we are using a mix of Bootstrap 4 and [Tachyons](https://tachyons.io). We aim to use [Atomic CSS](https://adamwathan.me/css-utility-classes-and-separation-of-concerns/) instead of having bloated, super hard to maintain CSS files. We'll get rid of Bootstrap entirely over time.

This means that we should add new CSS classes only if it's absolutely necessary.

<a id="markdown-js-and-vue" name="js-and-vue"></a>
### JS and Vue

We are using [Vue.js](https://vuejs.org/) in some parts of the application, and we'll use it more and more over time. Vue is very simple to learn and use, and with [Vue Components](https://vuejs.org/v2/guide/components.html), we can easily create isolated, reusable components in the app. If you want to add a new feature, you don't need to use Vue.js - you can use plain HTML views served by the backend. But with Vue.js, it'll be a nicer experience.

<a id="markdown-localization-i18n" name="localization-i18n"></a>
### Localization (i18n)

<a id="markdown-application" name="application"></a>
#### Application

Localization of the application is handled by the [default i18n helper provided by Laravel](https://laravel.com/docs/5.5/localization). When adding or modifying strings, you only have to handle the `en` language, which is stored in `resources/lang/en/`. The other locales are going to be handled by Crowdin, our translation platform.

We also have [a dedicated page](/docs/contribute/translate.md) for our translators, just in case you need it.

<a id="markdown-laravel" name="laravel"></a>
##### Laravel

We use the default Laravel helper: `trans('app.save')`.

<a id="markdown-vuejs" name="vuejs"></a>
##### VueJS

For everything that is in VueJS though, things are a bit different. We have to use a special library to allow translated strings to be available in the javascript views. The helper in Vue is slightly different.

You can use these replacements instead of the regular (php) definition:
* `trans('file.string')` is writen `$t('file.string')`.
* `trans('file.string', ['param' => $value])` is writen `$t('file.string', {param: value})`.
* `trans_choice('file.string', $count)` is writen `$tc('file.string', count)`.

Important note: every time a string changes in a translation file, you need to regenerate all the strings so they can be made available in JS. To do this,
* use `php artisan lang:generate`
* then compile all the JS assets `yarn run prod`, and commit the whole.
