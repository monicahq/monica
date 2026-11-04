# Implementation Plan: Pause the duplicate search while an import writes into a vault

**Branch**: `claude/pause-detect-duplicates-imports-491569` | **Date**: 2026-11-02 | **Spec**: [spec.md](spec.md)

## Summary

`Vault::isBeingImportedInto()` answers whether an import of the account is writing into that vault or
rolling back what it wrote ([R1, R2](research.md)). The three actions that queue a search around a contact
skip it while that holds, and `DetectDuplicateContacts` returns early for such a vault, which also covers
the nightly pass ([R3](research.md)). Each of the three places where an import ends queues one search per
vault it wrote into that still exists ([R4](research.md)). The job deletes itself when its record is
gone ([R5](research.md)). The button answers with its own message during an import ([R6](research.md)).
No migration, no dependency, no new route.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13.35 |
| Storage | `account_imports` and `contact_duplicate_candidates`, both unchanged; no migration |
| Queue | `low` for every duplicate search, as today |
| Front end | One flash message on the duplicates screen; no markup, no JavaScript |
| Testing | PHPUnit with `Queue::fake()`; scenarios in [quickstart.md](quickstart.md) |
| Scale | One indexed read per contact written or renamed, against hundreds of comparisons saved per contact |

No open question remains. `data-model.md` is omitted: no table, column or state is added, and the two
entities are in the spec. `contracts/` is omitted: the duplicate search has no API or MCP surface.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | The withholding sits in the three actions that queue the search; the controller only chooses its response (R6) |
| II. Simplicity | Two model methods, one property, five early returns, three short loops, no helper |
| III. Tested | Model, actions, jobs and controller, each in the layer that owns the rule, per `.claude/rules/testing.md` |
| IV. Laravel | `deleteWhenMissingModels` as the framework documents it; queued jobs as today |
| V. Localized, safe | Two new phrases through `__()` and `monica:localize`, in every locale |
| VI. Nothing underfoot | Nothing a person wrote changes. A dismissed pair stays dismissed (spec Assumptions) |
| VIII. Nothing disappears silently | No candidate is removed by the pause. The end of import search removes stale pairs exactly as every search does |
| IX. Accessible | The message uses the existing flash, which is already announced |
| X. API | No endpoint starts a search today, and none is added |
| XI. Short record | spec 106, plan, research and quickstart under their caps; two artifacts omitted, said above |

Re-checked after Phase 1: unchanged. No rule is set aside.

## Project Structure

```text
specs/105-pause-duplicate-detection-imports/
├── spec.md, plan.md, research.md, quickstart.md
└── checklists/requirements.md

app/Models/AccountImport.php                                          # vaultIds() (R1)
app/Models/Vault.php                                                  # isBeingImportedInto() (R2)
app/Actions/CreateContact.php                                         # lookForDuplicates() returns early (R3)
app/Actions/UpdateContactName.php                                     # same
app/Actions/UpdateContactNameRepresentations.php                      # same
app/Jobs/DetectDuplicateContacts.php                                  # handle() returns early (R3), deleteWhenMissingModels (R5)
app/Jobs/WriteAccountImportBatch.php                                  # complete() queues the vault search (R4)
app/Actions/RollBackAccountImport.php                                 # execute() queues it after the rollback (R4)
app/Jobs/RollBackAccountImportBatch.php                               # failed() queues it (R4)
app/Http/Controllers/App/Vaults/ContactDuplicateSearchController.php  # the message during an import (R6)
docs/portal/en/7-keeping-records-tidy/2-review-possible-duplicates.md # the search waits for an import
docs/FEATURES.md
lang/*.json                                                           # through monica:localize

tests/Unit/Models/AccountImportTest.php                               # vaultIds()
tests/Unit/Models/VaultTest.php                                       # isBeingImportedInto(), per status and per vault
tests/Unit/Actions/CreateContactTest.php                              # no search while imported into
tests/Unit/Actions/UpdateContactNameTest.php                          # same
tests/Unit/Actions/UpdateContactNameRepresentationsTest.php           # same
tests/Unit/Jobs/DetectDuplicateContactsTest.php                       # writes nothing for a vault imported into; ends quietly for a deleted contact (R5)
tests/Unit/Jobs/WriteAccountImportBatchTest.php                       # one vault search on completion
tests/Unit/Actions/RollBackAccountImportTest.php                      # existing vault searched, created vault not
tests/Unit/Jobs/RollBackAccountImportBatchTest.php                    # failed rollback searches the vaults left
tests/Unit/Actions/WriteAccountImportItemTest.php                     # line 448 inverted (R7)
tests/Feature/Controllers/App/Vaults/ContactDuplicateSearchControllerTest.php  # the message, nothing queued
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Complexity Tracking

None.
