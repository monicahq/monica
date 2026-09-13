# Quickstart: Add a relationship

How to see this working and what has to be green before it is done.

## Prerequisites

```bash
composer install && bun install
php artisan migrate:fresh --seed   # relationships is a new table; the seeder supplies the 10 types
bun run dev                        # or composer run dev
```

Sign in, open a vault, open any contact, and follow the **Relationships** tab. Where that tab used to say
"once relationships exist", there is now a way in to the form.

## Walk the four stories

| Story | What to do | What proves it |
| --- | --- | --- |
| 1 | Choose Parent / Child, pick somebody already in the vault, save | One row in `relationships`, the viewed contact in `contact_a_id` |
| 2 | Choose a type, say the name is known, type a first name, leave the offer unchecked, save | A new `contacts` row with `is_partial = 1`, absent from the people list, reachable at its own page |
| 3 | Save twice saying the name is unknown | Two `contacts` rows and two `relationships` rows, not one of each |
| 4 | Choose Parent / Child, swap, save | The viewed contact lands in `contact_b_id` |

Worth checking by hand, because no test sees it: choose a symmetric type and confirm the swap is
unavailable; type into the search after choosing somebody and confirm the choice clears; turn on reduced
motion and confirm each reveal is instant rather than quick.

## Refusals

Each of these must leave `relationships` untouched and say why on the form:

- the same contact on both sides;
- a contact reached through another vault of the same account, or another account entirely, which 404s;
- Parent / Child where the proposed child is already an ancestor of the proposed parent;
- a relationship type that was deactivated after the form was opened.

## What must be green

```bash
php artisan test --compact --filter=Relationship
php artisan test --compact                      # a shared component and RelationshipType change under it
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse                      # level 5, CI gate
php artisan monica:localize --check             # counted phrases and per-locale typography
```

`php artisan monica:localize` then translating everything it reports, in all 31 locales, is a delivery
condition rather than a follow-up. So is a keyboard-only pass over the form and a screen-reader pass over
the three-way choice and the `aria-live` preview.

Expect `tests/Unit/Models/RelationshipTypeTest.php` to fail until its `relationshipCount()` assertion is
rewritten. That failure is the point, not a regression: see [research.md](research.md).
