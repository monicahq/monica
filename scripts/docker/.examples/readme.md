# Docker examples for Monica

In this section you will find some examples about how to use monica's docker images.

## Supervisor

The [`supervisor`](supervisor) examples shows you how to run monica with
- a db container (mysql:5.7)
- an app container, which run `supervisord` to handle a web server/fpm, a cron, and a queue.

This let you use `QUEUE_DRIVER=database` in your `.env` file.


## Nginx proxy with a self-signed certificate

[`nginx-proxy-self-signed-ssl`](nginx-proxy-self-signed-ssl) shows you how to run monica with a self signed ssl certificate.
  
Set `VIRTUAL_HOST` and `SSL_SUBJECT` with the right domain, and update `SSL_KEY`, `SSL_CSR`, and `SSL_CERT` accordingly.
As this generates a new self-signed certificate, it might not be usefull in production mode.


## Nginx proxy with Let's Encrypt certificate

[`nginx-proxy`](nginx-proxy) run monica and generates a Let's Encrypt certificate.

- Set `VIRTUAL_HOST` and `LETSENCRYPT_HOST` with your domain
- Set `LETSENCRYPT_EMAIL` with a valid email
  