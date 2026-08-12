# 021. FieldValueRow

**Status:** exists (`app/Models/FieldValueRow.php`), settled
**Spec:** 5.5

## What it is

One repetition of a repeater. It exists so that "phone number 2" knows its label and its number belong together.

Section 5.5 describes the repeater as the field that lets the same block be filled several times: several phone numbers, several addresses. Without a row, the values of a repeater are a flat bag and there is no way to say which number goes with which label. It is a small model that removes an entire category of ambiguity.

It comes before [FieldValue](022-field-value.md) in the order because a value points at its row.

## Fields

```
id                          int                             primary key
account_id                  int                             account the row belongs to
valuable_type               string (255)                    class of the record the row belongs to, a contact, an entity or an activity
valuable_id                 int                             id of that record
field_id                    int                             repeater field this is a repetition of
position                    int                             order the repetition appears in, which the user can reorder
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `morphTo` valuable, which is a [Contact](019-contact.md), an [Entity](020-entity.md) or an [Activity](036-activity.md)
- `belongsTo` [Field](017-field.md), which must be a repeater
- `hasMany` [FieldValue](022-field-value.md)

## Rules

- `field_id` always points at a field whose `is_repeatable` is true.
- Every value inside a repeater carries the row it belongs to. Values outside one carry null.
- Deleting a row deletes the values in it, and detaches any file they held so the orphan sweep in [File](008-file.md) can pick it up after the grace period.
- Reordering rows changes `position` only. It never rewrites values, and it never changes which row a value belongs to.
- Rows are counted against `repeat_min` and `repeat_max` on the field at save time.

## Not done yet

- **An activity holds no repetitions,** because [Activity](036-activity.md) does not exist. The morph is written to take one the day it does; what exists today is a repetition on a contact or an entity.
- **No screen and no API.** Model and actions only, so nothing drags a repetition up the page yet. `UpdateFieldValueRowPosition` is what a drag will call.

## Decisions taken while building it

- **The row carries no uuid.** The conventions in this folder give one to what a user can open, link to or address as a resource, and a repetition is none of those: it is reached through the record holding it.
- **Deleting a repetition is an action rather than a model hook.** Taking the values out means letting go of the files they held, and letting go is `DetachFile`, which needs the user. A hook has no user, so `DestroyFieldValueRow` is where it lives and the model does not pretend to cover a path around it.
- **The repetition limits are counted at the moment one is added or removed,** not on the whole record at save time. `repeat_max` refuses one repetition too many and `repeat_min` refuses taking the last one away, which is the point at which somebody can be told something useful.
- **Positions are renumbered from one after a removal,** so they stay a sequence rather than a set with a hole in it. Nothing reads a position as an identity, and a page that renders them in order should not have to think about gaps.

## Open questions

- Whether a repeater row can be marked as the primary one, so a page can show "the main phone number" without guessing. Common, small, and not in the specification.
