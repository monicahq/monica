# 054. SearchIndexEntry

**Status:** to create
**Spec:** 13, 3.7, 9.5

## What it is

The flattened, searchable form of one record. It is what free text search reads, so that searching does not mean scanning [FieldValue](022-field-value-done.md) across every field of every record.

Section 13 sets three requirements that together decide this model exists. Search covers **every** field, including ones invented yesterday, with no configuration from the user. It is tolerant of typing mistakes. And indexing is asynchronous: saving a record stays instantaneous, and the index catches up right behind it.

Structured filtering ("every contact whose city is Paris and whose diet is vegetarian") does **not** go through this table. It queries [FieldValue](022-field-value-done.md) directly on its typed columns, which is exactly what those columns are for. This model serves the free text half.

## Fields

```
id                          int                             primary key
account_id                  int                             account the record belongs to
vault_id                    int                             vault it lives in, so a search never crosses the compartment
searchable_type             string (255)                    class of the record indexed, a contact, an entity, a life event or a group
searchable_id               int                             id of that record
title                       string (511)                    the name or heading, weighted highest when ranking results
body                        longtext                        every searchable value flattened into text
metadata                    json                            what the result card needs, so rendering a result does not load the record
locale                      string (5)                      language the content is mostly in, which decides the stemming applied
indexed_at                  datetime                        when it was last rebuilt
is_stale                    boolean                         whether the record changed since, so the queue knows what to redo
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `morphTo` searchable

## Rules

- Saving a record marks its entry stale and queues a rebuild. It never rebuilds inline (13).
- Every field type contributes its own text form, decided by the type. A new field is searchable the moment it exists, with no configuration, because the type already knows how to flatten itself. Layout fields contribute nothing.
- A choice field contributes the label of the chosen item, not its identifier, so searching for "vegetarian" finds it.
- A field marked sensitive is indexed, because the owner is the only person searching (5.6, 4.1). It is never surfaced through a share link, which reads its own restricted path and not this table.
- Every result is scoped by vault. Search respects the compartment like everything else (4.2).
- Stemming and tokenisation depend on the language (3.7), which is why `locale` is stored per entry rather than assumed from the account.
- Life events are indexed alongside records. Section 9.5 requires a life event to be findable by its content the way a contact is.
- Deleting a record deletes its entry. A soft deleted record is removed from the index and reindexed if it comes back.

## Open questions

- Which engine backs it. A database full text index is enough at first and typo tolerance is where it gets weak, which is exactly what section 13 asks for. The columns above suit either a database index or an external engine, and the choice should be made on measurement.
- Whether marketplace search shares this table. Section 13 says it is a separate need at a smaller scale and will be handled separately, so it should not.
