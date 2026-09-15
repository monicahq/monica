# 040. Reminder

**Status:** exists (`app/Models/Reminder.php`), produces deadlines, and is read and acted on from screens of its own. Built as far as the models it depends on allow
**Spec:** 3.2, 10.1, 10.2, 10.3, 10.5

## What it is

The rule. Section 10.1 takes apart the word "reminder" into four things that behave differently, and insists on the split because without it none of the required behaviours can be implemented: a reminder cannot be postponed cleanly, its history cannot be kept, and several notifications for one deadline cannot be delivered correctly.

- The **source** is where the need comes from: a date on a page, an inactivity, a manual entry.
- The **rule** is the logic. That is this model.
- The **occurrence** is one concrete deadline produced by one moment in one cycle of the rule ([ReminderOccurrence](041-reminder-occurrence-done.md)).
- The **notification** is what actually reaches the user ([Notification](042-notification-done.md)).

Reminders are in the core (3.2), not because every use needs them, but because the product has to handle them transversally whatever structure the user built.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the rule belongs to
vault_id                    int                             vault it lives in
remindable_type             string (255)                    class of the record it is about, a contact today
remindable_id               int                             id of that record
field_id                    int                             date field it reads
field_value_id              int                             exact value it reads, null once that value has been cleared
name                        string (255)                    what the reminder says when it fires
source                      enum (field_value)              where the need comes from
cadence                     enum (once, weekly, monthly, yearly)  how often it fires, once included
frequency_interval          tinyint                         how many of those between firings, 1 unless somebody set a rhythm
moments                     json                            when to be told, a list of direction, amount and unit, each producing an occurrence in every cycle
time_of_day                 time                            local time it fires at
timezone                    string (64)                     time zone frozen at creation, which later changes to the user preference never move
schedule_revision           int                             bumped whenever cadence, interval, moments, local time, zone or the value read changes, so a regenerated deadline can never collide with one cancelled under the old schedule
is_active                   boolean                         whether it still produces occurrences
inactive_reason             enum (value_cleared, record_archived, record_deleted, field_removed, paused_by_user)  why it stopped, so the page can explain instead of hiding it. Four are things that happened to the rule; the fifth is the only one somebody sets and the only one somebody may lift
deactivated_at              datetime                        when it stopped
created_by_user_id          int                             user who created it, null when the system did
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

**The source and the cadence are two columns, not one.** The specification's own section 10.2 says the source provides the record, field and value while the rule separately defines whether it fires once or recurs, so a single `kind` enum holding `one_off`, `recurring` and `field_based` cannot express half of what it has to: a field driven rule that fires once. `daily` is not a cadence, because a date on a record cannot come round every day, and a rhythm somebody sets themselves is the yearly cadence with the interval spaced out rather than a kind of its own.

**A moment carries a unit, not a count of days.** Three months before the first of March is the first of December, and no number of days says that: thirty is wrong in February and ninety-two is wrong in a leap year. Each moment is `{direction, amount, unit}`, with the amount and the unit dropped for a moment falling on the day itself.

**The revision is what makes regeneration safe.** Changing a rule cancels the deadlines ahead of it and generates replacements. Without a revision the replacements would carry the identities the cancelled rows already hold, and a rule changed and then changed back would silently stop producing anything, because the unique index would refuse every key it tried to write. The revision goes into the identity of every deadline, so each schedule the rule has ever had writes under identities nothing has used before.

**A moment becomes an occurrence, not a notification.** One cycle of the rule produces one occurrence for every moment. A yearly rule reading 12 May with moments seven days before and on the day therefore produces occurrences on 5 May and 12 May. The notification layer only delivers each occurrence through the enabled channels; it does not apply the moment again. Postponing or dismissing one of those occurrences leaves its siblings in the same cycle and every later cycle alone.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `morphTo` remindable
- `belongsTo` [Field](017-field-done.md), [FieldValue](022-field-value-done.md), [User](002-user-done.md)
- `hasMany` [ReminderOccurrence](041-reminder-occurrence-done.md)

## Rules

