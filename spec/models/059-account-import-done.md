# 059. AccountImport

**Status:** exists (`app/Models/AccountImport.php`), built as far as the models it depends on allow
**Spec:** 4.3, 11, 12. Feature [082](../../specs/082-account-export-import/spec.md)

## What it is

One run of a file, a Monica 4 export or an Monica export, into a vault of the account. The run is long, happens on a queue, can be cancelled and must be undone completely when it fails, so its state, its choices and its report need a home of their own. The items it read are [AccountImportItem](060-account-import-item-done.md).

## Fields

```
id                          int                             primary key
uuid                        uuid                            public identifier, used by the API and as the changelog's source detail
account_id                  int                             account the data goes into
user_id                     int                             user who started it
source_format               enum (monica_4, monica)          what the file is
source_checksum             string (64)                     sha256 of the file, to say it was imported here before
disk                        string (64)                     filesystem disk holding the uploaded file
path                        string (512)                    where the uploaded file is, under the account prefix
status                      enum (reading, refused, read, writing, rolling_back, rolled_back, rollback_failed, completed)  where it stands
refusal                     text                            why the file was refused, null otherwise
vault_id                    int                             vault chosen for a file of one vault, null until confirmed or for a file of several
created_vault_ids           json                            vaults the import created, one per vault of a file of several
me_source_uuid              string (64)                     source uuid of the contact chosen as Me, null for none
summary                     json                            per kind, how many were found and how many will not come over
source_references           json                            what the file says its own identifiers mean (genders, field kinds, natures), used while writing
items_total                 int                             items to write
items_done                  int                             items written, for the progress shown
ended_at                    datetime                        when it completed or finished rolling back
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [User](002-user-done.md) and [Vault](007-vault-in-progress.md)
- `hasMany` [AccountImportItem](060-account-import-item-done.md)

## Rules

- An account runs one import at a time.
- An Monica file of several vaults writes each into a new vault of its own, created when its vault item is written and added to `created_vault_ids`, with that vault's Me taken from the file, and counts every one against the vault cap.
- Nothing is written to the vault before the person confirms, and confirmation is refused when the summary crosses a cap of the account.
- An import only adds. It never changes or removes a record or a value that existed before it started, so rolling back removes exactly what its items say it created, destroys every vault it created, and removes every template copy it made.
- The uploaded file and the decoded files of items not used are deleted when the import ends, whatever the outcome.
- Every record it writes logs with source `import` and this uuid as source detail.

## Open questions

- How long a completed import and its report are kept. Kept until the account is deleted, for now.
