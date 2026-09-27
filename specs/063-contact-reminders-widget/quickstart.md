# Quickstart: Reminder answers

## Setup

The schema is edited in place, so an existing database cannot be migrated forward:

```bash
php artisan migrate:fresh --seed
```

Sign in to the example account. Freeze the clock where a scenario needs a date (tests use
`$this->travelTo()`).

## Scenarios

| # | Do | Expect |
| --- | --- | --- |
| 1 | Open a contact with a reminder due today | Today group; only I remembered offered, also after its email went out |
| 2 | Travel one day ahead, reload | Overdue badge; I remembered and I forgot offered |
| 3 | Press I forgot | Row leaves Overdue at once, no dialog; History shows "You forgot", due and answered dates |
| 4 | Send I remembered for the same deadline from a second tab | Refused with the "already answered" wording; nothing changes |
| 5 | Answer a monthly reminder | Next month's date is in Upcoming, unanswered |
| 6 | Add a manual reminder dated today, send time already passed, cadence once | Refused (FR-023) |
| 7 | Delete a reminder with an overdue deadline | Warned first; deadline gone from every list; past answers still in History |
| 8 | Archive the contact | Its Today and Overdue deadlines leave the lists (FR-025) |
| 9 | Look for Dismiss, Remind me later, Pause, "7 days before" anywhere | None |
| 10 | Set quiet hours 22:00 to 10:00, deadline at 23:00 | Notification arrives the next morning unless answered first (FR-024) |
| 11 | API: `PUT .../reminder-occurrences/{uuid}/answer` with `forgotten` on a deadline due tomorrow | 422 |
| 12 | Walk the contact's Reminders screen with the keyboard and VoiceOver | Filters announce the selected one; chart read as text; every answer reachable |

## Checks

```bash
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

The whole suite, not the reminder tests alone: moments and statuses reach about seventy test files
and the CalDAV calendar.
