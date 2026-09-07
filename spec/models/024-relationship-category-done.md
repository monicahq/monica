# 024. RelationshipCategory

**Status:** done
**Spec:** 8.2

## What it is

An account-owned display group for relationship types. Examples are Family, Romantic, Friendship, Professional and Other.

A category only organises the settings screen and the relationship picker. It never changes the meaning of a relationship. Moving a type into Family does not make it genealogical, and moving a supplied genealogical type elsewhere does not remove its protected behaviour.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the category belongs to
key                         string (64)                     stable identifier of a supplied category, null for a user-created one
name                        string (255), nullable          name the user gave it, null while the supplied wording stands
name_translation_key        string (255), nullable          the supplied wording, read through the translator
is_system                   boolean                         whether the product supplied it with the account
is_active                   boolean                         whether it is shown when choosing a type for a new relationship
position                    int                             order it appears in settings and pickers
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [RelationshipType](025-relationship-type-done.md)

## Rules

- A new account starts with Family, Romantic, Friendship, Professional and Other.
- The user may create, rename, reorder and deactivate categories.
- A supplied category cannot be deleted. Its `key` and `is_system` values never change.
- A user-created category may be deleted only when it contains no relationship type. Otherwise the types must first be moved or the category deactivated.
- Deactivating a category removes it and its types from the creation picker. Existing relationships remain readable.
- Category names and positions have no semantic effect.

