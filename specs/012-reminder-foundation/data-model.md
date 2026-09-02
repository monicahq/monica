# Data model: the reminder rule

One table: `spec/models/040`'s columns, less the ones nothing can write yet, with the corrections [research.md](research.md) argues.

## `reminders`

```
id                    int             primary key
uuid                  string (36)     public identifier, immediately after id per the models README
account_id            int             account the rule belongs to
vault_id              int             vault it lives in
remindable_type       string (255)    class of the record it is about, a contact today
remindable_id         int             id of that record
field_id              int             the date field it reads
field_value_id        int             the exact value it reads, null once it is cleared
name                  string (255)    what the reminder says when it fires
source                string (32)     where the need comes from: field_value
cadence               string (32)     once, weekly, monthly, yearly
frequency_interval    tinyint         how many of those between firings, 1 unless a rhythm was set
moments               json            list of {direction, amount, unit}, at least one
time_of_day           time            local time it fires at
timezone              string (64)     zone frozen at creation, which a later preference never moves
is_active             boolean         whether the rule still produces anything
inactive_reason       string (32)     value_cleared, record_archived, record_deleted, field_removed
deactivated_at        datetime        when it stopped
created_by_user_id    int             who created it
created_at            datetime
updated_at            datetime
deleted_at            datetime        untick is a soft delete, so the rule can come back
```

Foreign keys at the foot, none with `constrained()`: `account_id`, `vault_id` and `field_id` cascade,
`field_value_id` and `created_by_user_id` null. `field_value_id` **must** null rather than cascade:
clearing a date destroys its value row, and a rule cascaded away with it is the silent deletion
section 3.3 forbids. It is indexed and not unique, because a unique index cannot say "one *live* rule
per value" under soft deletes: MySQL skips the check whenever a key column is null. FR-010 is enforced
in `CreateReminder`, the way `is_unique` is already enforced in `SetFieldValue`. Also indexed:
`account_id` with `is_active`, `vault_id`, and `remindable_type` with `remindable_id`.

## Relationships

`belongsTo` Account, Vault, Field, FieldValue, User, and `morphTo` remindable. The other way: FieldValue `hasOne`, Field `hasMany`, Contact `morphMany`.

## States

| From | Event | To |
| --- | --- | --- |
| live, active | the value is cleared, the record archived or deleted, the field removed | live, inactive with that reason |
| live, inactive | the same cause is reversed | live, active |
| live, inactive | a *different* cause is reversed | unchanged, still saying why |
| live | the box is unticked | removed, restorable |


## Corrections to record in `spec/models/040`

| In that file today | What it becomes |
| --- | --- |
| `kind enum (one_off, recurring, field_based, activity_based, inactivity)` | two columns, `source` and `cadence`, because 10.2 makes them independent |
| `offset_days json` | `moments json`, each carrying a direction, an amount and a unit |
| `frequency enum (daily, weekly, monthly, yearly)`, `starts_on` | folded into `cadence`, which also carries `once`; the value the rule reads is where it starts |
| `inactive_reason` including `source_deleted` | four reasons ship; `user` has no producer while untick is a delete |
| `description`, `activity_type_id`, `inactivity_days`, `weekdays`, `day_of_month`, `month_of_year`, `ends_on`, `last_evaluated_at` | not created yet, each named in that file's **Not done yet** with what it waits on |