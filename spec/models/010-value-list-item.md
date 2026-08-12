# 010. ValueListItem

**Status:** exists (`app/Models/ValueListItem.php`), built as far as the models it depends on allow
**Spec:** 5.4, 3.3

## What it is

One choice inside a value list. "Mobile" inside phone labels, "France" inside countries, "Close friend" inside contact categories.

## Fields

```
id                          int                             primary key
account_id                  int                             account the item belongs to
value_list_id               int                             list the item belongs to
key                         string (64)                     stable identifier for a supplied item, such as an ISO code, null for a user made one
label                       string (255)                    label the user gave the item, null when it falls back to its translation key
label_translation_key       string (255)                    translation key of a supplied label
description                 string (255)                    clarification shown next to the label when the label alone is ambiguous
colour                      string (7)                      hex colour, used where the item is rendered as a pill
icon                        string (64)                     icon name, used the same way
position                    int                             order the item appears in, which the user controls
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be picked, false disables it without breaking existing records
usage_count                 int                             how many stored values point at it, so the user sees the cost of disabling it
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [ValueList](009-value-list.md)
- referenced by [FieldValue](022-field-value.md)

## Rules

- Disabling an item hides it from every picker and leaves every stored value that uses it untouched and still readable (3.3, 5.4). This is the explicitly required behaviour, not an implementation choice.
- Deleting an item is only allowed when `usage_count` is zero. Otherwise the user is offered disabling instead, with the count shown.
- Position is user controlled and must survive an item being disabled and re-enabled.
- `usage_count` is maintained on write. Counting on read means aggregating the value table, which is the largest table in the product.

## Not done yet

- `usage_count` is now kept in step by [FieldValue](022-field-value.md): `SetFieldValue` hands the count from the item somebody was on to the one they moved to, `DestroyFieldValue` gives it back, and restoring a snapshot moves both ways at once. `DestroyValueListItem` refuses an item the count says is used, and the count is finally true rather than merely written.
- Disabling hides an item from every picker there is, and there is one: a choice field on a record offers the active items and `FieldValueRules` accepts only those, so the form and its validation cannot disagree. A record already holding a disabled item goes on reading it, which is the half of the rule (3.3) that matters most and the one that is hardest to notice is working.
- Merging one item into another. Untouched, and still an open question rather than a task.

## Open questions

- Whether an item can be merged into another ("Mobile" absorbing "Cell"), which would rewrite stored values. Useful, and it rewrites data the user did not ask to have rewritten, so it needs an explicit confirmation flow before it is built.
