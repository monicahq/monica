# Quickstart: validating the feature

Each section proves one user story end to end. Run them in order; each assumes the one above passed.

## Prerequisites

`composer install --quiet && php artisan migrate:fresh --seed --quiet && bun run build --silent`.
Slack, Telegram and text message need an account of your own at each service. Nothing is configured
on the instance, by design (FR-038), so a run with none of them still validates stories 1 to 4.

## Story 1, a channel carries a message whatever it is

`php artisan test --compact tests/Unit/Actions/DeliverNotificationTest.php
tests/Unit/Actions/NotifyReminderOccurrenceTest.php tests/Unit/Actions/SendNotificationDigestTest.php`

Expected: one notification per enabled channel, each delivered by its own channel; a channel that
refuses retries to the configured maximum then abandons with a reason; the other channel is
unaffected; one summary per channel in digest mode.

## Story 3, the notification bar

`php artisan test --compact tests/Feature/Views/Components/NotificationBarTest.php`, then
`composer run dev`. Sign in, run `php artisan schedule:run` to sweep a due deadline, and reload. Expected: the count
rises in the top bar on every signed-in screen, the list reads newest first, opening one marks it
read and lands on the record, clearing all asks first, and the empty state is a sentence.

## Story 4, switching a channel on and proving it

Open `settings/account/notifications`. Expected: all six channels listed, each with its setup steps.
Switch on a channel with no destination and the save is refused naming what is missing. Save a phone
number and the channel stays off until the code is typed back; save a Slack room and it may be
switched on at once (FR-042, FR-043).

## Story 5, the outbound channels

With your own credentials saved, use the test send on each. Expected: the message arrives in the
account's language, carrying the reminder's name, what it is about and the date it reads. A text
message longer than the limit is shortened at a sentence boundary, not cut mid word.

## Retention

`php artisan model:prune --pretend`. Expected: notifications older than `monica.notification_retention_months` are listed. Run it for
real and check the reminder history still says the deadline was notified and says that per-channel
records are not kept beyond the period (FR-050, FR-051).

## Gates before merge

```bash
vendor/bin/pint --format agent
vendor/bin/phpstan analyse --no-progress
php artisan monica:localize --check
composer run test
```

`monica:localize` must report nothing missing across all 31 locales, and `--check` enforces the
counted-phrase and typography rules on every string this feature adds.
