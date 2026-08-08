# 030. RelationshipSuggestion

**Status:** to create
**Spec:** 8.3, 3.1

## What it is

A relationship the product thinks probably exists, waiting for the user to say yes or no. Two contacts sharing a parent are probably siblings. A partner of a parent is probably a stepparent.

Section 8.3 sets two conditions on suggestions and both are stored here. A suggestion always explains where it came from and with what confidence. And a suggestion is never recorded as confirmed without validation, which is the general principle of section 3.1 applied to inference: nothing changes under somebody's feet, including when the product is the one doing the changing.

Keeping suggestions in their own table, rather than as unconfirmed rows in [Relationship](026-relationship.md), is what makes that guarantee structural. A query for relationships cannot accidentally return a guess.

## Fields

```
id                          int                             primary key
account_id                  int                             account the suggestion belongs to
vault_id                    int                             vault both contacts live in
relationship_type_id        int                             link being suggested
contact_a_id                int                             one end
contact_b_id                int                             the other end
confidence                  enum (high, medium, low)         how sure the product is, shown to the user before they decide
reason                      string (255)                    why it was suggested, in words, such as they share a parent
derived_from                json                            the exact relationships the inference walked, so the reason can be shown in full
status                      enum (pending, accepted, rejected, expired)  what the user did with it
resolved_at                 datetime                        when they decided, null while it is pending
resolved_relationship_id    int                             relationship created on acceptance, null otherwise
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [RelationshipType](024-relationship-type.md)
- `belongsTo` [Contact](019-contact.md) twice
- `belongsTo` [Relationship](026-relationship.md) as the result of acceptance

## Rules

- A suggestion is never a relationship. It appears on a suggestions surface and never in the relationship list of a contact page.
- Every suggestion carries a reason readable by a human and a machine readable chain behind it. Section 8.3 requires the product to say where a suggestion comes from, always.
- A rejected suggestion is remembered and never offered again. Otherwise the engine reproposes it on every run, which is the fastest way to make a good feature hated.
- Accepting one creates a real relationship and links back to it.
- A suggestion whose supporting relationships were deleted is expired rather than deleted, so the log still explains what happened.
- Suggestions are computed in the background, never during a save.

## Open questions

- Whether deduced relationships that are merely computed for display (grandparent, cousin, and the rest of section 8.3) should also produce rows here, or be computed on read and never stored. Computing on read is simpler and correct by construction. Storing is faster on a large tree. The distinction between deduced and suggested is clear in the specification: deduced links need no entry, suggested ones need confirmation. Only suggested ones belong in this table.
