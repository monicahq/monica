# Installing Monica on Docker <!-- omit in toc -->

<img alt="Logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Docker_%28container_engine%29_logo.svg/915px-Docker_%28container_engine%29_logo.svg.png" width="290" height="69" />

Monica can run with Docker images.

- [Prerequisites](#prerequisites)
- [Use Monica docker image](#use-monica-docker-image)
- [Running the image with docker-compose](#running-the-image-with-docker-compose)

## Prerequisites

You can use [Docker](https://www.docker.com) and [docker-compose](https://docs.docker.com/compose/) to pull or build
and run a Monica image, complete with a self-contained MySQL database.
This has the nice properties that you don't have to install lots of software directly onto your system, and you can be up and running
quickly with a known working environment.

For any help about how to install Docker, see their [documentation](https://docs.docker.com/install/)

## Use Monica docker image

The [standard `monica` image](https://hub.docker.com/_/monica/) can be run with the latest release of Monica.

Run the container with:

```sh
mysqlCid="$(docker run -d \
 -e MYSQL_RANDOM_ROOT_PASSWORD=true \
 -e MYSQL_DATABASE=monica \
 -e MYSQL_USER=homestead \
 -e MYSQL_PASSWORD=secret \
 "mysql:5.7")"
docker run -d \
 --link "$mysqlCid":mysql \
 -e DB_HOST=mysql \
 -p 8080:80 \
 monica
```

Wait for the migration db to complete, then go to [http://localhost:8080](http://localhost:8080).

## Running the image with docker-compose

See some examples of docker-compose possibilities in the [example section](https://github.com/monicahq/docker/tree/master/.examples).
