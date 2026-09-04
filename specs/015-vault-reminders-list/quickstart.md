# Quickstart: Vault reminders list

How to prove the screen works. Requirements are in [spec.md](spec.md); routes and file layout in [plan.md](plan.md).

## Prerequisites

```bash
composer install && bun install && bun run build
php artisan migrate
```

A signed-in user, a vault, and contacts carrying date fields. `ReminderFactory` and `ReminderOccurrenceFactory` already exist.

## Seed a vault worth looking at

Occurrences are generated, not written by hand. Create rules on date values, then run generation, which is what the daily job calls:

```bash
php artisan tinker --execute 'App\Jobs\GenerateDueReminderOccurrences::dispatchSync();'
```

To cover all five buckets, the rules need source dates falling in the past, inside seven days, later in the month, next month, and beyond. To cover the horizon case, add one yearly rule whose date is eight months away and confirm it produces nothing.

## Run the tests

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact --filter=Reminder
```

## Check by hand

| Step | Expected |
| --- | --- |
| Open any vault screen | The sidebar Reminders entry is a link and carries a count |
| Follow it | Buckets Overdue, This week, Later this month, Next month, Later, in that order, empty ones absent |
| Read the heading | It names the vault and states the 90 day horizon truthfully |
| Switch grouping to Contact | Same rows under contact names, alphabetical, no overdue tint |
| Hover then Tab to a relative date | The exact instant, its zone and its offset appear both times |
| Tab through one row | Four controls, each announcing its own name, each showing its description on focus |
| Postpone with a preset | The sentence names the resulting instant; confirming moves that row and no other |
| Postpone to a past instant | Refused with a readable message, dialog still open, input intact |
| Dismiss | The row leaves; the rule stays active; next cycle still scheduled |
| Send now | A notification arrives immediately; the row is unchanged; nothing appears on the Sent tab |
| Open the Sent tab | Newest first, with sent time, channel, contact, text and an outcome that never says "completed" |
| Empty the vault of near reminders | The empty state distinguishes none at all from none inside 90 days |

## Gates before review

```bash
vendor/bin/pint --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

PHPStan level 5 is a CI gate that the `CLAUDE.md` checklist omits, so run it. `monica:localize` must be run and its missing keys translated in all nine locales before the check passes.

## Accessibility pass

Keyboard only, no mouse: reach and operate every control, open and close the dialog with focus returning to its button, confirm Escape closes it. Then check the page at 200% zoom, with reduced motion enabled, and with the Overdue bucket's colours removed to confirm lateness is still visible.
