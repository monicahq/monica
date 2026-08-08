# 042. Notification

**Status:** to create
**Spec:** 10.1, 10.4, 3.3, 7, 14.4

## What it is

What actually reaches the user, on one channel. It is the fourth of the four things section 10.1 separates, and it is deliberately not limited to reminders.

Several things in the product need to tell the user something, and they should all arrive through one mechanism rather than three. A reminder deadline arrives (10.4). A marketplace author published a new version of something installed, with its change note (7). A webhook subscription was disabled after repeated failures, with an explanation and a way to switch it back on (14.4). All three are notifications, all three respect quiet hours, and all three appear in the same place.

## Fields

```
id                          int                             primary key
account_id                  int                             account the notification belongs to
user_id                     int                             user it is for
uuid                        string (36)                     unguessable identifier used by the API
type                        string (64)                     what it is about, such as reminder_due or marketplace_update_available
channel                     enum (in_app, email, push)      how it was delivered
notifiable_type             string (255)                    class of what it is about, an occurrence, an installation, a subscription
notifiable_id               int                             id of that object
title                       string (255)                    the line the user reads first
body                        text                            the rest of what it says
payload                     json                            data the interface needs to render it and to offer its actions
locale                      string (5)                      language it was rendered in
scheduled_for               datetime                        when it should go out
held_until                  datetime                        when it may go out, once quiet hours have pushed it back
sent_at                     datetime                        when it was handed to the channel
delivered_at                datetime                        when the channel confirmed delivery, null when it cannot confirm
failed_at                   datetime                        when delivery failed
failure_reason              string (255)                    why it failed
read_at                     datetime                        when the user read it, for the in app channel
digest_id                   int                             daily digest it was folded into, null when it went out on its own
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [User](002-user.md)
- `morphTo` notifiable, most often a [ReminderOccurrence](041-reminder-occurrence.md)
- `belongsTo` self as the digest it belongs to

## Rules

- One occurrence can produce several notifications, one per enabled channel (10.1).
- Quiet hours are applied here (10.4). A notification due at 3am is held and delivered at 8am. `scheduled_for` keeps when it was due, `held_until` says when it may go, and the two are never conflated.
- Digest mode folds notifications into one (10.4). The folded ones keep their rows and point at the digest, so the history stays complete.
- A notification never goes to somebody viewing a share link (10.4, 4.4). Share link viewers are not users of the account and receive nothing.
- `locale` is stored so a language bug is visible after the fact, as on [EmailSent](005-email-sent.md).
- Nothing is deleted silently. A failed delivery keeps its reason.

## Open questions

- Retention. In app notifications pile up and lose value quickly, but they are the only trace of what the user was told and when.
- Whether push tokens live on the user or on their own model. Mobile applications are deferred (3.8) but push must exist server side in v1, and several devices per user means several tokens, which points at a model rather than a column. It is listed here so it is not forgotten when the mobile work starts.
