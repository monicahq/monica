# Quickstart: proving a reminder actually fires

The suite is the proof. This is the order to run it in, and the one manual pass worth doing by hand
because it is the only way to see an email come out of the chain.

## Prerequisites

`php artisan migrate:fresh --seed`. For the manual pass, set `MAIL_MAILER=log` and
`QUEUE_CONNECTION=sync` in `.env`, so a send lands in `storage/logs/laravel.log` and nothing waits on a
worker.

## The suite, narrowest first

```bash
php artisan test --compact tests/Unit/Helpers          # the arithmetic and the quiet hours
php artisan test --compact tests/Unit/Actions          # generation, reconciliation, postpone and dismiss
php artisan test --compact tests/Unit/Jobs            # the scheduled sweeps
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact                            # all of it
```

The three database drivers are only exercised in CI, so a change touching SQL is not proven locally.

## The manual pass

The chain is proven end to end by the two tests at the bottom of `DeliverDueNotificationsTest`, which
run it with a real queue and a real mailable. This is only for seeing an email come out with your own
eyes.

1. Sign in, open a contact, put a date on a `date` field, tick **Add a reminder**, ask for **on the
   day**, set the local time to a couple of minutes from now, save.
2. Run generation twice. Expect exactly one `reminder_occurrences` row, `pending`, its `scheduled_for`
   in UTC and its `local_date` the day you chose. The second run changing nothing is FR-008.
3. Let the time pass, run delivery. Expect one `notifications` row per enabled channel, the email in
   the log, and the occurrence `notified` with `notified_at` set.
4. Turn quiet hours on across the current hour and repeat with a second deadline. Expect the
   notification created but unsent, `held_until` at the end of the window, and the occurrence still
   saying it was due when it was due.

Generation and delivery are dispatched by the scheduler. To run them without waiting:

```bash
php artisan schedule:test   # pick the generation entry, then the delivery entry
```

## What proves each user story

| Story | Proven by |
| --- | --- |
| 1 Deadlines exist | `ReminderScheduleTest` for the window enumeration and the DST cases, `GenerateReminderOccurrencesTest` for idempotency and the concurrent insert. |
| 2 It reaches the person | `NotifyReminderOccurrenceTest`, `DeliverNotificationTest`, `QuietHoursTest`, `SendNotificationDigestTest`, and the two tests in `DeliverDueNotificationsTest` that run the whole chain with nothing faked but the mailer. |
| 3 Postpone or dismiss | `PostponeReminderOccurrenceTest` and `DismissReminderOccurrenceTest`, asserting the sibling occurrence and source value are untouched, unsent stale notifications are abandoned, and sent ones remain as history. |
| 4 Reconciliation | `ReconcileReminderOccurrencesTest` for the paths reached through the record and the field, plus `SetFieldValuesTest` for a corrected date, a cleared one and an unticked box. |
| 5 Bad input refused | `ContactSectionControllerTest`, asserting a redirect with an error and not a 500. |
| 6 Preferences | `CreateAccountTest`, `CreateNotificationPreferenceTest`, `UpdateNotificationPreferenceTest` and `BackfillNotificationPreferencesTest`. |

## Before it is done

```bash
vendor/bin/pint --format agent
vendor/bin/phpstan analyse
php artisan monica:localize          # then translate everything it reports, in all nine locales
php artisan monica:localize --check  # must pass, it judges the counted phrases and the typography
```
