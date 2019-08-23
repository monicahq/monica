# Installing Monica on Docker

You can use [Docker](https://www.docker.com) and
[docker-compose](https://docs.docker.com/compose/) to pull or build
and run a Monica image, complete with a self-contained MySQL database.
This has the nice properties that you don't have to install lots of
software directly onto your system, and you can be up and running
quickly with a known working environment.

Before you start, you need to get and edit a `.env` file. If you've already
cloned the [Monica Git repo](https://github.com/monicahq/monica), run:

```sh
cp .env.example .env
```

to create it. If not, you can fetch it from GitHub like:

```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/.env.example -o .env
```

Then open `.env` in an editor and update it for your own needs:

- Set `APP_KEY` to a random 32-character string. For example, if you
  have the `pwgen` utility installed, you could copy and paste the
  output of `pwgen -s 32 1`.
- Edit the `MAIL_*` settings to point to your own mailserver.
- Set `DB_*` settings to point to your database configuration. If you don't want to set a db prefix, be careful to set `DB_PREFIX=` and not `DB_PREFIX=''` as docker will not expand this as an empty string.

Note for macOS: you will need to stop Apache if you wish to have Monica available on port 80.

You can do this like so:

```sh
sudo /usr/sbin/apachectl stop
```

To start Apache up again use this command:

```sh
sudo /usr/sbin/apachectl start
```

Now select one of these methods to be up and running quickly:

#### Use docker-compose to run a pre-built image

This is the easiest and fastest way to try Monica! Use this process
if you want to download the newest image from Docker Hub and run it
with a pre-packaged MySQL database.

Start by fetching the latest `docker-compose.yml` and `.env` if you haven't done that already.

```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/docker-compose.yml -o docker-compose.yml
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/.env.example -o .env
```

Edit the `docker-compose.yml` and change both the volumes on the monicahq service and the mysql service. Change the part before the `:` and point it to an existing, empty directory on your system. It is also be a good idea to change the webserver port from `80:80` to `3000:80`.

Edit `.env` to set `DB_HOST=mysql` (as `mysql` is the creative name of the MySQL container).

Start by downloading all the images and setup your new instance.

```sh
docker-compose pull
docker-compose up
```

Wait until all migrations are done and check if you can open up the login page by going to http://localhost:3000. If this looks ok, add your first user account.

```sh
docker-compose exec monicahq php artisan setup:production
```

Now login.

#### Use docker-compose to build and run your own image

Use this process if you want to modify Monica source code and build
your image to run.

Edit `.env` again to set `DB_HOST=mysql` (as `mysql` is the creative name of the MySQL container).

Then run:

```sh
docker-compose build
docker-compose up
```

#### Use Docker directly to run with your own database

Use this process if you're a developer and want complete control over
your Monica container.

Edit `.env` again to set the `DB_*` variables to match your database. Then run:

```sh
docker build -t monicahq/monicahq .
docker run --env-file .env -p 80:80 monicahq/monicahq  # to run MonicaHQ
# ...or...
docker run --env-file .env -it monicahq/monicahq sh    # to get a prompt
```

Note that uploaded files, like avatars, will disappear when you
restart the container. Map a volume to
`/var/www/monica/storage/app/public` if you want that data to persist
between runs. See `docker-compose.yml` for examples.

#### Other documents to read	

[Connecting to MySQL inside of a Docker container](/docs/installation/docker-mysql.md)
[Use mobile app with standalone server](/docs/installation/mobile.md)
