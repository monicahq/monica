# 043. NotificationPreference

**Status:** exists (`app/Models/NotificationPreference.php`), one row per account, read on every send
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
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- read by [Notification](042-notification-done.md) on every delivery

## Rules

- One row per account, created with the account so delivery never has to handle a missing row. Every account that existed before the feature was given one by migration, for the same reason.
- **The defaults are in-app and email on, push off, quiet hours off, delivery immediate**, in the zone of the account's user. Quiet hours are off because guessing at somebody's sleeping hours and then silently holding their reminders is worse than telling them at the wrong time once.
- **Quiet hours reach every channel, in-app included.** Section 10.4 says nothing may disturb and carves nothing out.
- **The digest hour is never held by quiet hours.** It is an hour the account chose deliberately, and an implicit setting overruling an explicit one would move a summary somebody scheduled.
- **A window with both ends the same is a whole day of quiet**, not an absence of quiet. Nothing is lost by it: the notification waits.
- **`push_is_enabled` can be switched on and still delivers nothing.** There is no driver, and the model says so rather than letting a preference promise something the product cannot keep.
- Quiet hours hold a notification, they never drop it (10.4). Something due at 3am arrives at 8am, and the occurrence keeps its real due time ([ReminderOccurrence](041-reminder-occurrence-done.md)).
- Quiet hours may cross midnight. 22:00 to 08:00 is the obvious setting and the naive comparison gets it wrong.
- Exceptions defined on a single rule take precedence over these preferences (10.4). `type_overrides` holds the per type layer and the per rule layer lives on the rule itself.
- Notification preferences add no second lead time to a reminder. When the user wants to be told is expressed by the rule's moments, and each moment has already become an occurrence at that exact time before delivery begins. Moving the notification earlier here would apply the offset twice.
- Turning every channel off is allowed. Somebody who wants no notifications should not have to disable every rule to get silence.
- Push must exist server side in v1 even though the mobile applications come later (3.8).

## Not done yet

- **No screen.** The row is created and read, and nothing lets anybody change it but the action behind it. The Notifications entry in the settings has no URL.
- **No API.**
- **Push has no driver, and `type_overrides` has no producer.** The column is shaped for per type exceptions and nothing writes one yet.

## Open questions

- Where push tokens are stored. See the same question on [Notification](042-notification-done.md).
- Whether digest and immediate can be mixed per type, so a reminder arrives at once while a marketplace update waits for the digest. `type_overrides` is shaped to allow it and the specification does not require it.
