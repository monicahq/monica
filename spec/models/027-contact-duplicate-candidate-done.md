# 027. ContactDuplicateCandidate

**Status:** exists (`app/Models/ContactDuplicateCandidate.php`)
**Spec:** 15, 8.3, 3.3

## What it is

Two contacts of one vault that look like the same person, with the reason for thinking so.

Section 15 puts duplicate detection in the first version alongside merging. Detection is the half that finds the pair; [ContactMerge](034-contact-merge-done.md) is the half that joins it. They are separate models because they answer separate questions: this one says "these two might be one person and here is why", and that one says "they were made one, here is what moved".

It is a table rather than a query run when the screen opens because the comparison is too expensive to do at read time in a vault of ten thousand people, and because a dismissal has to be remembered.

## Fields

```
id                          int                             primary key
account_id                  int                             account the pair belongs to
vault_id                    int                             vault both contacts live in
contact_id                  int                             lower of the two ids
other_contact_id            int                             higher of the two ids
score                       tinyint                         how strong the resemblance is, 0 to 100
reasons                     json                            one entry per signal that fired, each naming its kind and, for a field, the key and the shared value
dismissed_at                datetime                        when somebody said these are two different people, null while the pair stands
dismissed_by_user_id        int                             user who said so
computed_at                 datetime                        when detection last looked at this pair
created_at                  datetime
updated_at                  datetime
```

The pair is always stored with the lower id first, and `contact_id, other_contact_id` is unique. Detection looks from both sides and would otherwise write the pair twice.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Contact](019-contact-done.md) twice
- `belongsTo` [User](002-user-done.md), the one who ruled the pair out

## Rules

- A pair holds two different contacts of one vault. It never crosses a vault, and never crosses an account (3.3).
- `Moi` is never proposed as a duplicate of anybody (4.3).
- A dismissal is permanent. Detection recomputes freely and leaves a dismissed row alone, so the pair is never raised again even though it still resembles itself.
- A pair that stopped matching is deleted, unless it was dismissed.
- Detection never joins two contacts. It proposes, and a person decides (8.3, "no deduction or suggestion becomes a fact without an explicit user action").
- Detection runs on a queue, never on the path of a save. Creating or renaming a contact queues a look at that contact; a nightly pass looks at every vault, which is what notices a template that has just gained an identifying field.
- Two signals produce a pair. Names that resemble each other, compared on the given names since the family name is what narrowed the field in the first place. And an exact shared value in a field marked identifying on the structure, which is what catches a pair whose names do not resemble each other at all.
- `score` orders the screen and says nothing about certainty. A pair scoring 95 is still only a proposal.

## Not done yet

- Nothing detects a duplicate across an incoming file, because no import exists. When one does, it reads this model rather than growing a second notion of a duplicate.
- Entities are not compared. See the settled note in [ContactMerge](034-contact-merge-done.md).

## Open questions

- Whether the reasons should record which name comparison fired and how strongly, rather than only that a name matched. It would make the screen more explicit and the row larger, and nothing needs it yet.
