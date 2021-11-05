# Build Docker image for Monica

If you want to build your own docker image for Monica, follow these steps:

## Use docker-compose to build and run your own image

Use this process if you want to modify Monica source code and build
your image to run.

Edit `.env` to set `DB_HOST=mysql` (as `mysql` is the creative name of the MySQL container).

Then run:

```sh
docker-compose -f docker-compose.dev.yml build
docker-compose -f docker-compose.dev.yml up
```

## Use Docker directly to run with your own database

Use this process if you're a developer and want complete control over
your Monica container.

If you aren't using docker-compose, edit `.env` again to set the `DB_*` variables to match your database. Then run:

```sh
scripts/docker/build.sh
```

You can add the tag name as a parameter:
```sh
scripts/docker/build.sh monica-dev
```

Run monica with:
```sh
docker run --env-file .env -p 80:80 monica-dev
```

Or run a command in the container:
```sh
docker run --env-file .env -it monica-dev bash
```

There's a bunch of [docker-compose examples here.](https://github.com/monicahq/docker/tree/master/.examples)

Note that uploaded files, like avatars, will disappear when you
restart the container. Map a volume to
`/var/www/monica/storage/app/public` if you want that data to persist
between runs. See `docker-compose.yml` for examples.

## Other documents to read

[Connecting to MySQL inside of a Docker container](/docs/installation/docker-mysql.md)
[Use mobile app with standalone server](/docs/installation/mobile.md)
