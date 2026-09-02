# Quickstart: Time zone picker

How to see it working, and how to prove it. Requirements in [spec.md](spec.md), decisions in [research.md](research.md).

## Prerequisites

```bash
composer install && bun install
php artisan migrate:fresh --seed          # users.timezone already exists, no new migration
composer run dev                          # or: bun run dev
```

Sign in, then open `/settings/account/preferences`. The zone reads `UTC` on a fresh account.

## The three ways in

| Path | What to do | What should happen |
| --- | --- | --- |
| Search | Type `Lisbon`, press Enter | The zone reads `Europe/Lisbon`, a notification appears, the panel clock shows the Lisbon time, and the marker moves |
| Map | Click over Japan | The zone reads `Asia/Tokyo`, a notification appears, and the panel says the nearest one was taken |
| Device | Press "Use my device" | The zone becomes whatever `Intl.DateTimeFormat().resolvedOptions().timeZone` reports |

Reload after each: the zone has to survive it, being stored rather than held in the page.

## What to check by hand

- **Keyboard alone.** Tab to the field, type, arrow through the matches, Enter to choose, Escape to
  close. Never reach for the mouse. FR-004 and SC-004.
- **Keyboard, no pointer.** Reach and save a zone without the map. The results are real submit
  buttons, so Enter and Space are the browser's own rather than handlers.
- **Both themes.** Water, land, the hour band and the marker stay legible, and the marker is
  findable without its colour. **Zoomed to 200%**, panel, results and map all stay usable.
- **Nothing else moved.** Note the language, clock, date order and number separators before saving a
  zone, and check all four after. FR-011, and research.md section 5 says why it is worth checking.

## The API

```bash
TOKEN=...   # settings > security > API keys
curl -sS -X PUT https://monica.test/api/v1/me/timezone \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -d 'timezone=Europe/Madrid'
curl -sS https://monica.test/api/v1/me -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

The first returns the user resource with the new zone and the second confirms it stored. Sending
`timezone=Nowhere/Here` comes back 422, old zone untouched.

## The suite

```bash
php artisan test --compact --filter=Timezone
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Preferences
php artisan test --compact tests/Unit/ViewModels/Settings/Account/Preferences
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

The last one has to pass in all nine locales before the change is done.
