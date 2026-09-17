# Quickstart

## Prerequisites

```bash
composer install && bun install
php artisan migrate:fresh --seed --quiet
bun run build
```

An account created by the seeder already holds the eight supplied categories and no type, which is
the state both screens are specified against.

## See it work

1. Sign in, open **Settings**, then **Activity types** in the *This account* group.
2. The rail lists the eight categories, each showing `0`, with *Every type* above them. The panel
   says the category holds nothing and offers to add a type.
3. Press **Add a category**. The modal asks for a name alone. Save an empty one: it is refused and
   the modal stays open. Save `Music and choir`: it lands last and is selected.
4. Rename it in place, move it up with the control next to it, then switch it off and back on,
   reading what the screen says before the switch off runs.
5. Press **Add a type**. Give it a name and a category, pick a mark, and save. It appears last in
   that category and the rail count moves from `0` to `1`.
6. Open it again, move it to another category, and confirm both counts move.
7. From the type, open **Custom fields**, add a section, add a field to it, and publish. Reopen the
   type: it reports its fields.
8. Delete the type: it asks first and says the deletion cannot be undone. Delete the category while
   it still holds a type: refused, with what to do instead.

## Prove it

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/ActivityTypes
php artisan test --compact tests/Unit/Actions --filter=ActivityType
php artisan test --compact tests/Unit/Policies
php artisan test --compact                      # the whole suite: shared components move screens
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G
php artisan monica:localize --check
```

`monica:localize --check` is the gate that matters most here, because the screens carry counted
phrases (how many types a category holds, how many leave when it is switched off) and it refuses a
phrase that chooses its wording by comparing against one.

## What proves each story

| Story | Proved by |
| --- | --- |
| 1, read the vocabulary | steps 1 and 2, and the controller tests for another account's records |
| 2, add and edit a type | steps 5 and 6 |
| 3, manage the categories | steps 3, 4 and 8 |
| 4, custom fields | step 7, and that no screen in the walk shows template, structure or version |
