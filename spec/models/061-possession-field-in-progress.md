# 061. PossessionField

**Status:** in progress (`app/Models/PossessionField.php`)
**Spec:** 3.2, specs/088-possession-types

## What it is

One detail a [PossessionType](059-possession-type-in-progress.md) asks for: a serial number, a power rating, the end of a warranty. It sits in one [group](060-possession-field-group-in-progress.md) of its type, or stands alone.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account the field belongs to, the type's
possession_type_id          int                             type the field belongs to
possession_field_group_id   int                             group it sits in, null when it stands alone
name                        string (255)                    what it is called, required
kind                        string                          text, number, date, boolean, select or rating
options                     json                            the choices of a select field, null otherwise
position                    int                             order within its group, or among the standalone fields, from 1
```

## Rules

- A new field is named "Untitled field", is a text field, and is placed last in its set. An empty name is refused afterwards.
- The group is chosen when the field is created and belongs to the same type; a field does not move between groups.
- The options of a select field are distinct and non empty, at most 200, and are dropped when the kind leaves select.
- A rating holds one to five.
