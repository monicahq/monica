<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Requirements

PHP 8.4 or later, with the `intl` extension. The extension is required rather than optional:
lists of names are ordered and searched in the alphabet of whoever is reading them, and the
alphabet rules come from it. Without it `composer install` refuses, which is deliberate. An
installation that started without it would order names in a way no reader could rely on, and
say nothing about why.

The official `php:8.4` images do not enable it by default:

```
docker-php-ext-install intl
```

Check it with `php -m | grep intl`.

## Development in Docker

`compose.yaml` describes a development environment: the application behind Apache, a queue worker
and a MariaDB database. It serves the checkout it mounts rather than a copy of its own, so that
checkout has to carry its own `.env`, `vendor` and `node_modules` first. `composer setup` writes
all three.

```
docker compose up
```

| Address | What answers there |
| --- | --- |
| `http://localhost:8000` | The application, served by Apache out of `public/` |
| `http://localhost:8000/up` | The health route, which answers only once the database does |
| `localhost:3306` | The database, for a client outside the container |

The stack runs three services. `app` is Apache and nothing else, `queue` is the one process
development adds, and `database` is what the application talks to. Apache has to be the only
process of its container: its log files are symbolic links to `/dev/stderr`, and a process
whose standard error is a pipe cannot open that, so running it under a supervisor fails with
`AH00015` before it serves anything.

No Vite dev server runs in the stack. The entrypoint builds the assets once at every start, so a
page has a manifest to read, and a change to the CSS or the JavaScript needs either a restart or
`docker compose exec app bun run build`. For a dev server and its hot reloading, run `bun run dev`
outside the container: it writes `public/hot`, which every view then follows in preference to the
built files. Delete that file when you stop it, since a page follows it to a dev server that is no
longer listening.

Run anything else inside the stack with `docker compose exec`:

```
docker compose exec app php artisan test
docker compose exec app php artisan tinker
docker compose exec app php artisan pail
```

The suite is unaffected by any of this. `phpunit.xml` asks for the `testing` connection, which is
SQLite in memory, so the database you develop on is never touched.

### What the image holds, and what the stack adds

`docker build` produces an image that serves on its own. It installs the PHP and the JavaScript
dependencies, builds the assets with Vite, configures Apache to serve `public/`, and ships
`.env.docker` as its `.env`.

Composer runs there with `--no-dev`, so the image carries what the application needs to serve and
nothing else. PHPUnit, Pint, PHPStan and the Telegram, Vonage and Microsoft integrations are all
development dependencies of this project and are absent from it. The checkout the stack mounts is
where they come back, which is what `docker compose exec app php artisan test` runs on.

Apache answers on 8000 rather than 80. That is what lets it run as the same user that owns the
files it serves: a port below 1024 needs privileges that user does not have, and granting them
would put root owned files back into your checkout.

`.env.docker` is what the image runs on. It names SQLite as the database, so a container serves
with nothing else beside it, and the entrypoint creates that file on first start. It holds no key
and no secret: `APP_KEY` is empty and is generated on first start. To put the image on another
database, pass `DB_CONNECTION` and its credentials to `docker run`, the way `compose.yaml` does for
development.

`compose.yaml` mounts the checkout over that copy, which is what makes an edit visible without a
rebuild. The mount also hides everything the image installed, which is why the checkout has to
carry its own. `docker-entrypoint.sh` installs none of it: it builds the assets, generates a key
where `APP_KEY` names none, waits for the database to answer and runs `php artisan monica:setup`.
Every step of it is safe to repeat, so it runs on every start.

`monica:setup` is the command that brings an installation to a state it can serve from. It links
the storage folder into public, clears the application cache, resets the compiled configuration,
routes, views and events, and migrates. In production it compiles those files instead of clearing
them, and caches the configuration. `--build` runs only the steps that prepare the checkout and
`--deploy` only the step that touches the database, so a deployment can split the two.

The entrypoint calls it twice, `--deploy` before `--build`. Caching the configuration is refused
while the cache table is missing, and on a database nobody has migrated yet that table only appears
once the migrations have run. Run in the command's own order, the first start of a fresh
installation would serve its whole life with the configuration uncached.

The image does not run it at build time. In production the command caches the configuration, and a
configuration cached from `.env.docker` would ignore every value passed to `docker run` afterwards.
The entrypoint runs it once the environment is settled instead.

### The health route

`/up` is where Laravel reports whether an installation is serving, and an installation that cannot
reach its database is not. `App\Listeners\CheckDatabaseIsReady` listens for `DiagnosingHealth` and
runs a query there, so the route answers 500 while the database is away rather than claiming a
health the application does not have.

`compose.yaml` polls it as the health check of the app service, and `queue` waits for it before it
starts. That is what keeps it from running against a checkout whose dependencies the app container
has not finished installing, and it is why only that one container runs the entrypoint: `queue`
steps over it with an emptied `entrypoint`.

### Three things worth knowing

The container writes `public/build`, `storage` and `bootstrap/cache` into the checkout as the user
1000:1000. Where your own identifiers differ, build with `USER_ID` and `GROUP_ID` set:
`USER_ID=$(id -u) GROUP_ID=$(id -g) docker compose up --build`.

The `.env` of the checkout is read, never written. For the application to reach the stack, it has
to name the database service: `DB_CONNECTION=mariadb`, `DB_HOST=database`, `DB_DATABASE=monica`,
`DB_USERNAME=monica` and `DB_PASSWORD=secret`.

Where the host already answers on 3306, set `DATABASE_PORT_ON_HOST`. Port 8000 is fixed, because
`APP_URL` is written for it.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
