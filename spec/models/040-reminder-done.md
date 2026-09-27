# 040. Reminder

**Status:** exists (`app/Models/Reminder.php`), produces deadlines, and is read and acted on from screens of its own. Built as far as the models it depends on allow
**Spec:** 3.2, 10.1, 10.2, 10.3, 10.5

## What it is

The rule. Section 10.1 takes apart the word "reminder" into four things that behave differently, and insists on the split because without it none of the required behaviours can be implemented: the answer given for each date cannot be kept, and several notifications for one deadline cannot be delivered correctly.

- The **source** is where the need comes from: a date on a page, a manual entry, one day an inactivity.
- The **rule** is the logic. That is this model.
- The **occurrence** is one concrete deadline, one per cycle of the rule, on the day itself ([ReminderOccurrence](041-reminder-occurrence-done.md)). It carries the answer.
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
field_id                    int                             date field it reads, null for a manual rule
field_value_id              int                             exact value it reads, null once that value has been cleared
name                        string (255)                    what the reminder says when it fires
source                      enum (manual, field_value)      where the need comes from
starts_on                   date                            first date of a manual rule, null for a field rule
cadence                     enum (once, weekly, monthly, yearly)  how often it fires, once included
frequency_interval          tinyint                         how many of those between firings, 1 unless somebody set a rhythm
time_of_day                 time                            local time it fires at
timezone                    string (64)                     time zone frozen at creation, which later changes to the user preference never move
schedule_revision           int                             bumped whenever cadence, interval, first date, local time, zone or the value read changes, so a regenerated deadline can never collide with one cancelled under the old schedule
is_active                   boolean                         whether it still produces occurrences
inactive_reason             enum (value_cleared, record_archived, record_deleted, field_removed)  why it stopped, so the page can explain instead of hiding it. All four are things that happened to the rule; nobody sets one by hand, since there is no pause
deactivated_at              datetime                        when it stopped
created_by_user_id          int                             user who created it, null when the system did
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

**The source and the cadence are two columns, not one.** The specification's own section 10.2 says the source provides the record, field and value while the rule separately defines whether it fires once or recurs, so a single `kind` enum holding `one_off`, `recurring` and `field_based` cannot express half of what it has to: a field driven rule that fires once. `daily` is not a cadence, because a date on a record cannot come round every day, and a rhythm somebody sets themselves is the yearly cadence with the interval spaced out rather than a kind of its own.

**A manual rule carries its date, a field rule reads it.** Exactly one of `starts_on` and `field_id` is set, depending on `source`. A manual rule cannot start before today.

**The revision is what makes regeneration safe.** Changing a rule cancels the deadlines ahead of it and generates replacements. Without a revision the replacements would carry the identities the cancelled rows already hold, and a rule changed and then changed back would silently stop producing anything, because the unique index would refuse every key it tried to write. The revision goes into the identity of every deadline, so each schedule the rule has ever had writes under identities nothing has used before.

**One cycle, one occurrence, on the day.** There are no moments ahead of the day. A yearly rule reading 12 May produces one occurrence a year, notified once per enabled channel on 12 May at `time_of_day`, and never again after it.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `morphTo` remindable
- `belongsTo` [Field](017-field-done.md), [FieldValue](022-field-value-done.md), [User](002-user-done.md)
- `hasMany` [ReminderOccurrence](041-reminder-occurrence-done.md)

## Rules

- **Any `date` or `recurring_date` field can drive a rule** (10.2), on any page, custom or not. A field based rule derives its record, field and exact value from the source, but keeps its own cadence; the creation behaviour and what the user chooses are settled in 10.2 rather than repeated here.
- **The time is local, not absolute** (10.3). "Every year at 9am" means 9am local, across daylight saving changes. The zone is frozen at creation, and changing the personal time zone later never shifts a rule already set. This is why `timezone` is on the rule and not read from the user.
- **Every cycle produces one occurrence.** The cadence finds the local date of the cycle and produces one [ReminderOccurrence](041-reminder-occurrence-done.md) at `time_of_day` on that date. A date whose instant has already passed when the rule is created produces nothing, and a one off rule whose only date is in that case is refused.
- **Inactivity reads `last_interaction_at` on the contact** (9.3, 10.2). Only a life event in which both the contact and the Me contact participate contributes to that value, regardless of its type. A life event documented about the contact alone moves `last_life_event_at` and leaves the inactivity delay running. Its quality depends entirely on participants and dates being recorded accurately, which the interface should say plainly.
- **A change to the source breaks nothing** (10.3). A corrected date recomputes the occurrences after today and keeps every answer. A deleted or archived contact cancels every occurrence still waiting for an answer, overdue ones included. A field removed from a structure disables the rule and the product **explains why it is inactive** rather than making it disappear. That is what `inactive_reason` is for, and it is a direct application of section 3.3.
- **An archived record is a silent record.** Archiving a [Contact](019-contact-done.md) or an [Entity](020-entity-done.md) deactivates every rule about it with `record_archived`, and unarchiving reactivates them and recomputes what is ahead. Archiving exists so somebody stops hearing about a record without losing what is written on it, and a rule that kept firing would defeat the only thing archiving does. The rule stays visible and says why it is quiet, as it does for every other reason (3.3). An inactivity rule is included: an archived record cannot fire for silence.
- **Reactivation is matched on the reason.** Unarchiving reverses archiving and nothing else, so a rule quiet because the field it read has left the structure stays quiet and goes on saying so. Reviving every quiet rule would undo something nobody undid.
- **Deleting the rule is the only way a user stops it** (10.3). There is no pause. Deleting cancels every occurrence still waiting for an answer and soft deletes the rule, so the answers already given stay readable. Answering acts on one occurrence and is handled by [ReminderOccurrence](041-reminder-occurrence-done.md).
- **At most one live rule per value.** The rule hangs off the value it reads rather than off the pair of record and field, which is what caps it and what lets a date inside a repetition carry a rule of its own.
- A reminder belongs to one account and is never sent to anybody viewing a share link (10.4).
- A rule is not a task (10.5). A task is an action to do, with a status and a completion date. The two are not merged, and when tasks arrive they will reuse this scheduling machinery rather than grow a second one, so nothing here may assume a rule is always about a record.

