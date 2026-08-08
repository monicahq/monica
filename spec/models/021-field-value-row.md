# 021. FieldValueRow

**Status:** to create
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

## Open questions

- Whether a repeater row can be marked as the primary one, so a page can show "the main phone number" without guessing. Common, small, and not in the specification.
