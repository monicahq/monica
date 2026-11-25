# Data model

Two new tables, one new index on each record table. Rationale in [research.md](research.md) R2 and R5.

## `template_changes`

One decision moving many records of one kind, in one vault, onto one version.

| Column | Type | Notes |
| --- | --- | --- |
| `id`, `uuid` | | uuid is the API address and the log's source detail |
| `account_id`, `vault_id`, `user_id` | foreign keys | `user_id` is who confirmed |
| `entity_type_id` | nullable foreign key | null for People |
| `from_template_id` | foreign key | the source template |
| `from_version_ids` | json, list of int | the versions of the source the batch took |
| `structure_version_id` | foreign key | the target version |
| `items_total`, `moved_count`, `skipped_count`, `failed_count` | unsigned int | counts the result screen reads |
| `ended_at` | nullable timestamp | null while some item lacks an outcome; set once, never again |
| `created_at`, `updated_at` | | |

## `template_change_items`

One record of a batch, and what became of it.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | | |
| `account_id`, `template_change_id` | foreign keys | cascade on the change |
| `record_type`, `record_id` | morph | `Contact` or `Entity`, no foreign key |
| `from_version_id` | foreign key | the version the preview saw |
| `outcome` | nullable `TemplateChangeOutcomeEnum` | `Moved`, `Skipped`, `Failed`; null until done |
| `reason` | nullable `TemplateChangeReasonEnum` | `deleted`, `archived`, `changed_after_preview`, `confirmed_by_deleted_user`, `move_failed` |

Indexes `(template_change_id, outcome)` for the next slice and the counts, `(record_type, record_id)` for R8.

## Indexes on existing tables

- `contacts`: `(vault_id, deleted_at, structure_version_id, is_archived, is_partial)`.
- `entities`: `(vault_id, entity_type_id, deleted_at, structure_version_id, is_archived)`.

## State

No status column and no state machine. A change is running while `ended_at` is null; the slice that finds no item without an outcome writes the counts and `ended_at`. A record is held by another change while it has an item without an outcome (research R8). A slice whose attempts are spent marks its remaining items `failed`, so a change always ends. The one log entry is written at confirmation (research R7). A change with `ended_at` is never written again.

## Not stored

Statistics, field completion and the preview are computed on each request.
