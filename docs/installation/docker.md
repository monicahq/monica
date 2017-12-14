# Running with Docker

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

Note for macOS: you will need to stop Apache if you wish to have Monica available on port 80.

You can do this like so:

```sh
$ sudo /usr/sbin/apachectl stop
```

To start Apache up again use this command:

```sh
$ sudo /usr/sbin/apachectl start
```

Now select one of these methods to be up and running quickly:

#### Use docker-compose to run a pre-built image

This is the easiest and fastest way to try Monica! Use this process
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

#### Other documents to read

[Connecting to MySQL inside of a Docker container](../database/connecting.md)
