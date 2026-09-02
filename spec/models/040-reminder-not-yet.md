# 040. Reminder

**Status:** to create
**Spec:** 3.2, 10.1, 10.2, 10.3, 10.5

## What it is

The rule. Section 10.1 takes apart the word "reminder" into four things that behave differently, and insists on the split because without it none of the required behaviours can be implemented: a reminder cannot be postponed cleanly, its history cannot be kept, and several notifications for one deadline cannot be delivered correctly.

- The **source** is where the need comes from: a date on a page, an inactivity, a manual entry.
- The **rule** is the logic. That is this model.
- The **occurrence** is a concrete deadline ([ReminderOccurrence](041-reminder-occurrence-not-yet.md)).
- The **notification** is what actually reaches the user ([Notification](042-notification-not-yet.md)).

Reminders are in the core (3.2), not because every use needs them, but because the product has to handle them transversally whatever structure the user built.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the rule belongs to
vault_id                    int                             vault it lives in, null for an account level rule
name                        string (255)                    what the reminder says when it fires
description                 text                            anything more the user wants said
kind                        enum (one_off, recurring, field_based, activity_based, inactivity)  which of the five types it is
remindable_type             string (255)                    class of the record it is about, a contact, an entity or an activity
remindable_id               int                             id of that record
field_id                    int                             date or recurring date field it reads, for a field based rule
field_value_id              int                             exact value it reads, for a field based rule
activity_type_id            int                             nature it watches, for an activity based or inactivity rule
inactivity_days             int                             days of silence before it fires, for an inactivity rule
offset_days                 json                            how many days before or after the date to fire, several allowed
frequency                   enum (daily, weekly, monthly, yearly)  cadence of a manual recurring rule
frequency_interval          int                             every how many of those, for a manual recurring rule
weekdays                    json                            days of the week a manual weekly rule fires on
day_of_month                int                             day a manual monthly or yearly rule fires on
month_of_year               int                             month a manual yearly rule fires in
starts_on                   date                            first date it may fire
ends_on                     date                            last date it may fire, null when it never stops
time_of_day                 time                            local time it fires at
timezone                    string (64)                     time zone frozen at creation, which later changes to the user preference never move
is_active                   boolean                         whether it still produces occurrences
inactive_reason             enum (user, source_deleted, field_removed, record_deleted, record_archived)  why it stopped, so the page can explain instead of hiding it
deactivated_at              datetime                        when it stopped
last_evaluated_at           datetime                        when occurrences were last computed from it
created_by_user_id          int                             user who created it, null when the system did
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `morphTo` remindable
- `belongsTo` [Field](017-field-done.md), [FieldValue](022-field-value-done.md), [ActivityType](035-activity-type-not-yet.md), [User](002-user-done.md)
- `hasMany` [ReminderOccurrence](041-reminder-occurrence-not-yet.md)

## Rules

- **Any `date` or `recurring_date` field can drive a rule** (10.2), on any page, custom or not. A field based rule derives its record, field and exact value from the source, but keeps its own cadence; the creation behaviour and what the user chooses are settled in 10.2 rather than repeated here.
- **The time is local, not absolute** (10.3). "Every year at 9am" means 9am local, across daylight saving changes. The zone is frozen at creation, and changing the personal time zone later never shifts a rule already set. This is why `timezone` is on the rule and not read from the user.
- **Inactivity reads only activities whose type counts as an interaction** (9.3, 10.2). Its quality depends entirely on the journal being kept, which section 10.2 states plainly and which the interface should say too.
- **A change to the source breaks nothing** (10.3). A corrected date recomputes future occurrences and keeps the history. A deleted contact cancels future occurrences and restores them if it comes back. A field removed from a structure disables the rule and the product **explains why it is inactive** rather than making it disappear. That is what `inactive_reason` is for, and it is a direct application of section 3.3.
- **An archived record is a silent record.** Archiving a [Contact](019-contact-done.md) or an [Entity](020-entity-done.md) deactivates every rule about it with `record_archived`, and unarchiving reactivates them and recomputes what is ahead. Archiving exists so somebody stops hearing about a record without losing what is written on it, and a rule that kept firing would defeat the only thing archiving does. The rule stays visible and says why it is quiet, as it does for every other reason (3.3). An inactivity rule is included: an archived record cannot fire for silence.
- **Disabling the rule is the only action a user takes that cancels every future occurrence**, and it is reversible (10.3). Deleting or archiving the record it is about cancels them too, and those are reversible in the same way. Postponing and skipping act on one occurrence and are handled by [ReminderOccurrence](041-reminder-occurrence-not-yet.md).
- A reminder belongs to one account and is never sent to anybody viewing a share link (10.4).
- A rule is not a task (10.5). A task is an action to do, with a status and a completion date. The two are not merged, and when tasks arrive they will reuse this scheduling machinery rather than grow a second one, so nothing here may assume a rule is always about a record.

## Open questions

- How far ahead occurrences are generated. A rolling window is the usual answer and the window length is not specified.
- Whether an inactivity rule can watch a group rather than a single contact ("nobody in the family for a month"). Natural to want, not specified.
