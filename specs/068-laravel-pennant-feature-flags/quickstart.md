# Quickstart: Feature flags

How to check the feature end to end. Interfaces are in [contracts/flags.md](contracts/flags.md).

## Setup

```bash
composer install
php artisan migrate
```

## The marketplace (the flag this change ships with)

1. `/marketplace` answers not found, signed in or not, and no screen links to it.
2. `php artisan monica:switch-flag marketplace on --every-account`: the page answers everybody,
   visitors included, and the top bar and the share screens offer it again.

## Nothing declared (after `Feature::forgetDrivers()` in a test)

1. `php artisan monica:list-flags` says no flag is declared and exits 0.
2. Signed in as an administrator, `/admin/flags` says the same, and the account screen shows no flag panel.

## With a flag declared

Copy `tests/Fixtures/Features/ExampleCapability.php` into `app/Features/`, changing its namespace to
`App\Features`. Delete it when done.

| Step | Expect |
| --- | --- |
| `php artisan monica:list-flags` | `example-capability`, off for every account |
| `php artisan monica:switch-flag example-capability on --email=jim@example.com` | Exit 0; Jim's account screen shows it on; the record shows it, by the console |
| Same, `off --every-account` | Exit 0; off for Jim too; one more line in the record |
| `/admin/flags`, activate for every account | Asks to confirm, then the password; the flag reads on |
| Create a new account, open its admin screen | The flag reads on (clarification Q3) |
| `php artisan monica:switch-flag nonexistent on --every-account` | Exit 1, one line naming the unknown flag |

## Retiring a flag

Delete the class, then `php artisan pennant:purge --except-registered`: the `features` table holds no
row named `example-capability`.

## Gates

```bash
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```
