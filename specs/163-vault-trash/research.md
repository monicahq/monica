# Research: Vault trash

Facts about the current code are in the spec's "Context found in the code". This file holds the decisions.

## R1. How a dependent remembers its owner

**Decision**: nullable `deleted_with_type` and `deleted_with_id` on `relationships`, `life_events`, `reminders`, `group_members`, `group_notes` and `group_files`, set in the same transaction as the owner's deletion and cleared on restore. Full class names are stored, as `reminders.remindable_type` already does (there is no morph map).
**Rejected**: matching equal `deleted_at` timestamps (breaks on a second deletion in the same second); a deletion batch table (one more model for a pointer).

## R2. What goes with an owner

| Owner | Dependents set aside with it |
| --- | --- |
| Contact | live relationships on either side; live group memberships; reminders whose remindable is it; life events whose only contact or entity participant is it |
| Group | live memberships, notes, reminders, group files |
| Entity | nothing |

A life event shared with a live participant stays, as today. Life events tied to a group by `group_id` stay too: the spec does not list them. Reminders are still deactivated with `RecordDeleted`, so `ReactivateReminders` keeps giving back their deadlines (constitution VIII). An entity takes nothing with it, but its reminders are deactivated the same way and reactivated on restore.

A dependent comes back with its owner even when its other end is still in the trash. It stays hidden on screens until that end returns, which the queries already do with `whereHas`. Leaving it in the trash would keep `deleted_with` pointing at a live owner, and the listing and the purge, which treat a set `deleted_with` as "waits for its owner", would never reach it again.

## R3. One listing for three surfaces

**Decision**: `App\Helpers\VaultTrash` builds one `UNION ALL` of the nine tables (kind, id, uuid, deleted_at, owner), scoped to the vault, paginated by the database, then loads the page's records per kind. The view model, the API controller and the MCP tool (through the API) read it, as `ContactListCatalogue` is shared today. `group_members` and `group_files` have no `vault_id` and join `groups`.
**Rejected**: nine queries merged in memory (pages break once a vault holds thousands of trashed rows after a bulk delete).

## R4. The "cannot be restored" rule, stated once

**Decision**: `App\Helpers\TrashItem::obstacle()` returns the reason a trashed record cannot come back (owner in the trash, other end in the trash, entity type deleted) or null. The row reads it to mark Restore unavailable; `RestoreTrashItem` reads it to refuse.
**Rejected**: a policy. `policies.md` wants one when a rule is written twice, which this avoids, but a trash item is not a model, and nine model policies would repeat the same rule nine times.

## R5. Actions

`RestoreTrashItem` resolves the kind and calls one restore action per kind: the existing `RestoreGroup` and `RestoreLifeEvent`, and new `RestoreContact`, `RestoreEntity`, `RestoreRelationship`, `RestoreReminder`, `RestoreGroupMembership`, `RestoreGroupNote`, `RestoreGroupFile`. An owner's restore brings back its dependents. `DestroyTrashItem` force deletes one record and its dependents; `EmptyVaultTrash` loops it. `DestroyEntity` is new. Counters follow: `vault.contact_count`, `structure_version.record_count` and `entity_types.entity_count` go up on restore as they went down on deletion.

## R6. Files

`group_files` gains `deleted_at`. The file keeps its reference while the attachment is in the trash, so it is never orphaned and storage still counts it. Deleting for good runs `DetachFile`, and `PurgeOrphanedFiles` removes the content after its grace period. No change to `AddGroupFile`: an upload always creates a new file, so the `(group_id, file_id)` unique index cannot meet a trashed attachment.

## R7. Merge leftovers

The absorbed contact is excluded through `contact_merges.merged_contact_id` where `undone_at` is null. `MergeContacts` sets the owner of the relationships and memberships it drops to the `ContactMerge`, and a migration backfills that owner from the ledgers of merges not undone. The listing and the purge skip anything owned by a `ContactMerge`.

## R8. Retention and the purge

`config('monica.trash_retention_in_days')`, 30, read by the screen and by the daily job `PurgeVaultTrash` (low queue, 03:10, before `PurgeOrphanedFiles`). Rows set aside before this release get `deleted_at` moved to the migration's time, so they wait 30 days from now: their old dates are shown nowhere today. Records with `deleted_with_vault` are skipped by both. A kind behind a flag that is off for the account is skipped by the purge as well, since `flags.md` keeps the data of a withdrawn capability. The purge reports an item that fails and goes on, and skips one already gone with an owner purged before it. Deleting a person for good also erases a trashed life event left with no person in it.

## R9. Flags

The `groups` flag removes groups, memberships, notes and group files from the listing, the filters, the API and MCP when off for the account; `file-upload` removes group files.

## R10. Surfaces

Web: the settings controller moves to the General tab, and the Trash tab is its own route. API: one listing, one restoration, one deletion and one emptying endpoint under `vaults/{vault}/trash`, with the kind in the path, plus entity deletion. MCP: `ListTrash`, `RestoreTrashItem`, `DeleteTrashItem`, `EmptyTrash`, `DeleteEntity`. Contracts in [contracts/api.md](contracts/api.md).
