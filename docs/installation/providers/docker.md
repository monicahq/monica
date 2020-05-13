# Installing Monica on Docker <!-- omit in toc -->

<img alt="Logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Docker_%28container_engine%29_logo.svg/915px-Docker_%28container_engine%29_logo.svg.png" width="290" height="69" />

Monica can run with Docker images.

- [Prerequisites](#prerequisites)
- [Use Monica docker image](#use-monica-docker-image)
  - [Using the apache image](#using-the-apache-image)
  - [Using the fpm image](#using-the-fpm-image)
  - [Persistent data storage](#persistent-data-storage)
  - [Run commands inside the container](#run-commands-inside-the-container)
- [Running the image with docker-compose](#running-the-image-with-docker-compose)
  - [Apache version](#apache-version)
  - [FPM version](#fpm-version)
- [Make Monica available from the internet](#make-monica-available-from-the-internet)
  - [Using a proxy webserver on the host](#using-a-proxy-webserver-on-the-host)
  - [Using a proxy webserver container](#using-a-proxy-webserver-container)
- [Other documents to read](#other-documents-to-read)

## Prerequisites

You can use [Docker](https://www.docker.com) and
[docker-compose](https://docs.docker.com/compose/) to pull or build
and run a Monica image, complete with a self-contained MySQL database.
This has the nice properties that you don't have to install lots of
software directly onto your system, and you can be up and running
quickly with a known working environment.

For any help about how to install Docker, see their [documentation](https://docs.docker.com/install/)

## Use Monica docker image

There are two versions of the image you may choose from.

The `apache` tag contains a full Monica installation with an apache webserver. This points to the default `latest` tag too.

The `fpm` tag contains a fastCGI-Process that serves the web pages. This image should be combined with a webserver used as a proxy, like apache or nginx.

### Using the apache image

This image contains a webserver that exposes port 80. Run the container with:
```sh
docker run -d -p 8080:80 monicahq/monicahq
```

### Using the fpm image

This image serves a fastCGI server that exposes port 9000. You may need an additional web server that can proxy requests to the fpm port 9000 of the container.
Run this container with:
```sh
docker run -d -p 9000:9000 monicahq/monicahq:fpm
```

### Persistent data storage

To have a persistent storage for your datas, you may want to create volumes for your db, and for monica you will have to save the `/var/www/monica/storage` directory.

Run a container with this named volume:
```sh
docker run -d  \
    -v monica_data:/var/www/monica/storage \
    monicahq/monicahq
```

### Run commands inside the container

Like every Laravel application, the `php artisan` command is very usefull for Monica.
To run a command inside the container, run

```sh
docker exec CONTAINER_ID php artisan COMMAND
```

or for docker-compose
```sh
docker-compose exec monicahq php artisan COMMAND
```
where `monicahq` is the name of the service in your `docker-compose.yml` file.


## Running the image with docker-compose

See some examples of docker-compose possibilities in the [example section](/scripts/docker/.examples).

---

### Apache version

This version will use the apache image and add a mysql container. The volumes are set to keep your data persistent. This setup provides **no ssl encryption** and is intended to run behind a proxy.

Make sure to pass in values for `APP_KEY` variable before you run this setup.

Set `APP_KEY` to a random 32-character string. For example, if you
have the `pwgen` utility installed, you could copy and paste the
output of `pwgen -s 32 1`.

1. Create a `docker-compose.yml` file

```yaml
version: "3.4"

services:
  app:
    image: monicahq/monicahq
    depends_on:
      - db
    ports:
      - 8080:80
    environment:
      # generate with `pwgen -s 32 1` for instance:
      - APP_KEY=
      - DB_HOST=db
    volumes:
      - data:/var/www/monica/storage
    restart: always

  db:
    image: mysql:5.7
    environment:
      - MYSQL_RANDOM_ROOT_PASSWORD=true
      - MYSQL_DATABASE=monica
      - MYSQL_USER=homestead
      - MYSQL_PASSWORD=secret
    volumes:
      - mysql:/var/lib/mysql
    restart: always

volumes:
  data:
    name: data
  mysql:
    name: mysql
```

2. Set a value for `APP_KEY` variable before you run this setup.

   It should be a random 32-character string. For example, if you have the `pwgen` utility installed,
   you can copy and paste the output of
   ```sh
   pwgen -s 32 1
   ```

3. Run
   ```sh
   docker-compose up -d
   ```
   
   Wait until all migrations are done and then access Monica at http://localhost:8080/ from your host system.
   If this looks ok, add your first user account.

4. Run this command once:
   ```sh
   docker-compose exec app php artisan setup:production
   ```


### FPM version

When using FPM image, you will need another container with a webserver to proxy http requests. In this example we use nginx with a basic container to do this.

The webserver will need an access to all static files from Monica container, the volumes `html` will deal with it.


1. Download `nginx.conf` file. An example can be found on the [`example section`](/scripts/docker/.examples/supervisor/fpm/web/nginx.conf)
   ```sh
   curl -sSL https://raw.githubusercontent.com/monicahq/monica/master/scripts/docker/.examples/supervisor/fpm/web/nginx.conf -o nginx.conf
   ```

2. Create a `docker-compose.yml` file

```yaml
version: "3.4"

services:
  app:
    image: monicahq/monicahq:fpm
    depends_on:
      - db
    environment:
      # generate with `pwgen -s 32 1` for instance:
      - APP_KEY=
      - DB_HOST=db
    volumes:
      - html:/var/www/monica
      - data:/var/www/monica/storage
    restart: always
  
  web:
    image: nginx
    ports:
      - 8080:80
    depends_on:
      - app
    volumes:
      # see [`nginx.conf`](/scripts/docker/.examples/supervisor/fpm/web/nginx.conf)
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - html:/var/www/monica:ro
      - data:/var/www/monica/storage:ro
    restart: always

  db:
    image: mysql:5.7
    environment:
      - MYSQL_RANDOM_ROOT_PASSWORD=true
      - MYSQL_DATABASE=monica
      - MYSQL_USER=homestead
      - MYSQL_PASSWORD=secret
    volumes:
      - mysql:/var/lib/mysql
    restart: always

volumes:
  data:
    name: data
  html:
    name: html
  mysql:
    name: mysql
```

3. Set a value for `APP_KEY` variable before you run this setup.

   It should be a random 32-character string. For example, if you have the `pwgen` utility installed,
   you can copy and paste the output of
   ```sh
   pwgen -s 32 1
   ```

4. Run
   ```sh
   docker-compose up -d
   ```
   
   Wait until all migrations are done and then access Monica at http://localhost:8080/ from your host system.
   If this looks ok, add your first user account.

5. Run this command once:
   ```sh
   docker-compose exec app php artisan setup:production
   ```


## Make Monica available from the internet 

To expose your Monica instance for the internet, it's important to set `APP_ENV=production` in your `.env` file or environment variables. In this case `https` scheme will be **mandatory**.

### Using a proxy webserver on the host

One way to expose your Monica instance is to use a proxy webserver from your host with SSL capabilities. This is possible with a reverse proxy.

See [this documentation](/docs/installation/ssl.md) about howto set a ssl reverse proxy.

### Using a proxy webserver container

See some examples of docker-compose possibilities in the [example section](/scripts/docker/.examples) to show how to a proxy webserver with ssl capabilities.



## Other documents to read	

- [Build your own docker image](/docs/contribute/docker.md)
- [Connecting to MySQL inside of a Docker container](/docs/installation/docker-mysql.md)
