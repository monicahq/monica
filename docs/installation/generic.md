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

### 1. Clone the repository

You may install Monica by simply closing the repository. Consider cloning the repository into any folder, example here in your 'home' directory:
```sh
cd ~
git clone https://github.com/monicahq/monica.git
```

You should check out a tagged version of Monica since `master` branch may not always be stable.
Find the latest official version on the [release page](https://github.com/monicahq/monica/releases).
```sh
cd ~/monica
# Clone the desired version
git checkout tags/v1.6.2
```

### 2. Setup the database

Log in with the root account to configure the database.
```sh
mysql -uroot -p
```

Create a database called 'monica'.
```sql
CREATE DATABASE monica;
```

Create a user called 'monica' and its password 'strongpassword'.
```sql
CREATE USER 'monica'@'localhost' IDENTIFIED BY 'strongpassword';
```

We have to authorize the new user on the monica db so that he is allowed to change the database.
```sql
GRANT ALL ON monica.* TO 'monica'@'localhost';
```

And finally we apply the changes and exit the database.
```sql
FLUSH PRIVILEGES;
exit
```

### 3. Configure Monica

`cd ~/monica` then run these steps:

1. `cp .env.example .env` to create your own version of all the environment variables needed for the project to work.
1. Update `.env` to your specific needs. Don't forget to set `DB_USERNAME` and `DB_PASSWORD` with the settings used behind.
1. Run `composer install --no-interaction --prefer-dist --no-suggest --optimize-autoloader --no-dev` to install all packages.
1. Run `php artisan key:generate` to generate an application key. This will set `APP_KEY` with the right value automatically.
1. Run `php artisan setup:production` to run the migrations, seed the database and symlink folders.
1. Optional: run `php artisan passport:install` to create the access tokens required for the API (Optional).
1. Finally, Monica requires some background processes to continuously run. The list of things Monica does in the background is described [here](https://github.com/monicahq/monica/blob/master/app/Console/Kernel.php#L33). To do this, setup a cron that runs every minute and triggers the following command `php artisan schedule:run`.

### 4. **Optional**: Setup the queues with Redis, Beanstalk or Amazon SQS

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

To configure the other queues, refer to the [official Laravel documentation](https://laravel.com/docs/master/queues#driver-prerequisites) on the topic.
