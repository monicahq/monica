# Research: Pause the duplicate search while an import writes into a vault

## R1. Which vaults an import writes into

**Decision**: `AccountImport::vaultIds()` returns `vault_id` and every id in `created_vault_ids`, without duplicates and without null. A file of one vault sets `vault_id`, and also lists it in `created_vault_ids` when the vault is new (`StartAccountImport.php:186-194`). A file of several lists each vault as it is written (`WritesMonicaItems.php:89`).
**Rationale**: four places need the same list (R2, R4), and the two columns mean different things to the rollback.

## R2. Is a vault being imported into

**Decision**: `Vault::isBeingImportedInto()` reads the imports of the vault's account whose status is `Writing` or `RollingBack`, and answers whether one of them names the vault in `vaultIds()`. An account runs one import at a time (`AccountImport` docblock), so the read finds at most one row, through the existing `(account_id, status)` index.
**Rationale**: FR-001. The check is done in PHP rather than in SQL, so `created_vault_ids` needs no JSON query, which would compile differently on each database the suite runs on.
**Rejected**: `whereJsonContains` on `created_vault_ids`; a flag column on `vaults`, which a worker that died would leave set; pausing every vault of the account, which breaks User Story 1 scenario 3.

## R3. Where the search is withheld

**Decision**: in two places.

| Place | Covers |
| --- | --- |
| `lookForDuplicates()` in `CreateContact`, `UpdateContactName`, `UpdateContactNameRepresentations` returns early | Every contact the import writes, and every rename during the import, without putting a job on the queue at all |
| `DetectDuplicateContacts::handle()` returns early for a subject whose vault is being imported into | The nightly pass, which queues one job per vault (FR-003), and a job queued before the import started |

**Rationale**: a check in `handle()` alone is not enough. The import's batches run on `default` and the search on `low`, so most of the 2,000 per contact jobs would wait behind the batches and reach a worker after the import completed and search anyway, which is what SC-001 forbids. A check at dispatch alone misses the nightly pass.
**Rejected**: overriding the job's static `dispatch()`, which would also stop the end of import search (R4); reading the `log_source` request attribute the import sets, which misses a rename by a person during the import.

## R4. The search at the end

**Decision**: each of the three places where an import ends queues `DetectDuplicateContacts` on `low` for each vault of `Vault::query()->whereKey($import->vaultIds())->get()`, after the import's new status is saved.

| Ending | Place |
| --- | --- |
| Completed | `WriteAccountImportBatch::complete()`, after the status update |
| Rolled back | `RollBackAccountImport::execute()`, after the transaction |
| Rollback failed | `RollBackAccountImportBatch::failed()`, after the status update |

**Rationale**: FR-004. Once the status has left `Writing` and `RollingBack`, the guard in R3 lets the job through. A vault the rollback destroyed is no longer found by the query, and `vault_id` is nulled on delete, so no search is queued for it. The three loops are three lines each, and the project prefers repeating them inline to a helper.

## R5. Searches for a record that no longer exists

**Decision**: `public bool $deleteWhenMissingModels = true;` on `DetectDuplicateContacts`, as on `ReadAccountImport`.
**Rationale**: FR-006. The job serializes its subject, so a contact the rollback removed throws `ModelNotFoundException` when the job is unserialized, and the worker records it as failed. With the property set, the worker deletes the job instead. The suite runs the `sync` queue (`phpunit.xml:41`), which serializes and unserializes every job, so a test dispatches the job after deleting its contact and proves it ends without an error.

## R6. The button during an import

**Decision**: `ContactDuplicateSearchController::create()` checks `$vault->isBeingImportedInto()` first. When true, it redirects to the same screen with its own `status` and `status_description` and queues nothing.
**Rationale**: FR-005. The branch only chooses a response, so it stays in the controller, and the job's guard would refuse the work anyway. The copy goes through `__()` and `monica:localize`.

## R7. The existing test that changes

`WriteAccountImportItemTest.php:448` asserts that one `DetectDuplicateContacts` job is pushed per imported contact. That assertion describes the behaviour this feature removes, so it becomes an assertion that none is pushed while the import writes.
