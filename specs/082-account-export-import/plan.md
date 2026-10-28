# Implementation Plan: Account export and import

**Branch**: `2026-10-18-account-export-import` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

## Summary

Three models, [AccountExport](../../spec/models/058-account-export-done.md), [AccountImport](../../spec/models/059-account-import-done.md) and [AccountImportItem](../../spec/models/060-account-import-item-done.md), and a chain of short queued jobs. An import reads its file once into item rows, waits for the person to choose the vault and Me (an Monica file of several vaults writes each into a new vault, with its own Me), writes the items 50 at a time through the actions the screens already use, and on failure deletes what its items say it created. An export streams one JSON file to the configured disk. It was built behind the `account-transfer` flag, removed once all three stories landed. Decisions in [research.md](research.md), shapes in [contracts/](contracts/).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.4+, current Laravel, Sanctum, Pennant |
| New dependency | `halaxa/json-machine`, approved 2026-10-18 (R3) |
| Storage | Three new tables; files on the configured disk under `accounts/{id}/transfers` |
| Testing | PHPUnit on SQLite, MySQL and PostgreSQL; fixtures of a few contacts per format in `tests/Fixtures/Imports` |
| Scale | The example: 1,767 contacts, 27 MB, under 10 minutes (SC-001); web uploads to 512 MB |
| Unknowns | None left: R1 to R8 |

## Constitution Check

| Principle | How it is met |
| --- | --- |
| I. Actions | One action per operation below; jobs, controllers, the API and the console command call them |
| II. Simplicity | No importer class hierarchy: one reader per format filling the same item rows, one writer per kind |
| III. Tests | Listed below; fixtures are small files, the example file is never committed |
| IV. Laravel | Queued jobs, notifications, policies, one route per line, inline validation |
| V. Localized | Screens, emails, refusal reasons, report reasons and the notes the import writes go through `__()`; delete and cancel warn first |
| VI. Nothing changes | An import only adds (answer 2); Me keeps its values (answer 4); records keep their structure version (R5) |
| VII. Core and fields | Values land in template fields by key; nothing becomes a column |
| VIII. Nothing disappears | Every item has an outcome and a reason; data with no home becomes a note (answer 1) |
| IX. Accessible | Two settings screens: progress announced in a live region, every choice a native control |
| X. API | Every operation has an endpoint (contracts/api.md) and a docs page |
| XI. Short | Spec 120, plan, research, three contracts and quickstart under their caps; no data-model.md, the models are in `spec/models` |

Gate: pass, before and after design.

## Project Structure

```text
app/Models/{AccountExport,AccountImport,AccountImportItem}.php  + factories, migrations, spec/models 058 to 060
app/Enums/{AccountExportStatusEnum,AccountImportStatusEnum,AccountImportSourceEnum,AccountImportOutcomeEnum}.php
app/Enums/{UserActionEnum,EmailType}.php                         + export asked, downloaded, import started, ended
app/Features/AccountTransfer.php                                 flag, ships off; tests/TestCase.php turns it on
app/Actions/CreateAccountExport.php                              checks one at a time, dispatches the build
app/Actions/WriteAccountExport.php                               streams the file (contracts/export-format.md)
app/Actions/CreateAccountImport.php                              stores the upload, dispatches the reading
app/Actions/ReadMonicaFourFile.php, ReadMonicaFile.php            fill item rows and the summary
app/Actions/StartAccountImport.php                               vault, Me, caps (R7), dispatches the first batch
app/Actions/WriteAccountImportItem.php                           one item through the existing actions (R6)
app/Actions/RollBackAccountImport.php, CancelAccountImport.php   R2
app/Actions/{CreateContact,CreateContactReminder}.php            unchanged; created_at set after (R6)
app/Jobs/{BuildAccountExport,ReadAccountImport,WriteAccountImportBatch,RollBackAccountImportBatch}.php
app/Jobs/PurgeExpiredAccountExports.php                          daily in routes/console.php
app/Notifications/{AccountExportReady,AccountImportEnded}.php    HasEmailType, queue high
app/Policies/{AccountExportPolicy,AccountImportPolicy}.php       create only: one at a time; ownership stays with scoping (policies.md)
app/Http/Controllers/App/Settings/Account/Transfer/{Export,ExportDownload,Import,ImportContact,ImportReport}Controller.php
app/Http/Controllers/Api/{AccountExport,AccountExportDownload,AccountImport,AccountImportContact,AccountImportReport}Controller.php
app/Http/Resources/{AccountExport,AccountImport}Resource.php
app/ViewModels/Settings/{AccountExports,AccountImport}ViewModel.php
app/Console/Commands/ImportAccountCommand.php                    monica:import-account
resources/views/app/settings/account/transfer/{exports,import,import-report}.blade.php
resources/views/components/settings/sidebar.blade.php            + Export and import, behind the flag
resources/docs/api/NN-account-transfer.php                       every endpoint
routes/web.php, routes/api.php, routes/console.php
```

**Structure decision**: existing folders only. Settings controllers in a `Transfer` folder beside `Templates` and `Logs`.

## What each test proves

- **Read actions**: each fixture yields the right items and summary; reverse relationships skipped; refusals for an unknown format, a newer version, invalid JSON; the two factor secret never reaches a row.
- **WriteAccountImportItem**: one test per row of the mapping contract that carries a rule (unknown year, age based, past one time reminder, note fallback, Me filled not overwritten, pet with caregiver, created_at kept).
- **Start, roll back, cancel**: caps refused with the numbers; a vault of another account not found; rollback leaves the counts, storage total and Me as before; a retried batch writes nothing twice.
- **Export**: a vault round trip through `WriteAccountExport` and `ReadMonicaFile` gives the same records and values (SC-002); credentials absent; expiry deletes the file.
- **Controllers and API**: answers, inline validation, one action refusal each, wrong vault 404, download of another account 404.

## Order of work

1. Flag, models, enums, policies, the reading actions and the import screen up to the summary.
2. Writing, rollback, cancel, report, notification, console command (user story 1).
3. Export, download, expiry (user story 2). 4. Monica reading and structures (user story 3). 5. API and docs, `monica:localize`, PHPStan, Pint, whole suite; the flag dies in the pull request that ships story 3.

## Risks

- **Hosted upload size**: the Docker image allows 512 MB (`Dockerfile:106`); the hosted proxy and a self hoster's web server may not. The screen states the instance's limit.
- **`MergeContacts` throws on an undecided conflict**, so the Me merge passes `kept` for every one; its fingerprint is not used.

## Complexity Tracking

None.
