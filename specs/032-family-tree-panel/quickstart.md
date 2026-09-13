# Quickstart: Family tree panel

How to see the feature working and prove it does. Requirements are in [spec.md](spec.md); decisions in [research.md](research.md).

## Prerequisites

```bash
composer install && bun install
php artisan migrate:fresh --seed
bun run dev          # or composer run dev
```

The seed gives an account with a vault of contacts and the supplied relationship types, of which
Parent / Child carries the parentage behaviour and Spouse and Partner carry the romantic one.

## Walk it

1. Sign in, open a vault, open a contact. Through **Add a relationship**, record a **Parent**, a
   **Spouse** and two **Children**. Make one child a name only rather than an existing person.
2. Open the **Relationships** tab. The **Family tree** panel stands beside the groups, titled after
   the contact.
3. Read the drawing. The parent sits above, the contact and the spouse side by side, the two children
   below. The contact is marked out from the rest. The name-only child is drawn with a dashed edge
   and leads nowhere; the others open their pages.
4. Read the line under the panel. It says friends and colleagues stay in the list.
5. Record a **Friend**. They appear in the groups on the left and nowhere in the drawing.
6. Mark the spouse relationship **ended** through the group's Manage control. They leave the drawing.
   Mark a parentage ended: that person stays, because ending contact does not undo parentage.

## Prove the edges

| What to do | What must happen |
| --- | --- |
| Record seven children | All seven are drawn. The panel keeps its size and the drawing scrolls sideways. |
| Tab through the page | The drawing takes focus as a region, then each person with a profile in turn. |
| Open a contact with no parent, partner or child | The panel says there is no line yet and offers a way to write one down. |
| Narrow the window below `xl` | The panel drops under the groups and still works. |
| Open the **Overview** tab | The Immediate family panel is unchanged. |
| Rename Parent / Child in settings, then reload | The drawing is identical. Membership follows the behaviour, not the name. |
| Sign in as another account and request the same URL | Not found, as though the contact did not exist. |

## Prove it under test

```bash
php artisan test --compact --filter=FamilyTree
php artisan test --compact tests/Unit/Models/ContactTest.php
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G
php artisan monica:localize --check
```

The last is the gate that fails on an untranslated string and on a counted phrase written the wrong way. Run `php artisan monica:localize` first and translate everything it reports, in every locale.

## Count the queries

With the family of step 1 recorded, the page must ask the records the same number of times as it
does with seven children. Watch the query log while loading both, or assert it in the view model
test. Three is the number, one per tier, and it does not move.
