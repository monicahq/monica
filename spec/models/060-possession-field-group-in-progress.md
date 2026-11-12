# 060. PossessionFieldGroup

**Status:** in progress (`app/Models/PossessionFieldGroup.php`)
**Spec:** 3.2, specs/088-possession-types

## What it is

A named set of [fields](061-possession-field-in-progress.md) within a [PossessionType](059-possession-type-in-progress.md), such as "Purchase" or "Technical details", which keeps a type with many fields readable.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account the group belongs to, the type's
possession_type_id          int                             type the group belongs to
name                        string (255)                    what it is called, required
position                    int                             order among the groups of the type, from 1
```

## Rules

- A new group is named "Untitled group" and placed last. An empty name is refused afterwards.
- Removing a group keeps its fields: they become standalone fields of the type, after the existing ones, in their order.
