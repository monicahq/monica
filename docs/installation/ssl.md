# Using monica with HTTPS

When Monica is run with `APP_ENV=production`, it is required that Monica is running
with HTTPS. In order to satisfy this requirement, some additional configuration
needs to be performed.

## Local Installation

If you have Monica installed locally, and have HTTPS set up on your Apache server,
the only configuration required for Monica to support HTTPS is to set your `APP_URL`
to start with `https://`. This configuration parameter is used to generate external
links to your application for emails and such.

## With a proxy

Monica uses the [fideloper/proxy](https://packagist.org/packages/fideloper/proxy)
package to configure support *trusted proxies*. When enabled, Monica will trust
incoming headers like X-Forwarded-For, X-Forwarded-Host and X-Forwarded-Proto in
order to dynamically determine the setup of your application.

You can configure this in your `.env` file:

``` bash
# Set trusted proxy IP addresses.
# To trust all proxies that connect directly to your server, use a "*".
# To trust one or more specific proxies that connect directly to your server, use a comma separated list of IP addresses.
APP_TRUSTED_PROXIES=

# Enable automatic cloudflare trusted proxy discover
APP_TRUSTED_CLOUDFLARE=false
```

Make sure that whatever proxy you are using is in your `APP_TRUSTED_PROXIES` list.
If you use Cloudflare, you can also simply set `APP_TRUSTED_CLOUDFLARE` to true to
automatically add cloudflare's IP addresses to the list.

If you fail to have `APP_TRUSTED_PROXIES` set correctly, Monica will generate internal links that
have the wrong protocol or host on them. This might seem to work if you have redirects set up,
but can fail with insecure form submission errors.

Remember to also update your `APP_URL` to correctly point to the HTTPS version of your application.

### Example: Docker Compose

If you are already using a dockerized version of Monica, you can use a Dockerized nginx
configuration to perform TLS termination.

For example, you could use an `nginx.conf` similar to:

``` nginx.conf
error_log stderr;
events { worker_connections 1024; }

http {
  server {
    listen 443 ssl;
    server_name monica.example.com;
    ssl_certificate /https-cert.pem;
    ssl_certificate_key /https-key.pem;
    ssl_protocols TLSv1.2;

    location / {
        proxy_pass http://monica:80;
        proxy_set_header Host monica.example.com;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
  }
}
```

And a `docker-compose.yml` like:

``` yaml
version: '3'
services:
  monica:
    container_name: monica
    expose:
      - 80
    volumes:
      - '/var/monica-storage:/var/www/monica/storage'
    image: monicahq/monicahq
    env_file: /etc/monica/monica.env
    restart: unless-stopped

  nginx:
    container_name: nginx
    volumes:
      - '/etc/monica/nginx.conf:/etc/nginx/nginx.conf:ro'
      - '/etc/monica/https-cert.pem:/https-cert.pem:ro'
      - '/etc/monica/https-key.pem:/https-key.pem:ro'
    ports:
      - '443:443'
    image: nginx:latest
    depends_on:
      - monica
    restart: unless-stopped
```

You would also need to set `APP_TRUSTED_PROXIES=*` in your monica environment.
