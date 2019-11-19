# Installing Monica on Docker

<img alt="Logo" src="https://fr.wikipedia.org/wiki/Docker_(logiciel)#/media/Fichier:Docker_(container_engine)_logo.svg" width="290" height="69" />

Monica can run with Docker images.

## Prerequisites

You can use [Docker](https://www.docker.com) and
[docker-compose](https://docs.docker.com/compose/) to pull or build
and run a Monica image, complete with a self-contained MySQL database.
This has the nice properties that you don't have to install lots of
software directly onto your system, and you can be up and running
quickly with a known working environment.

For any help about how to install Docker, see https://docs.docker.com/install/

## Use Monica docker image

There are two versions of the image you may choose from.
The `apache` tag contains a full Monica installation with an apache webserver. This points to the default `latest` tag too.
The `fpm` tag contains a fastCGI-Process that serves the web pages. This images should be combined with a webserver used as a proxy, like apache or nginx.

### Using the apache image

This image contains a webserver that exposes port 80. Run the container with:
```sh
docker run -d -p 8080:80 monicahq/monicahq
```

### Using the fpm image

This image serves a fastCGI server that exposes port 9000. You may need an additional web server that can proxy requests to the fpm port 9000 of the container.
Run this container with:
```sh
docker run -d monicahq/monicahq:fpm
```

## Running the image with docker-compose


### 1. Get your .env file

Download a copy of MonicaHQ's example configuration file:

```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/.env.example -o .env
```

Open the file in an editor and update it for your own needs:

- Set `APP_KEY` to a random 32-character string. For example, if you
  have the `pwgen` utility installed, you could copy and paste the
  output of `pwgen -s 32 1`.
- Edit the `MAIL_*` settings to point to your own [mailserver](/docs/installation/mail.md).
- Set `DB_*` settings to point to your database configuration. If you don't want to set a db prefix, be careful to set `DB_PREFIX=` and not `DB_PREFIX=''` as docker will not expand this as an empty string.


### 2. Run with docker-compose

Start by fetching the latest `docker-compose.yml` file.

```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/docker-compose.yml -o docker-compose.yml
```

Edit the `docker-compose.yml` and change both the volumes on the monicahq service and the mysql service. Change the part before the `:` and point it to an existing, empty directory on your system. It is also be a good idea to change the webserver port from `80:80` to `3000:80`.

Edit `.env` to set `DB_HOST=mysql` (as `mysql` is the creative name of the MySQL container).

Start by downloading all the images and setup your new instance.

```sh
docker-compose pull
docker-compose up
```

### 3. Set the container

Wait until all migrations are done and check if you can open up the login page by going to http://localhost. If this looks ok, add your first user account.

```sh
docker-compose exec monicahq php artisan setup:production
```

Now login.

## Other documents to read	

[Build your own docker image](/docs/contribute/docker.md)
[Connecting to MySQL inside of a Docker container](/docs/installation/docker-mysql.md)
[Use mobile app with standalone server](/docs/installation/mobile.md)
