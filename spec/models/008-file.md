# 008. File

**Status:** exists (`app/Models/File.php`), built as far as the models that point at it allow
**Spec:** 11, 4.4, 9.2

## What it is

An uploaded file: a photo, a document, a scan, an avatar. Files are attached to records through fields (5.5), to activities (9.2), and to nothing else.

The model exists separately from the value that points at it for three reasons stated in section 11. The product must know at any moment which files are actually in use, so the storage quota is reliable. It must be able to delete orphans automatically after a grace period. And that grace period has to be long enough for restoring an old version of a record to recover a file detached a moment ago, which is impossible if detaching deletes.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in every path and URL, never the id
account_id                  int                             account the file belongs to
vault_id                    int                             vault the file belongs to, null for account level files such as the user photo
disk                        string (32)                     Laravel disk the file was written to, so a moved instance still resolves old files
path                        string (512)                    path on that disk, always under the account and vault prefix
original_name               string (255)                    name the file had on the machine it came from
mime_type                   string (127)                    mime type as detected on upload, not as claimed by the browser
size_in_bytes               int                             size, counted against the account quota
checksum                    string (64)                     hash of the contents, used to spot a duplicate upload
width                       int                             width in pixels for an image, null otherwise
height                      int                             height in pixels for an image, null otherwise
variants                    json                            the derived sizes written beside it, each with its suffix and pixel size
reference_count             int                             how many values or activities currently point at it, zero meaning orphan
orphaned_at                 datetime                        when the count last reached zero, which starts the grace period
uploaded_by_user_id         int                             user who uploaded it, null when an import or a module did
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [Vault](007-vault.md)
- `belongsTo` [User](002-user.md) as uploader
- referenced by [FieldValue](022-field-value.md) and [Activity](036-activity.md)

## Rules

- Storage goes through the disk configured in Laravel (11). Nothing in the application may assume local disk or S3. Self hosted picks its own driver, hosted picks ours, and the behaviour is identical either way.
- The path is always prefixed by account then vault. Download, preview, asynchronous processing, export, module and share link all apply the same prefix check. A file is never served by path from user input.
- Public URLs use `uuid`, never `id`. Sequential identifiers in a URL let somebody walk the table.
- `reference_count` is maintained when a value or an activity attaches or detaches. When it reaches zero, `orphaned_at` is stamped. When it rises again, `orphaned_at` is cleared.
- A file whose `orphaned_at` is older than the grace period is deleted by a scheduled job, which also decrements the account total. The grace period is configuration, and it must be longer than the window in which a snapshot can be restored ([RecordSnapshot](023-record-snapshot.md)), otherwise a restore recovers a pointer to nothing.
- An upload that would push `storage_used_in_bytes` past the account limit is refused before the bytes are written, not after.
- Nothing is deleted silently (3.3). A file removed by the orphan sweep is logged.
- Images are processed asynchronously into their variants. The original is kept.

## Not done yet

- **Nothing points at a file.** [FieldValue](022-field-value.md) and [Activity](036-activity.md) are both ahead, so `reference_count` is moved by `AttachFile` and `DetachFile` and nothing calls either yet. [Contact](019-contact.md) carries an `avatar_file_id` with its constraint, and nothing writes to it either. Every file uploaded today is an orphan from the moment it lands, which is exactly what the count says.
- **No screens and no API.** `CreateFile` takes an upload, checks the room and writes the row; nothing uploads anything through it. The user photo still writes its own two versions outside this model, and moves onto it when the record renderer needs a file at all.
- **`variants` stays null.** Which sizes an image is derived into is a question for whatever renders one, and writing a set of sizes before anything reads them would be guessing. The column and the asynchronous processing arrive together.
- **The checksum is written and never read.** Deduplication is an open question below, and until it is answered the hash is only what would make answering it possible.

## Decisions taken while building it

- **The sweep is the only thing that deletes.** `DetachFile` never removes anything: it stamps the moment the last thing let go, and `PurgeOrphanedFiles` comes for it once the grace period has run out. That gap is the point of the model, and it is configuration (`monica.orphaned_file_grace_period_in_days`) so an instance can keep files longer than it keeps snapshots.
- **The bytes go and the row stays.** Deleting soft deletes, so what was uploaded, by whom and when survives the file itself. Section 3.3 asks that nothing disappear silently, and a row nobody can see is still better than no record at all.
- **The room is checked before a byte is written.** An upload that would take the account past its ceiling is refused outright rather than written and then found to be one file too many.
- **The disk is read from the row rather than from the configuration.** An instance that changes disk has to go on resolving everything it wrote before, which is what the column is for.
- **The path never contains anything the uploader chose.** The name they had on their machine is kept on the row so a download hands it back, and what is written is the uuid, under the account and then the vault.

## Open questions

- Whether identical uploads are deduplicated by `checksum` inside a vault. It saves storage and complicates deletion, since a file would then have references from unrelated records. Not needed for the first version.
- Virus scanning on the hosted version. Out of the specification, but it is the kind of thing that is painful to add after the fact.