- **Any `date` or `recurring_date` field can drive a rule** (10.2), on any page, custom or not. A field based rule derives its record, field and exact value from the source, but keeps its own cadence; the creation behaviour and what the user chooses are settled in 10.2 rather than repeated here.
- **The time is local, not absolute** (10.3). "Every year at 9am" means 9am local, across daylight saving changes. The zone is frozen at creation, and changing the personal time zone later never shifts a rule already set. This is why `timezone` is on the rule and not read from the user.
- **Every cycle is expanded through `moments`.** The cadence first finds the local date of the cycle, then every normalized moment shifts that date and produces one [ReminderOccurrence](041-reminder-occurrence-done.md) at `time_of_day`. Two moments therefore never share an occurrence, even when calendar clamping makes them land at the same instant.
- **Inactivity reads `last_activity_at` on the contact** (9.3, 10.2). Every activity in which the contact participates contributes to that value, regardless of its type. Its quality depends entirely on participants and dates being recorded accurately, which the interface should say plainly.
- **A change to the source breaks nothing** (10.3). A corrected date recomputes future occurrences and keeps the history. A deleted contact cancels future occurrences and restores them if it comes back. A field removed from a structure disables the rule and the product **explains why it is inactive** rather than making it disappear. That is what `inactive_reason` is for, and it is a direct application of section 3.3.
- **An archived record is a silent record.** Archiving a [Contact](019-contact-done.md) or an [Entity](020-entity-done.md) deactivates every rule about it with `record_archived`, and unarchiving reactivates them and recomputes what is ahead. Archiving exists so somebody stops hearing about a record without losing what is written on it, and a rule that kept firing would defeat the only thing archiving does. The rule stays visible and says why it is quiet, as it does for every other reason (3.3). An inactivity rule is included: an archived record cannot fire for silence.
- **Reactivation is matched on the reason.** Unarchiving reverses archiving and nothing else, so a rule quiet because the field it read has left the structure stays quiet and goes on saying so. Reviving every quiet rule would undo something nobody undid.
- **Disabling the rule is the only action a user takes that cancels every future occurrence**, and it is reversible (10.3). Deleting or archiving the record it is about cancels them too, and those are reversible in the same way. Postponing and dismissing act on one occurrence and are handled by [ReminderOccurrence](041-reminder-occurrence-done.md).
- **At most one live rule per value.** The rule hangs off the value it reads rather than off the pair of record and field, which is what caps it and what lets a date inside a repetition carry a rule of its own.
- A reminder belongs to one account and is never sent to anybody viewing a share link (10.4).
- A rule is not a task (10.5). A task is an action to do, with a status and a completion date. The two are not merged, and when tasks arrive they will reuse this scheduling machinery rather than grow a second one, so nothing here may assume a rule is always about a record.

## Not done yet

- **The notification preferences have no screen.** Everything else about a rule is now on one. The vault's Reminders page lists what is coming, groups it by date or by contact under a real URL for each, and shows what has already gone out on a third tab; the sidebar entry carries the count of what is due inside the generation window; a deadline can be put off by a preset, dismissed, or sent to the account as a test; and the rule itself has a page saying what it reads, when it fires, how it was delivered and what it has done, with pause, resume and delete on it. What has no screen is [NotificationPreference](043-notification-preference-done.md): the account cannot choose its channels, its quiet hours or its digest from anywhere.
- **Nine columns the Fields list above does not carry.** `description` has no control on the screen. `activity_type_id` and `inactivity_days` wait for [ActivityType](035-activity-type-in-progress.md), which has no table. `weekdays`, `day_of_month` and `month_of_year` are cadence detail no screen asks for, and `starts_on` and `ends_on` are bounds no screen asks for either; the value the rule reads is where it starts. `last_evaluated_at` is gone from the list for good: the generator is idempotent over a rolling window, so it needs no memory of when it last ran, and a column saying otherwise would be one nothing could be trusted to keep true. Storing a column nothing writes would be storing a lie, and migrations are edited rather than added until production, so each arrives with the screen that fills it.
- **`source` has one case.** A manual entry, an activity and an inactivity are sources the specification names and no screen offers. `inactive_reason` has four of its five written: `value_cleared` when a date is emptied, `record_archived` when a contact is archived, `record_deleted` when one is deleted, and `paused_by_user`, which is the only reason a person sets and the only one a person may lift, and which is what makes pausing different from unticking the box on the record, since that soft deletes the rule instead. `field_removed` is written by nothing, the action that set a field's values aside having been removed.
- **Only a contact can hold one.** `remindable` is a morph so an [Entity](020-entity-done.md) can carry a rule the day entities have screens. They have none at all today.
- **A date inside a repetition can hold a rule and no screen offers one.** The rule keys on the value, and a repetition's answer is its own value row, so the model needs nothing added. The panel is drawn only under a date sitting directly in a section, because a second naming scheme in the form is what it would cost.
- **No API.** The API has reached contacts, but not what is written on one: neither `fields` nor `field_values` carries a `uuid`, so the value a rule is created from cannot be addressed from outside, and no endpoint reads or writes a rule. Principle X is unmet and the debt is recorded.
- **No webhook.** Each state change is meant to be listenable (14.2), and there is no webhook machinery in the product.
- **The only source is still a date on a contact.** A manual reminder, one relative to an activity and one watching for silence all wait for screens and for [ActivityType](035-activity-type-in-progress.md). So do several weekdays in one rule, a date inside a repetition, and an [Entity](020-entity-done.md) carrying a rule. The Reminders page therefore lists rules somebody made from a date and nothing else, and offers no way to make one from there.
- **`DestroyField` is not wired.** It only ever takes a field out of a draft section version, which can hold no values and therefore no rules, so there is nothing there to silence. The path that matters is [FieldValue](022-field-value-done.md) being set aside, and the two actions that carried it had no caller and have been removed, so `field_removed` is a reason nothing writes.
- **Only pausing is ever lifted.** `ReactivateReminders` matches on the reason, and one caller is left: the pause screen, which passes `paused_by_user`. The three actions that lifted the other reasons, unarchiving a record for `record_archived`, restoring a deleted one for `record_deleted` and putting a field's values back for `field_removed`, had no caller of their own and have been removed. `value_cleared` never had one, since filling the date in again builds a new rule rather than waking the old one. A rule silenced by anything but a person therefore stays silent, and the [ReminderOccurrence](041-reminder-occurrence-done.md) rows cancelled with it are never brought back.

