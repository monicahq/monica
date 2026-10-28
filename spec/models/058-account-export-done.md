# 058. AccountExport

**Status:** exists (`app/Models/AccountExport.php`), built as far as the models it depends on allow
**Spec:** 4.2, 11, 12. Feature [082](../../specs/082-account-export-import/spec.md)

## What it is

One request to take the data of an account out of Monica, as a file the account holder downloads. It exists as a model because preparing the file takes time and happens on a queue, so the request, its state and the file it produced have to outlive the page that asked for it.

## Fields

```
id                          int                             primary key
uuid                        uuid                            public identifier, used by the API
account_id                  int                             account the data comes from
user_id                     int                             user who asked for it
vault_ids                   json                            vaults it covers, every vault of the account when it was asked
status                      enum (preparing, ready, failed, expired)  where it stands
disk                        string (64)                     filesystem disk holding the file
path                        string (512)                    where the file is, under the account prefix
size_in_bytes               bigint                          size of the file once ready
ready_at                    datetime                        when the file was complete
expires_at                  datetime                        seven days after ready_at, when the file is deleted
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [User](002-user-done.md)

## Rules

- An account prepares one export at a time.
- The file is reachable only by its account holder, signed in or through the API with the `sensitive-fields` ability, and never by a path built from user input (constitution, files).
- Expiry deletes the file and keeps the row, with `status` expired, so the screen can say what happened. `PurgeExpiredAccountExports` runs once a day, and the download stops being offered the moment `expires_at` passes.
- Downloading is the `download` ability of `AccountExportPolicy`: a ready export before `expires_at`. Any other export answers as not found, and the screen draws its Download control refused, pointing at the state the row prints.
- Asking for an export, and each download, is written to the changelog.
- The file does not count against the storage quota: it is a copy of what is already counted, and it is gone after seven days.
- The API reaches exports at `exports`, behind `flag:account-transfer`. Asking for one and downloading its file both answer 403 to a key without the `sensitive-fields` ability; listing and reading them do not.

## Open questions

- None.
