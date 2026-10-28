# 060. AccountImportItem

**Status:** exists (`app/Models/AccountImportItem.php`), built as far as the models it depends on allow
**Spec:** 12. Feature [082](../../specs/082-account-export-import/spec.md)

## What it is

One thing read from an imported file: a contact, a relationship, a note, a file, a journal entry. It exists so a file is read once and written in short batches, and so every item carries what became of it. The same rows are the import's report and the ledger its rollback reads.

## Fields

```
id                          int                             primary key
account_id                  int                             account of the import
account_import_id           int                             import it was read for
vault_source_uuid           string (64)                     vault of the file it belongs to, null for a Monica 4 file
kind                        string (32)                     contact, relationship, life_event, file, reminder, entity, structure, vault, related_values, and so on
source_uuid                 string (64)                     identifier in the file, null when the file gives none
position                    int                             order of writing, dependencies first
payload                     json                            what the file said, cleared once written, except what a structure copy made
outcome                     enum (imported, merged_into_me, left_out)  null until written
reason                      string (255)                    why it was left out, as a translation key
record_type                 string (64)                     morph type of the record it created
record_id                   int                             record it created
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [AccountImport](059-account-import-done.md)
- `morphTo` the record it created

## Rules

- An item with an outcome is never written again, which is what makes a retried batch safe.
- An item that cannot be written is left out with its reason, and the rest of the import goes on. Only a refusal from a cap or a failure of the run itself rolls the whole import back.
- Rollback walks the items in reverse position and hard deletes each record they name.
- An item that made a copy of a structure (a version of a template or of a life event type, named `owner:version` in `source_uuid`, its record the structure version) keeps, once written, which section of the copy each section of the file became, and the value lists and kinds of record the copy made. Records written after it find their values through it, and the rollback removes what it names.

## Open questions

- None.
