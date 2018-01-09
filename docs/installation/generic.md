# Setup the project on your server

## Prerequisites

If you don't want to use Docker, the best way to setup the project is to use the same configuration that [Homestead](https://laravel.com/docs/5.3/homestead) uses. Basically, Monica depends on the following:

* Git
* PHP 7.0+
* [Composer](https://getcomposer.org/)
* MySQL
* Optional: Redis or Beanstalk

The preferred OS distribution is Ubuntu 16.04, simply because all the development is made on it and we know it works. However, any OS that lets you install the above packages should work.

### Types of databases

The official Monica installation uses mySQL as the database system and **this is the only official system we support**. While Laravel technically supports PostgreSQL and SQLite, we can't guarantee that it will work fine with Monica as we've never tested it. Feel free to read [Laravel's documentation](https://laravel.com/docs/5.5/database#configuration) on that topic if you feel adventurous.

## Installation steps

Once the softwares above are installed:

1. Create a database called `monica` in your mySQL instance. This will let you store your data.
1. Clone the repository: `git clone https://github.com/monicahq/monica` in the folder you want to install the software to.
1. Run `cd monica` to go to the root of the newly created folder containing Monica's code.
1. Run `composer install` at the root of the folder Monica has been cloned.
1. Run `cp .env.example .env`. This will create the `.env` file that contains all the settings about Monica.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Open the file `.env` to set the different variables needed by the project. The file comes with predefined values - you won't have to change most of them.
1. Run `php artisan setup:production` to run the migrations, seed the database and symlink folders.
1. Finally, Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33). To do this, setup a cron that runs every minute and triggers the following command `php artisan schedule:run`.
1. (optional) If you want to use the API, you need to run `php artisan passport:install`. This command will create the encryption keys needed to generate secure access tokens.

**Optional**: Setup the queues with Redis, Beanstalk or Amazon SQS

Monica can work with a queue mechanism to handle different events, so we don't block the main thread while processing stuff that can be run asynchronously, like sending emails. By default, Monica does not use a queue mechanism but can be setup to do so.

We recommend that you do not use a queue mechanism as it complexifies the overall system and can make debugging harder when things go wrong.

This is why we suggest to use `QUEUE_DRIVER=sync` in your .env file. This will bypass the queues entirely and will process requests as they come. In practice, unless you have thousands of users, you don't need to use an asynchronous queue.

That being said, if you still want to make your life more complicated, here is what you can do.

There are several choices for the queue mechanism:
* Database (this will use the database used by the application to act as a queue)
* Redis
* Beanstalk
* Amazon SQS

The simplest queue is the database driver. To set it up, simply change in your `.env` file the following `QUEUE_DRIVER=sync` by `QUEUE_DRIVER=database`.

To configure the other queues, refer to the [official Laravel documentation](https://laravel.com/docs/5.4/queues#driver-prerequisites) on the topic.
