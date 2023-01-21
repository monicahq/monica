## Warning - alpha version - do not use

Chandler is currently in alpha state which means it's like a teenager, full of potential but also prone to mood swings and unexpected outbursts. So, please use it at your own risk, we don't want you to end up with spaghetti code for breakfast. We're working hard to make sure it grows into a stable and reliable adult, but for now, it's best to think of it as a pet rock. It may not do much, but it's fun to play with. So, let's have fun testing it together!

## Local development

1. Install PHP and a web server like Nginx. If you are on macOS, we recommend [Valet](https://laravel.com/docs/9.x/valet)
2. Install [SQLite](https://formulae.brew.sh/formula/sqlite) or MySQL
3. `composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader`
4. `yarn install --frozen-lockfile`
5. `cp .env.example .env` and configure `.env` file
   1. `php artisan key:generate --no-interaction` (generates APP_KEY)
   2. `touch monica.db` (if you use SQLite) and add the path to DB_DATABASE
6. `php artisan monica:setup --force -vvv`
7. Optional: generate dummy data
   1. `php artisan monica:dummy --force -vvv`
8. Optional: make the search work:
   1. Install and run [meilisearch](https://www.meilisearch.com/) locally
   2. Configure and run a queue (`php artisan queue:listen --queue=high,low,default`)
9. `yarn build` to generate the proper JS and CSS files
10. `yarn dev` and head to your browser to play with Monica

## Configuring search

Search in production is done with Meilisearch. As we use [Laravel Scout](https://laravel.com/docs/9.x/scout#introduction), you can use other drivers if you prefer.

That being said, if you want to index new data, or add additional data to search data for, you need to indicate all the indexes of this new data in SetupApplication.php – otherwise, Meilisearch won't know the indexes and you will end up with an error message saying that data is not filterable.

## Docker

We have a [docker image](https://github.com/monicahq/chandler/pkgs/container/monica-next) that you can use to run this project locally.

First you need setup [authentication to the Container registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-to-the-container-registry)

Then play with the image:

```sh
docker run -p 8080:80 ghcr.io/monicahq/monica-next:main
```

This runs the image locally on port 8080 and using sqlite. You can then access the application at http://localhost:8080.

### Configuration

Note that you'll need to setup a mail mailer to be able to register a user.
You can try to use the `log` mailer like this:

```sh
docker run -p 8080:80 -e MAIL_MAILER=log ghcr.io/monicahq/monica-next:main
```

For more complex scenario (database setup, queue, etc.) see https://github.com/monicahq/docker/tree/main/.examples

### Build it yourself

You can also build your image locally using `yarn docker:build` and run it with `yarn docker:run`.
