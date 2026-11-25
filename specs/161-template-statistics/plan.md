# Implementation Plan: Statistics per kind of record, and changing the template of many records

**Branch**: `2026-11-25-template-statistics` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

## Summary

A Statistics page per kind of record and vault (overview, templates and versions, field completion), and from it a three step bulk template change that previews in a few aggregate queries and moves records in parallel queued slices, each record still going through `MoveRecordToStructureVersion`. Decisions are in [research.md](research.md), tables in [data-model.md](data-model.md), endpoints and tools in [contracts/api.md](contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax, Laravel Scout, Laravel MCP

**Storage**: the application database; two new tables and two indexes

**Testing**: PHPUnit, `RefreshDatabase`

**Target Platform**: web, JSON API, MCP server

**Project Type**: web application

**Performance Goals**: statistics under 1 s and preview under 5 s at 10,000 records; a batch of 10,000 moved under 2 minutes (SC-001, SC-002)

**Constraints**: no per-record query in the statistics or the preview; one log entry per batch; nothing deleted

**Scale/Scope**: about 10 screens and partials, 5 API endpoints, 5 MCP tools, 1 job, 2 models

## Constitution Check

| Principle | How the plan meets it |
| --- | --- |
| I. Actions hold the logic | `CreateTemplateChange` validates and creates the batch; the job only loops over `MoveRecordToStructureVersion` |
| II. Simplicity | Statistics, completion and preview are computed, not stored; `AccountImport` is the template for the batch |
| III. Tested | Each action, helper and job tested in its layer; one test proves bulk and single previews agree |
| V. Localized | Every string through `__()` or `trans_choice()` for counts; all locales filled by the translations skill |
| VI. Nothing changes under anybody's feet | The preview is the explicit acceptance; each record is snapshotted and revertible; a record changed after the preview is skipped |
| VIII. Nothing disappears silently | Values are set aside, never deleted; skipped and failed records are listed with their reason |
| IX. Accessible | `x-steps` and `x-meter` carry their ARIA; the result is announced; 375 px without horizontal scroll |
| X. Through the API | Five endpoints and five MCP tools, answering through the controllers |
| XI. Short record | Within caps |

Gate passes before and after design. No violation to track.

## Project Structure

### Documentation (this feature)

```text
specs/161-template-statistics/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
└── contracts/api.md
```

### Source Code (repository root)

```text
app/Helpers/                     RecordKind, RecordStatistics, FieldCompletion, TemplateFilter, TemplateChangeBatch, BulkTemplateChangePreview
app/Actions/                     CreateTemplateChange; MoveRecordToStructureVersion gains an optional FieldCorrespondence
app/Jobs/                        ChangeTemplateOfRecords
app/Models/                      TemplateChange, TemplateChangeItem
app/Enums/                       TemplateChangeOutcomeEnum, TemplateChangeReasonEnum, UserActionEnum case
app/Http/Controllers/App/Vaults/ RecordStatisticsController (field completion reloads inside it), TemplateChangeController, TemplateChangePreviewController
app/Http/Controllers/Api/        RecordStatisticsController, FieldCompletionController, TemplateChangePreviewController, TemplateChangeController
app/Mcp/Tools/                   five tools of contracts/api.md
app/ViewModels/Vaults/           RecordStatisticsViewModel, TemplateChangeFormViewModel, TemplateChangePreviewViewModel, TemplateChangeViewModel; ContactsViewModel and EntitiesViewModel take the filters
resources/views/app/vaults/      statistics/, template-changes/ (select, preview, result); list headers gain Statistics
resources/views/components/      steps, meter
database/migrations/             template_changes, template_change_items, record indexes
routes/web.php, routes/api.php
docs/portal/en/, resources/docs/api/, resources/docs/api-changelog/, What's new
tests/Unit, tests/Feature         mirroring the above
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Delivery order

1. Indexes, `RecordKind`, `RecordStatistics`, `FieldCompletion`, statistics page and list filters (first delivery).
2. Tables, `BulkTemplateChangePreview`, `CreateTemplateChange`, the job, the three steps.
3. API, MCP, documentation, translations, What's new.

## Complexity Tracking

None.
