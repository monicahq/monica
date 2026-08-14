# 025. RelationshipLabel

**Status:** to create
**Spec:** 8.2, 3.7

## What it is

How a relationship type reads on one side, for one language, for one gender. "Father", "Mother", "Parent", "Dad", "Stepfather", "Guardian" are all labels of the same parent to child type.

Section 8.2 makes this a separate layer on purpose, and the payoff is concrete: without it, every combination of type, gender and language becomes its own relationship type, and the picker becomes unusable in exactly the situations that matter most (a non binary parent, a language with no neutral word, a family that says "papi" and not "grandfather").

## Fields

```
id                          int                             primary key
account_id                  int                             account the label belongs to
relationship_type_id        int                             type the label words
side                        enum (a, b)                     which end of the type it words, the parent end or the child end
key                         string (64)                     stable identifier of a supplied label, null for a user made one
label                       string (255)                    the word itself, null when it falls back to its translation key
label_translation_key       string (255)                    translation key of a supplied label
gender_value_list_item_id   int                             gender this wording is for, null when it reads for anybody
locale                      string (5)                      language it is written in, null when it is a supplied label translated everywhere
is_default                  boolean                         whether it is the wording proposed first for that side and gender
is_system                   boolean                         whether the product supplied it
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [RelationshipType](024-relationship-type-not-yet.md)
- `belongsTo` [ValueListItem](010-value-list-item-done.md) as the gender it words
- referenced by [Relationship](026-relationship-not-yet.md) on each side

## Rules

- A label words one side of one type. A non symmetric type has labels on both sides and they are different words.
- Gender comes from the gender value list (5.4), which the user controls, so a label can be attached to whatever gender values that account actually uses. Nothing here hardcodes a set of genders.
- A supplied label is translated by the product in every locale. A user written label is stored as typed, in one language, and is not translated (3.7).
- Each side of a relationship stores its own label independently ([Relationship](026-relationship-not-yet.md)). Ross calling Monica "my little sister" while Monica calls Ross "my brother" is the example from the specification, and it works because the label lives on the side, not on the pair.
- Deleting a label is refused while a relationship uses it. It is deactivated instead.

## Open questions

- Whether a label can be created inline while recording a relationship, or only from settings. Inline is much better for the family nickname case, which is most of the point of this model.
