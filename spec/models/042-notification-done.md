# 042. Notification

**Status:** exists (`app/Models/Notification.php`), made, held and delivered on two channels
**Spec:** 10.1, 10.4, 3.3, 7, 14.4

## What it is

What actually reaches the user, on one channel. It is the fourth of the four things section 10.1 separates, and it is deliberately not limited to reminders.

Several things in the product need to tell the user something, and they should all arrive through one mechanism rather than three. A reminder deadline arrives (10.4). A marketplace author published a new version of something installed, with its change note (7). A webhook subscription was disabled after repeated failures, with an explanation and a way to switch it back on (14.4). All three are notifications, all three respect quiet hours, and all three appear in the same place.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the notification belongs to
user_id                     int                             user it is for
type                        string (64)                     what it is about, such as reminder_due or marketplace_update_available
channel                     enum (in_app, email, push)      how it was delivered
notifiable_type             string (255)                    class of what it is about, an occurrence, an installation, a subscription, null on a digest
notifiable_id               int                             id of that object, null on a digest
title                       string (255)                    the line the user reads first
body                        text                            the rest of what it says
payload                     json                            data the interface needs to render it and to offer its actions
locale                      string (5)                      language it was rendered in
scheduled_for               datetime                        when it was due, copied from the deadline and never recomputed
held_until                  datetime                        when it may go out, always written, equal to scheduled_for when nothing holds it
sent_at                     datetime                        when it was handed to the channel
delivered_at                datetime                        when the channel confirmed delivery, null when it cannot confirm
failed_at                   datetime                        when delivery failed
failure_reason              string (255)                    why nothing was sent
attempts                    int                             how many times the channel was tried, so a bounded retry is visible on the row
read_at                     datetime                        when the user read it, for the in app channel
digest_id                   int                             daily digest it was folded into, null when it went out on its own
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [User](002-user-done.md)
- `morphTo` notifiable, most often a [ReminderOccurrence](041-reminder-occurrence-done.md)
- `belongsTo` self as the digest it belongs to

## Rules

- One occurrence produces at most one notification per enabled channel (10.1), on its day. A notification copies its occurrence's `scheduled_for` and never shifts it.
- Quiet hours are applied here (10.4). A notification due at 3am is held and delivered at 8am. `scheduled_for` keeps when it was due, `held_until` says when it may go, and the two are never conflated.
- Digest mode folds notifications into one (10.4). The folded ones keep their rows and point at the digest, so the history stays complete.
- A notification never goes to somebody viewing a share link (10.4, 4.4). Share link viewers are not users of the account and receive nothing.
- A held notification is checked again before it goes out, and it does not go out if the record it is about was archived or deleted while it waited. A reminder due at 3am on a contact archived at 7am must not arrive at 8am. The row stays, with `failure_reason` saying why nothing was sent, since a notification that was silently dropped is indistinguishable from one that was never scheduled.
- `locale` is stored so a language bug is visible after the fact, as on [EmailSent](005-email-sent-in-progress.md).
- Nothing is deleted silently. A failed delivery keeps its reason, and is retried up to `notification_maximum_attempts` before being abandoned.
- **Nobody is told twice.** A unique index over `notifiable_type, notifiable_id, channel, user_id` refuses a second row about the same thing on the same channel. There is no reminder after the first. A digest leaves the first two columns null and collides with nothing on any of the three drivers.
- **An obsolete delivery never escapes.** Answering an occurrence abandons every unsent notification for it, and delivery checks again that the occurrence is still active before anything goes out, so a row held by quiet hours or picked up by a worker cannot outlive the answer. Sent rows remain as history, and sending or reading one never counts as an answer.
- `held_until` is always written rather than left null when nothing holds the notification, so the sweep that finds what may go out is one comparison instead of two.
- The name is `App\Models\Notification` and has nothing to do with Laravel's own notification system, which lives in `app/Notifications` and carries the framework's email verification.

## Not done yet

- **Only in-app and email are delivered.** `push` is a channel and a preference with no driver behind it, which the constitution asks for on the server before the native applications arrive. Nothing pretends to deliver it.
- **The in-app channel has no notification centre.** The row is the notification, and there is nowhere to read the unread ones as a list, so `read_at` is written by nothing. What does read the table is the reminder side: the sent tab of the vault's Reminders page draws a mark per channel a deadline went out on, and the rule's own page says how each of its deliveries was carried. So a notification is visible where the thing it is about is, and nowhere on its own.
- **Only reminders produce one.** A marketplace update and a webhook subscription switched off are both meant to arrive here, and neither is wired.
- **No API and no webhook.**
- **No retention.** See the open question below.

## Open questions

- Retention. In app notifications pile up and lose value quickly, but they are the only trace of what the user was told and when.
- Whether push tokens live on the user or on their own model. Mobile applications are deferred (3.8) but push must exist server side in v1, and several devices per user means several tokens, which points at a model rather than a column. It is listed here so it is not forgotten when the mobile work starts.
