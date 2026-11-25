# Quickstart: validating the group page

## Prerequisites

The checkout served by Herd at `monica.test`, `groups` and `file-upload` switched on for the account
used (`php artisan monica:switch-flag groups on --email=...`). Never migrate the local database;
the test suite migrates its own.

## Automated

```bash
php artisan test --compact tests/Unit/Actions --filter='Group|LifeEvent|Reminder'
php artisan test --compact tests/Unit/ViewModels/Vaults --filter='Group|ContactTimeline|Reminder'
php artisan test --compact tests/Feature/Controllers/App/Vaults --filter=Group
php artisan test --compact tests/Feature/Controllers/Api --filter=Reminder
php artisan test --compact tests/Feature/RouteProtectionTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

| # | Do | Expect |
| --- | --- | --- |
| 1 | Open a group with seven members and a start date | Avatars, "7 people belong to it", "Since …", three tabs |
| 2 | Record an activity, untick two members | Top of the history, "5 of 7 were there"; on five timelines, not on the other two |
| 3 | Open one attendee's page | The activity names the group and links to it |
| 4 | Write a note | In the history as a note; on no member's page |
| 5 | Add a yearly reminder | In the panel marked as repeating, and in the vault's reminders list naming the group |
| 6 | Delete the group, then restore it | Reminders deactivated, then back; history and files back |
| 7 | Add a file over the storage limit | The usual refusal |
| 8 | Switch `file-upload` off for the account | Files panel and tab absent; one sentence saying the files are kept |
| 9 | List reminders through the API | No group reminder in the response |
| 10 | Whole page by keyboard at 390px | Every control reachable; tabs, radio group and checkboxes announced |
