# 043. NotificationPreference

**Status:** to create
**Spec:** 10.4, 3.8

## What it is

How the user wants to be told things. Section 10.4 puts this at the account level and lists exactly what it holds: which channels are on, the quiet hours during which nothing may disturb, and whether notifications arrive immediately or folded into a daily digest.

It is one row per account, not a bag of settings scattered across the user row, because these values are read together on every delivery and because a rule may override them individually.

## Fields

```
id                          int                             primary key
account_id                  int                             account the preferences belong to
in_app_is_enabled           boolean                         whether notifications appear inside the application
email_is_enabled            boolean                         whether they are also emailed
push_is_enabled             boolean                         whether they are also pushed to a device
quiet_hours_start           time                            local time from which nothing may be delivered
quiet_hours_end             time                            local time from which delivery may resume
quiet_hours_are_enabled     boolean                         whether quiet hours apply at all
delivery_mode               enum (immediate, digest)        whether each notification goes out on its own or is folded into a summary
digest_time                 time                            local time the daily summary is sent
digest_weekdays             json                            days the summary is sent, so weekends can be spared
timezone                    string (64)                     zone quiet hours and the digest are read in, defaulting to the user's
type_overrides              json                            per type exceptions, so one kind of notification can bypass or be silenced
minimum_reminder_lead_time  int                             how far ahead of an occurrence its notification goes out, in minutes
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- read by [Notification](042-notification-not-yet.md) on every delivery

## Rules

- One row per account, created with the account so delivery never has to handle a missing row.
- Quiet hours hold a notification, they never drop it (10.4). Something due at 3am arrives at 8am, and the occurrence keeps its real due time ([ReminderOccurrence](041-reminder-occurrence-not-yet.md)).
- Quiet hours may cross midnight. 22:00 to 08:00 is the obvious setting and the naive comparison gets it wrong.
- Exceptions defined on a single rule take precedence over these preferences (10.4). `type_overrides` holds the per type layer and the per rule layer lives on the rule itself.
- Turning every channel off is allowed. Somebody who wants no notifications should not have to disable every rule to get silence.
- Push must exist server side in v1 even though the mobile applications come later (3.8).

## Open questions

- Where push tokens are stored. See the same question on [Notification](042-notification-not-yet.md).
- Whether digest and immediate can be mixed per type, so a reminder arrives at once while a marketplace update waits for the digest. `type_overrides` is shaped to allow it and the specification does not require it.
