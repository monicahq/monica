# Build Docker image for Monica

If you want to build your own docker image for Monica, follow these steps:

## Use docker-compose to build and run your own image

Use this process if you want to modify Monica source code and build
your image to run.

Edit `.env` to set `DB_HOST=mysql` (as `mysql` is the creative name of the MySQL container).

Then run:

```sh
docker-compose build
docker-compose up
```

## Use Docker directly to run with your own database

Use this process if you're a developer and want complete control over
your Monica container.

If you aren't using docker-compose, edit `.env` again to set the `DB_*` variables to match your database. Then run:

```sh
# assets are copied from the host machine, make sure they are built
yarn install && yarn run production

docker build -t monicahq/monicahq -f scripts/docker/Dockerfile .

docker run --env-file .env -p 80:80 monicahq/monicahq  # to run MonicaHQ

# ...or...
docker run --env-file .env -it monicahq/monicahq sh    # to get a prompt
```

There's a bunch of [docker-compose examples here.](https://github.com/monicahq/docker/tree/master/.examples)

Note that uploaded files, like avatars, will disappear when you
restart the container. Map a volume to
`/var/www/monica/storage/app/public` if you want that data to persist
between runs. See `docker-compose.yml` for examples.

## Other documents to read

[Connecting to MySQL inside of a Docker container](/docs/installation/docker-mysql.md)
[Use mobile app with standalone server](/docs/installation/mobile.md)