## Decisions taken while building it

- **The rule points at the value, not at the record and the field.** It is what makes one live rule per value a statement about one column, and it is what makes a repetition's date work with nothing added.
- **`field_value_id` nulls rather than cascades.** Clearing a date destroys its value row, and a rule cascaded away with it would be the silent deletion section 3.3 forbids. The pointer is let go of and the rule stays, quiet and saying why. It is therefore looked up on the field rather than on the value, or a rule whose date has gone would become invisible.
- **A date typed in again gets a rule of its own.** The quiet one is taken away as the new one is made, so a record does not collect an explanation for every time somebody changed their mind.
- **Occurrences are generated over a rolling window of 90 days, and the length is configuration.** It answers what used to be the open question here. Ninety days is long enough that no realistic outage of the generator loses a deadline, since it runs daily and only has to have run once inside the window, and short enough that a yearly rule multiplied across hundreds of records stays thousands of rows rather than a table nobody reads. It is `reminder_generation_window_in_days` in `config/monica.php`.
- **A deadline already in the past is never generated.** A rule made today over a date next week produces nothing for the moment seven days before it, which has already gone. A deadline that passed before its rule existed was never a deadline, and inventing it would fire a reminder about yesterday the moment somebody ticks a box.
- **The moments live in a json column.** A moment has no identity, is never addressed, never ordered on its own and is always read with its rule. A table for them would enforce the no-duplicates rule in the schema, which is the one thing it buys, and cost a join for something never queried apart. Duplicates are refused in validation instead, by `App\Rules\ReminderMoments`, which the form and the actions behind it both read so neither can have an opinion of its own about what a moment is.
- **Occurrences snapshot their moment.** A moment needs no row or permanent identifier on the rule. When it produces an occurrence, its normalized direction, amount and unit are copied onto that occurrence with the cycle date. That immutable snapshot says which configured interruption produced the historical deadline after the rule itself has changed.
- **One live rule per value is enforced in the action, not the schema.** A unique index cannot say "one *live* rule" under soft deletes, because MySQL skips the check whenever a key column is null. It is `CreateReminder` that refuses the second, the way `is_unique` on a value is refused by `SetFieldValue`.
- **Whether a field drives a reminder is a property of its type.** The `is_reminder_source` column on [Field](017-field-done.md) is gone, along with its builder wiring and its payloads. A structure file exported before that still imports: the key stays allowed and is read by nobody, because refusing it would refuse every file already in circulation.

## Open questions

- Whether an inactivity rule can watch a group rather than a single contact ("nobody in the family for a month"). Natural to want, not specified.
