# 029. RelationshipQualifierAssignment

**Status:** to create
**Spec:** 8.2

## What it is

The join between a relationship and the qualifiers stacked on it. It exists as its own file because it carries a little more than a pair of keys, and because the composition rule in section 8.2 is the whole point of the relationship qualifier design.

## Fields

```
id                          int                             primary key
account_id                  int                             account the assignment belongs to
relationship_id             int                             relationship the qualifier is added to
relationship_qualifier_id   int                             qualifier being added
side                        enum (a, b, both)               which end it describes, since a link can be legal one way and social the other
notes                       string (255)                    anything the user wants to note about this particular nuance
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Relationship](026-relationship-not-yet.md)
- `belongsTo` [RelationshipQualifier](028-relationship-qualifier-not-yet.md)

## Rules

- A qualifier is assigned at most once per relationship per side.
- `side` defaults to `both`, which is what most nuances mean. It exists because a blended family produces links that are not the same nuance in both directions, and the specification takes blended families seriously enough to name them.
- Adding or removing a qualifier is an event on the relationship timeline (8.4).
- Deleting the relationship deletes its assignments. There is nothing to keep.

## Open questions

- Whether `side` earns its keep, or whether it is over-modelling something users will never touch. It is one column and removing it later is a migration, so it is listed rather than assumed.
