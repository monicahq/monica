# 031. GroupType

**Status:** to create
**Spec:** 8.2, 5.4

## What it is

The kind of a group: household, family unit, couple, social circle, team.

It is a configurable list like the others in section 5.4, and it is its own model rather than a value list because a group type carries behaviour a plain list item does not: whether it has a single address and whether it caps its membership.

## Fields

```
id                          int                             primary key
account_id                  int                             account the type belongs to
key                         string (64)                     stable identifier of a supplied type, null for a user made one
name                        string (255)                    name of the type, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name
description                 string (255)                    what this kind of group is for
icon                        string (64)                     icon shown next to a group of this kind
colour                      string (7)                      hex colour used the same way
has_shared_address          boolean                         whether the group has one address of its own, as a household does
max_members                 int                             ceiling on membership, such as two for a couple, null when there is none
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether new groups of this kind can be created
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [Group](032-group-not-yet.md)

## Rules

- The supplied set covers the five kinds section 8.2 names, and is editable like any other supplied list.
- `max_members` is a warning, not a wall. Three people in a group typed as a couple is unusual, not impossible.
- Deactivating a type leaves existing groups alone.

## Open questions

- Whether a group type should be able to carry its own custom fields through a template, the way an activity type does (9.3). It would be consistent, it is not asked for, and section 15 defers personalising anything beyond contacts and entities.
