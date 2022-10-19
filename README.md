## Run locally

1. `composer install`
1. `cp .env.example .env` and configure `.env` file
1. `yarn install`
1. `yarn run dev`
1. Optional: make the search work:
   1. Install and run [meilisearch](https://www.meilisearch.com/) locally
   1. Configure and run a queue (`php artisan queue:listen --queue=high,low,default`)

## Configuring search

Search in production is done with Meilisearch. You can use other drivers if you want to.

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


### Build it

You can also build your image locally with:

```sh
docker build -t monica-next -f scripts/docker/Dockerfile .
```
