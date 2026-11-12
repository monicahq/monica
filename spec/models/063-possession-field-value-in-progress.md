# 063. PossessionFieldValue

**Status:** in progress (`app/Models/PossessionFieldValue.php`)
**Spec:** 3.2, specs/091-possessions

## What it is

What one [Possession](062-possession-in-progress.md) holds for one [field](061-possession-field-in-progress.md) of its type: the plate of a car, the date a warranty ends.

## Fields

```
id                          int                             primary key
account_id                  int                             account of the possession
possession_id               int                             possession holding the value
possession_field_id         int                             field it answers, a field of the possession's type
value                       text                            normalised by the field's kind
```

## Rules

- One value per possession and field.
- Text and choices are kept as written, numbers as a decimal string, dates as `Y-m-d`, yes or no as `1` or `0`, a rating as `1` to `5`.
- A choice keeps its wording when the option is later renamed or removed; only a write checks the current options.
- Deleting the field, or changing its kind, deletes its values.
