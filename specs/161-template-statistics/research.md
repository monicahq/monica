# Research: Statistics per kind of record, and changing the template of many records

Line references are to `main` on 2026-11-25.

## R1. One kind of record, two addresses

**Decision**: a value object `App\Helpers\RecordKind` stands for People or one `EntityType`: its names, its record model, its query, its offered versions and its list route. The web keeps the list prefixes (`contacts/statistics`, `entities/{entityType}/statistics`), so the trail leads back to the list. The API and the MCP server use one prefix, `vaults/{vault}/record-kinds/{recordKind}`, where `recordKind` is `contacts` or an entity type uuid.
**Rationale**: five capabilities times two kinds would be ten routes and ten tools for one question. An entity type always stores its plural, so the brief's "Records" fallback never applies.
**Rejected**: duplicating every API route per kind; making People an entity type.

## R2. Statistics are counted live, from two grouped queries

**Decision**: `RecordStatistics` runs one `GROUP BY structure_version_id, is_archived(, is_partial)` over the vault's records of the kind, then joins the versions and templates in PHP. A migration adds `(vault_id, structure_version_id, is_archived)` to `contacts` and `(vault_id, entity_type_id, structure_version_id, is_archived)` to `entities`. Nothing is cached, so the page is right the moment a batch ends (SC-005).
**Rationale**: `structure_versions.record_count` is account wide, not per vault. A covering index makes the group read 10,000 index entries, well under SC-001.
**Rejected**: per-vault counters (a second truth to keep in step with every create, archive, move and delete).

## R3. Field completion is one aggregate over field values

**Decision**: `FieldCompletion` counts `COUNT(DISTINCT valuable_id)` per `field_id` from `field_values` (and `field_value_rows` for a repeater), joined to the records on the chosen versions, `is_detached = false`, restricted to the fields of those versions so the `(vault_id, field_id, …)` indexes serve it. The denominator per field is the record count of the versions carrying it, already known from R2. Partial contacts are left out.
**Rejected**: loading values per record.

## R4. The bulk preview groups records by what they hold

**Decision**: `BulkTemplateChangePreview` reads, once, the distinct `(valuable_id, field_id, is_detached)` of the batch, groups records by that signature, and asks `FieldCorrespondence::counterparts()` once per distinct signature and once per target. Reminders come from one query joined to the batch's values. It returns the six counts, totals per field (`label`, `section`, `records`) and per reminder rule name, the blocked records, and `hiddenCount`.
**Rationale**: FR-019 and FR-022 of spec 159 prepared exactly this; records leaving one version hold a few dozen signatures at most, so the rule runs dozens of times, not 10,000. The single-record preview stays as it is, and a test proves both agree on the same record (SC-003).
**Rejected**: running `TemplateChangePreview` per record (about 50,000 queries).

## R5. A batch is a record of its own, written in slices

**Decision**: `TemplateChange` (source template, versions, target version, counts, `ended_at`, user; no status column) and `TemplateChangeItem` (record morph, `from_version_id`, `outcome`, `reason`), modelled on `AccountImport` and `AccountImportItem`. Confirming (`CreateTemplateChange`) inserts the items in chunks of 1,000 and dispatches one `ChangeTemplateOfRecords` per range of 200 item ids; the ranges are disjoint, so the slices run side by side. A skip or failure reason is a `TemplateChangeReasonEnum` code, turned into a sentence where it is shown. An item with an outcome is never moved again, so a retried job and a double submit move each record once. The slice's timeout stays under the queue's `retry_after`, so two workers never hold one slice; a slice cut short resumes, a record found already on the destination counts as moved, and `failed()` marks what is left as failed so the change always ends.
**Rationale**: the import already proves the shape (lock, slices, resumable, report). A per-record row is the result screen's list and the "View affected" filter: both lists take a `change` query parameter keeping the records whose item in that change is `moved`.
**Rejected**: Laravel batches (no `job_batches` table, and their progress is not the per-record report the result needs); one job per record.

## R6. Each record still moves through `MoveRecordToStructureVersion`

**Decision**: the job calls it with `writesToLog: false` (prepared by spec 159 R7) and a new optional `FieldCorrespondence` shared by the slice. It skips, with a reason, a record no longer on its item's `from_version_id`, archived or deleted since. Search indexing is suspended with `withoutSyncingToSearch()` during the slice and done once per slice with `searchable()` on the moved ids. A `Throwable` on one record marks it failed and the slice goes on.
**Rationale**: principle I and FR-014. One place moves a record; the snapshot, set aside and reminders stay identical.

## R7. One log entry per batch

**Decision**: `UserActionEnum::TemplateChangeStarted`, written once by `CreateTemplateChange` when the batch is confirmed, with the target template and the record count through `trans_choice()`, the change as its subject.
**Rationale**: the job runs on the queue, where the request that says whether the web, the API or an assistant asked is gone; the action still has it.
**Rejected**: writing it when the last slice ends (every batch would read as coming from the web).

## R8. Which records a batch takes, and what blocks one

**Decision**: every non-archived record of the source template in the vault, on one chosen version or on every one, partial contacts included. A record held by another running `TemplateChange` is blocked ("Another change of template is moving this animal."). "Update to vN" is the same batch with the source template as target and the older versions selected.

## R9. Screens

**Decision**: statistics and the three steps are full pages in `x-shell`. Field completion is a form whose `template`, `version` and `order` reload its section with Alpine Ajax (`x-target`), and work without JavaScript. Step 3 reloads itself every two seconds while the batch runs (`x-init` polling of its own block), then stops. New components: `x-steps` (step indicator, `aria-current="step"`) and `x-meter` (bar with `role="meter"` and its values), each a contract used in two files or more. Reused: `x-page-header`, `x-page-section`, `x-actions-menu`, `x-chip`, `x-badge`, `x-notice`, `x-empty-state`, `x-help`.
**List filters**: `template`, `version` and `change` query parameters on both lists, as `categories` already is on the contact list, shown as removable chips with "Clear filters". The entity list gains the template and version of each record.

## R10. Sensitive fields

As spec 159 R10: the web names every field; the API and MCP count a sensitive field in `hidden_count` unless the key holds the `sensitive-fields` scope. Field completion follows the same rule.

## R11. Documentation

Help centre page "See statistics and change the template of many records" (`docs/portal/en/9-templates-and-customisation/`), linked from an `x-help` on step 1; the API reference gains a "Record kinds" section; the MCP family "Templates" gains five tools; one What's new entry.
