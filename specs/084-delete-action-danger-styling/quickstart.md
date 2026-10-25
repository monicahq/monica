# Quickstart: Delete actions drawn as danger

## Checks that must pass

```bash
php artisan view:clear
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

## Look at it, in the light theme and then the dark one

Sign in with a seeded account (`composer run dev`).

| # | Screen | Expect |
| --- | --- | --- |
| 1 | A vault's settings | "Delete this vault": red box, solid red button, as before |
| 2 | Edit a contact | "Delete contact": red box, solid red button |
| 3 | Edit a contact the account may not delete | Same red box, button dimmed, reason read with it |
| 4 | Edit a template | "Delete the template" in its own red box below the form; the foot holds only Cancel and Save |
| 5 | Edit an entity type with nothing filed, then one with records | Red box, solid red button; then dimmed with its reason |
| 6 | The same for a relationship type and a life event type | Same as 5 |
| 7 | A value list | Every "Delete" red at rest, not filled |
| 8 | A template's builder | "Remove" on a section and a page, "Delete the draft": red text at rest |
| 9 | Admin, accounts and flags | "Deactivate", "Withdraw", "Deactivate for every account" still solid red |
| 10 | Any button from 1 to 6 | Opens the warning card; nothing is deleted before confirming |

## Accessibility

- Tab to each solid red button: the focus ring is visible against red.
- Check the red text, the red heading and the white label on red with a contrast tool: 4.5:1 or more
  in both themes (FR-011).
- With a screen reader, a refused button is announced as unavailable, followed by its reason.
