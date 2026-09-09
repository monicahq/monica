# Quickstart: proving the magic link setting works

## Prerequisites

This worktree has no `vendor/`. Run `composer install` first. The routes, the responses and the
setting are in [contracts/magic-link-setting.md](contracts/magic-link-setting.md).

## Verification

```bash
composer remove spatie/laravel-login-link        # once nothing references it any more
composer why spatie/laravel-package-tools        # expected: not required by anything
php artisan route:list --name=magicLink          # three routes, each carrying magic.link
php artisan route:list --path=laravel-login-link # expected: no route
php artisan test --compact tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php
php artisan test --compact tests/Feature/Controllers/App/Auth/LoginControllerTest.php
php artisan test --compact tests/Unit/ViewModels/Auth/LoginViewModelTest.php
php artisan test --compact tests/Feature/Auth tests/Feature/Views/Layouts/GuestScreenTest.php
php artisan monica:localize --check              # expected: nothing missing, nothing stale
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

By hand, in a browser:

- With `MONICA_MAGIC_LINK_ENABLED` absent from `.env`: the sign in screen shows "Send me a link
  instead", the request screen opens, an existing address gets "Check your inbox", an unknown address
  gets the same page, and the emailed link signs in.
- Set `MONICA_MAGIC_LINK_ENABLED=false`, then `php artisan config:clear` if the config is cached: the
  box is gone from the sign in screen, `/send-magic-link` answers 404 on GET and POST, and a link
  emailed before the change answers 404 and stays unused in the `magic_links` table.
- Set it to `0`: off. Set it to `maybe`: on. Remove it: on.
- On every screen, `Sign in as` appears nowhere, and `POST /laravel-login-link-login` answers 404.
- The example account is still seeded by `php artisan db:seed`, and nothing on the sign in screen
  refers to it.

## What failure looks like

- `Route [auth.magicLink.show] not defined` from a queued email means the routes were registered
  conditionally rather than gated by the middleware.
- 403 on a link with a bad signature while the setting is off means `signed` ran before `magic.link`.
- `Class "Spatie\LoginLink\Http\Controllers\LoginLinkController" not found` means
  `config/login-link.php` is still there.
- `Send me a link instead` visible while the setting is off means the view still links to the route
  without asking `LoginViewModel::magicLinkIsEnabled()`.
- `monica:localize --check` reporting a stale key means a translated string was removed. None should
  be: the shortcut's label was never translated.
