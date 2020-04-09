# Docker examples for Monica

In this section you will find some examples about how to use monica's docker images.

Example|Description
-------|-----------
[`supervisor`](supervisor)| uses supervisor to run a cron and a queue inside your container.
[`nginx-proxy-self-signed-ssl`](nginx-proxy-self-signed-ssl)| shows you how to run monica with a self signed ssl certificate.
[`nginx-proxy`](nginx-proxy)| shows you how to run monica with https and generate a [Let's Encrypt](https://letsencrypt.org/) certificate.


## Run with docker-compose

### Configuration (all versions)

First, download a copy of Monica example configuration file:

```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/.env.example -o .env
```

Open the file in an editor and update it for your own needs:

- Set `APP_KEY` to a random 32-character string. For example, if you
  have the `pwgen` utility installed, you could copy and paste the
  output of `pwgen -s 32 1`.
- Edit the `MAIL_*` settings to point to your own [mailserver](/docs/installation/mail.md).
- Set `DB_*` settings to point to your database configuration. If you don't want to set a db prefix, be careful to set `DB_PREFIX=` and not `DB_PREFIX=''` as docker will not expand this as an empty string.
- Set `DB_HOST=db` or any name of the database container you will link to.


### With supervisor

The [`supervisor`](supervisor) examples shows you how to run monica with
- a db container (mysql:5.7)
- an app container, which run `supervisord` to handle a web server/fpm, a cron, and a queue.

This let you use `QUEUE_CONNECTION=database` in your `.env` file.


### With nginx proxy and a self-signed certificate

[`nginx-proxy-self-signed-ssl`](nginx-proxy-self-signed-ssl) example shows you how to run monica with a self signed ssl certificate, to run the application in `https` mode.

Set `VIRTUAL_HOST` and `SSL_SUBJECT` with the right domain name, and update `SSL_KEY`, `SSL_CSR`, and `SSL_CERT` accordingly.
This example generates a new self-signed certificate.

Your browser might warn you about security issue, as a self-signed certificate is not trusted in production mode. For a real domain certificate, see the next section.


### With nginx proxy and a Let's Encrypt certificate

[`nginx-proxy`](nginx-proxy) example shows you how to run monica and generate a [Let's Encrypt](https://letsencrypt.org/) certificate for your domain.

Don't forget to set:
- `VIRTUAL_HOST` and `LETSENCRYPT_HOST` with your domain
- `LETSENCRYPT_EMAIL` with a valid email
- `APP_URL` in your `.env` file with the right domain url

You may want to set `APP_ENV=production` to force the use of `https` mode.

This example add a `redis` container, that can be used too, adding these variables to your `.env` file:
- `REDIS_HOST=redis`: mandatory
- `CACHE_DRIVER=redis`: to use redis as a cache table
- `QUEUE_CONNECTION=redis`: to use redis as a queue table