## Not done yet

- **The notification preferences have no screen.** The account cannot choose its channels' quiet hours or its digest from anywhere but defaults.
- **Four columns the Fields list above does not carry.** `description` has no control on the screen. `life_event_type_id` and `inactivity_days` wait for [LifeEventType](035-life-event-type-in-progress.md). `ends_on` is a bound no screen asks for.
- **Reminders tied to life events are not built,** and neither is one watching for silence. Both wait for [LifeEventType](035-life-event-type-in-progress.md).
- **Only a contact can hold one.** `remindable` is a morph so an [Entity](020-entity-done.md) can carry a rule the day entities have screens.
- **A date inside a repetition can hold a rule and no screen offers one.** The panel is drawn only under a date sitting directly in a section.
- **A field rule cannot be created through the API.** Neither `fields` nor `field_values` carries a `uuid`, so the value it reads cannot be addressed from outside. A field rule can be read, changed and deleted there; a manual rule can be created too.
- **No webhook.** Each state change is meant to be listenable (14.2), and there is no webhook machinery in the product.
- **A rule silenced by archiving or deleting a contact stays silent.** Nothing unarchives or restores a contact yet. A rule silenced because its record moved to a version without the field starts again when the record moves back to one that carries it, and undoing a contact merge reactivates the rules the merge silenced.

## Decisions taken while building it

- **The rule points at the value, not at the record and the field.** It is what makes one live rule per value a statement about one column, and it is what makes a repetition's date work with nothing added.
- **`field_value_id` nulls rather than cascades.** Clearing a date destroys its value row, and a rule cascaded away with it would be the silent deletion section 3.3 forbids. The pointer is let go of and the rule stays, quiet and saying why. It is therefore looked up on the field rather than on the value, or a rule whose date has gone would become invisible.
- **A date typed in again gets a rule of its own.** The quiet one is taken away as the new one is made, so a record does not collect an explanation for every time somebody changed their mind.
- **Occurrences are generated over a rolling window of 90 days, and the length is configuration.** It answers what used to be the open question here. Ninety days is long enough that no realistic outage of the generator loses a deadline, since it runs daily and only has to have run once inside the window, and short enough that a yearly rule multiplied across hundreds of records stays thousands of rows rather than a table nobody reads. It is `reminder_generation_window_in_days` in `config/monica.php`.
- **A deadline already in the past is never generated.** A rule made at 15:00 for today at 09:00 produces nothing for today; it starts at its next date. A deadline that passed before its rule existed was never a deadline, and inventing it would fire a reminder about something already gone the moment somebody ticks a box.
- **Moments were removed.** Being told ahead of the day was replaced by one notification on the day and an answer per date. Nothing ahead of the day is configurable.
- **One live rule per value is enforced in the action, not the schema.** A unique index cannot say "one *live* rule" under soft deletes, because MySQL skips the check whenever a key column is null. It is `CreateReminder` that refuses the second, the way `is_unique` on a value is refused by `SetFieldValue`.
- **Whether a field drives a reminder is a property of its type.** The `is_reminder_source` column on [Field](017-field-done.md) is gone, along with its builder wiring and its payloads. A structure file exported before that still imports: the key stays allowed and is read by nobody, because refusing it would refuse every file already in circulation.

## Open questions

- Whether an inactivity rule can watch a group rather than a single contact ("nobody in the family for a month"). Natural to want, not specified.
