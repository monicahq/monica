# Quickstart: Instance Configuration

How to prove the feature works end to end. Entities are in [data-model.md](data-model.md).

## Prerequisites

```bash
composer install && bun install && bun run build
php artisan migrate --seed
```

Make yourself an administrator, then sign in.

```bash
php artisan tinker --execute 'App\Models\Administrator::factory()->create(["user_id" => App\Models\User::query()->first()->id]);'
```

## Scenario 1: a change takes effect with no restart (FR-003, SC-001)

1. Open `/admin/configuration`. Every setting shows its value and the value the deployment supplied.
2. Lower the free contact limit to 1 and save. You are asked for your password first, and only once for the window.
3. As an ordinary user, add a second contact to a vault. It is refused by the new limit.
4. Revert the setting. The deployment value governs again and the control offers nothing to revert.

## Scenario 2: the record holds every change (FR-006, SC-003)

Open `/admin/logs`. Both the change and the reversion are listed against your name with the value before and after. Change a secret, and the entry says it changed without saying to what.

## Scenario 3: a secret is never read back

Save a mail password. The field reports the secret as set and never shows it.

## Scenario 4: a queue worker obeys within a minute (FR-003)

```bash
php artisan queue:work --queue=high,default,low
```

Leave it running. Change a setting a queued job reads, wait a minute, dispatch that job, and confirm it used the new value without the worker being restarted.

## Scenario 5: the compartments hold (FR-001)

A visitor, a regular user and a deactivated administrator all get not found on `/admin/configuration`, over each HTTP verb.

## Before it is done

```bash
php artisan monica:localize
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan test --compact
```

Translate everything `monica:localize` reports, in every configured locale. Check the screen with the keyboard alone, with a screen reader, and zoomed to 200%.
