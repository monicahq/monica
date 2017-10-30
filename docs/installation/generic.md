# Setup the project on your server

If you don't want to use Docker, the best way to setup the project is to use the
same configuration that [Homestead](https://laravel.com/docs/5.3/homestead)
uses. Basically, Monica depends on the following:

* PHP 7.0+
* MySQL, SQLite or PostgreSQL
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
1. Run `php artisan key:generate` to generate an application key. This will set
`APP_KEY` with the right value automatically.
1. Create a database called `monica`.
1. `php artisan migrate` to run all migrations.
1. `php artisan storage:link` to enable avatar uploads for the contacts.
1. `php artisan db:seed --class ActivityTypesTableSeeder` to populate the
activity types.
1. `php artisan db:seed --class CountriesSeederTable` to populate the countries
table.
1. If you want to use the API, you need to run `php artisan passport:install`.
This command will create the encryption keys needed to generate secure access
tokens.
1. You also need to clear your cache `php artisan cache:clear`.
1. Finally, Monica requires some background processes to continuously run. The
list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33). To do this, setup a cron that runs every minute and
triggers the following command `php artisan schedule:run`.

**Optional**: Setup the queues with Redis, Beanstalk or Amazon SQS

Monica can work with a queue mechanism to handle different events, so we don't
block the main thread while processing stuff that can be run asynchronously,
like sending emails. By default, Monica does not use a queue mechanism but can
be setup to do so.

We highly recommend that you do not use a queue mechanism as it complexifies the
overall system and can make debugging harder when things go wrong.

This is why we suggest to use `QUEUE_DRIVER=sync` in your .env file. This will
bypass the queues entirely and will process requests as they come. In practice,
unless you have thousands of users, you don't need to use an asynchronous queue.

That being said, if you still want to make your life more complicated, here is
what you can do.

There are several choices for the queue mechanism:
* Database (this will use the database used by the application to act as a queue)
* Redis
* Beanstalk
* Amazon SQS

The simplest queue is the database driver. To set it up, simply change in your
`.env` file the following `QUEUE_DRIVER=sync` by `QUEUE_DRIVER=database`.

To configure the other queues, refer to the
[official Laravel documentation](https://laravel.com/docs/5.4/queues#driver-prerequisites)
on the topic.
