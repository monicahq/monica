# Quickstart: Confirmation when a contact is marked a favourite

## Prerequisites

```bash
composer install && bun install
php artisan migrate --seed
composer run dev
```

## See it work

1. Open any vault, open a contact, and find the star medallion at the corner of the avatar.
2. Press it. The star fills, the ring around the avatar turns, and a card appears at the bottom
   right saying the person is a favourite. It counts down and leaves on its own.
3. Press it again. The star empties and a card says they are no longer a favourite.
4. Press it five times quickly. One card is showing at the end, and it matches the star.
5. Move to another page. No card follows you there.

## Check the paths that are easy to miss

| Path | How | Expected |
| --- | --- | --- |
| No scripting | Disable JavaScript, reload, press the star | Full page reload, same card, same wording |
| Screen reader | VoiceOver on the contact page, press the star | The card is announced politely, focus stays on the star |
| Reduced motion | System setting on, press the star | The card appears without the entry animation |
| Another account | Sign in as a second account, `PUT` the favourite route for the first account's contact | 404, and no card |
| A long name | A contact with a very long name | The card wraps and the page does not scroll sideways |

## Prove it with tests

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
```

Both directions assert `assertSessionHas('status', ...)` and `assertSessionHas('status_description', ...)`.
The two tests that asserted the old silence are rewritten, not deleted; see
[research.md](research.md), Decision 5.

## Before it is done

```bash
php artisan monica:localize          # translate everything it reports, in all nine locales
php artisan monica:localize --check  # typography and counted-phrase rules
vendor/bin/pint --format agent
vendor/bin/phpstan analyse
php artisan test --compact
```
