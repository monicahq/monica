# Research: Account export and import

## R1. An import is read once into item rows, then written in short batches

- **Decision**: uploading stores the file on the configured disk; `ReadAccountImport` streams it once and writes one `account_import_items` row per item (kind, source uuid, payload), decoding embedded files to the disk beside it. The preview counts those rows. On confirmation, `WriteAccountImportBatch` takes the next 50 pending items in dependency order, writes them, records each outcome and the record it created, and dispatches itself until none are left.
- **Rationale**: `retry_after` is 90 seconds and the worker retries (`config/queue.php:45`, `docker-compose.yml:33`), so a job that runs for minutes is started twice. Short batches finish inside it, and an item that already has an outcome is skipped when a batch is retried. The same rows are the report (FR-011) and the ledger rollback reads (R2).
- **Rejected**: one long job (retried while running); re-reading the file from the start for each batch (a 500 MB file read twenty times).

## R2. Rollback deletes what the item rows say was created

- **Decision**: a failure, a cancellation or an exhausted retry dispatches `RollBackAccountImport`, which hard deletes every created record named on the items in reverse order, files and their storage total included, and undoes the Me merge with `UndoContactMerge`. Every vault the import created is destroyed whole.
- **Rationale**: answer 2 makes an import purely additive, so removing what it added restores the account exactly (FR-008, SC-003).
- **Rejected**: one database transaction around the import (minutes long, holds the SQLite writer lock for the whole instance, and hides progress until commit).

## R3. Reading large JSON needs a streaming parser: `halaxa/json-machine`

- **Decision**: iterate `/account/data` and `/account/properties/*` with `JsonMachine\Items` (pure PHP, Apache 2.0, no extension; 1.2.6 installed), one item at a time.
- **Rationale**: `json_decode` of the 27 MB example peaks near 200 MB; a 500 MB file would need well over 1 GB in a worker. The project has no streaming parser (`composer.lock`).
- **Approved** by the owner on 2026-10-18.

## R4. The Monica export is one JSON file in the Monica 4 envelope, files embedded

- **Decision**: `{"version": "2.0-preview.1", "app_version", "exported_at", "account": {...}}`, the key Monica 4 opens with (`1.0-preview.1`), so the version alone tells the two apart, written with `fwrite` as it goes, each file base64 encoded through a stream filter. Shape in [contracts/export-format.md](contracts/export-format.md).
- **Rationale**: one reader (R3) serves both formats, and writing needs no library. Base64 costs a third more bytes, which the seven day life of the file absorbs.
- **Rejected**: a zip archive (`ext-zip` is not required by `composer.json`, and a second reading path).

## R5. Templates travel as structure files, one per version in use

- **Decision**: the export carries a `MarketplacePayload` document for every structure version a record of the vaults points at. Import writes each through `StructureWriter` as a published version of a new template, in version order, and records point at the copy of their own version.
- **Rationale**: `ImportTemplate` creates a single draft (`ImportTemplate.php:89-96`), so records could not keep their version (spec US3, constitution VI). Value lists already map by key, otherwise are copied (`StructureWriter.php:129-181`).

## R6. Monica 4 contacts go through the existing actions

- **Decision**: `CreateContact` with the vault's default version (caps, slug, duplicate detection on `low`), values through `SetFieldValue` on the field found by `DefaultFieldEnum` key in that version, as `OwnedFields` does by type. `created_at` is set with `forceFill` after creation (FR-010). Me is `MergeContacts` with every conflict decided `kept` (answer 4).
- **Mapping**: [contracts/monica-4-mapping.md](contracts/monica-4-mapping.md). Relationships arrive twice in Monica 4, once per direction; the reverse row is skipped.
- **Reminders**: `CreateContactReminder` refuses a past start (`:97`), so a recurring reminder starts at its next occurrence and a past one time reminder is left out and reported.

## R7. Caps are counted from the items before confirmation

- **Decision**: contacts not partial, vaults (one per vault of an Monica file of several, FR-003), and the decoded file bytes are compared with `contactLimit()`, `vaultLimit()` and `storageLimitInBytes()` on the account. `CreateContact` and `CreateFile` still check as they write, so a race ends in a rollback, not an overrun.

## R8. Flag, notifications, changelog, API

- **Flag**: `account-transfer` kept the work out of sight until all three stories landed, then was removed in the same branch (`.claude/rules/flags.md`). Deployment runs `php artisan pennant:purge --except-registered`.
- **Email**: `AccountExportReady` and `AccountImportEnded` notifications with `HasEmailType`; the screen shows the state, since in-app notifications exist only for reminders.
- **Changelog**: four `UserActionEnum` cases; imported records log with `LogSourceEnum::Import` and the import uuid as `source_detail`.
- **API**: an export answers only to a key holding `sensitive-fields`, since the file carries every sensitive value. Shapes in [contracts/api.md](contracts/api.md).

## R9. An Monica file is read into the same items, one vault item per vault

- **Decision**: `ReadMonicaFile` streams the export with the pointers of R3 and writes one item per vault, structure version, own type, file and record, each carrying `vault_source_uuid`. A file of one vault behaves like a Monica 4 file: the person picks the vault and Me, the file's `is_me` contact preselected. In a file of several, writing a `vault` item (rank 0) creates that vault and adds it to `created_vault_ids`; every other item lands in the vault its `vault_source_uuid` names, and each vault gets a `me` item for its `is_me` contact. The cap counts one vault per `vault` item.
- **Structures**: the first version of a template is written with `ImportTemplate` and published, each later one as a new version of that copy through `StructureWriter`, in version order. The copy's sections take new slugs when the account already uses the file's, so the structure item keeps, once written, `{sections: {file slug: section version id}, value_lists, entity_types}`. Values are found through that map, by slug where there is none (shipped life event types, a vault default), and the rollback removes what the map names. A life event type, built in or own, travels the same way, one document per version a life event is on: the first version a type needs copied goes through `ImportLifeEventType`, each later one becomes a new version of that copy, and every item keeps the same map. The account's own type of that key or name is used instead, with no copy, when it holds every field of the version (same section slug, field key and kind).
- **Order**: the values of a record pointing at another record wait in a `related_values` item (rank 4) until every record exists, and reminders follow them (rank 5), since a reminder can read a date among them. Two rows of one repeater may share a position, as a merge leaves them, so a field met twice in a row opens a new row.
- **Me**: the contact chosen as Me is written on the structure version of the vault's Me, because `MergeContacts` carries values by field and Me's template wins (answer 4).
- **Entities** are written directly: `CreateEntity` accepts only the vault default of the type, and refuses a kind of record the vault has no default for.
- **Reader**: `StructureFileReader` refused the application's own template files (the label lists of email, phone and address fields, the open label key, the `distinct_in_list` option). It reads them now.
- **Rejected**: a vault chosen per vault of the file (a screen for a case a new vault covers); finding sections by page and position (ties every record to the copy's assembly order).
