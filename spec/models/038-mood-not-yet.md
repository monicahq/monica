# 038. Mood

**Status:** to create
**Spec:** 9.4, 5.4

## What it is

One of the general states a day can be marked with. Section 9.4 says the mood is chosen from a list that is itself configurable, and this is that list.

It is a small model, and it earns its own file because a day can hold nothing but a mood, which makes it one of the two ways into the journal rather than a decoration on top of it.

## Fields

```
id                          int                             primary key
account_id                  int                             account the mood belongs to
key                         string (64)                     stable identifier of a supplied mood, null for a user made one
name                        string (255)                    label of the mood, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied label
icon                        string (64)                     icon shown on the day, since a mood reads faster as a symbol
colour                      string (7)                      hex colour used on the calendar view
scale_value                 int                             where it sits on a scale, so moods can be charted and compared over time
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be chosen
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [JournalDay](039-journal-day-not-yet.md)

## Rules

- `scale_value` exists so a year of moods can be drawn as something other than a list of icons. It is an ordering, not a judgement, and a user made mood needs one too.
- Colour and icon are both stored because section 3.5 forbids carrying information by colour alone. A mood that reads only as a coloured square is inaccessible, so the icon is not optional.
- Disabling a mood leaves every day already marked with it untouched (3.3).
- The supplied set is seeded at account creation and fully editable.

## Open questions

- Whether a day can hold several moods, or a mood per part of the day. The specification describes one general mood per day, which is the simpler and probably truer thing.
