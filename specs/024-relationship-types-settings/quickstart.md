# Quickstart

## Prerequisites

The application runs under Herd at `https://monica.test`. Run `bun run dev` if the assets are not built.

## Set it up

```bash
php artisan migrate
php artisan test --compact tests/Unit/Actions/CreateDefaultRelationshipTypesTest.php
```

The migrations create both tables. The five categories and ten types are given to an account when it is created, so sign up rather than expecting an existing account to grow them.

## See it work

1. Sign in and open `settings/account/relationship-types`.
2. Expect five categories, ten types under the first four, Other empty and offering to add a type.
3. Open Parent / Child. Expect the supplied badge, a locked direction with its reason, and the family tree panel. Rename a role and save; the row reads with the new word and keeps its badge.
4. Add a type. Expect the preview to read two sentences as you type, and the saved type to appear at the end of its category with no badge.
5. Turn off a type. Expect the not offered badge on its row.
6. Delete the type you added. Expect a confirmation, then the row gone.
7. Add a category, rename it, move it above another with the up button, then delete it while it is empty.
8. Try to delete a category holding a type. Expect a refusal naming how many it holds.

## Check it before calling it done

```bash
php artisan test --compact tests/Unit/Actions tests/Unit/Models/RelationshipTypeTest.php tests/Feature/Controllers/App/Settings/Account/RelationshipTypes
php artisan monica:localize
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G
```

`monica:localize` must report nothing missing in any of the nine locales, and `--check` must pass on the counted phrases.

## Check the screen

Tab through the list with no mouse: every row must be reachable, and the up and down buttons must move a category and a type. Zoom to 200%. Switch to dark. Confirm the supplied and not offered badges each carry a word, not only a colour.
