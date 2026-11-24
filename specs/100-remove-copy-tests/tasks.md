---
description: "Task list for removing copy assertions from the test suite"
---

# Tasks: Remove Copy Tests

**Input**: `specs/100-remove-copy-tests/` (plan.md, spec.md, research.md, data-model.md, quickstart.md)

**Tests**: none added. The feature edits the suite itself; the suite passing is the proof.

`SP` below is the scratchpad,
`/private/tmp/claude-501/-Users-regis-code-monica/95b891c9-8d2f-4845-b815-d752fd57c8b1/scratchpad`.
Nothing under `SP` is committed.

## Phase 1: Setup

- [X] T001 Record the baseline: run `php artisan test --compact` and note the pass count and run time in SP/baseline.txt
- [X] T002 Record per-file counts of non-copy assertions from SP/match_strong.py output into SP/baseline_counts.json (R6)

## Phase 2: Foundational

- [X] T003 Write SP/strip_copy_assertions.py applying research R2 (delete a standalone copy assertion statement, delete a copy link of a chain), R3 (weaken `assertSessionHas`, `assertSessionHasErrors`, `assertJsonValidationErrors`, MCP `assertHasErrors`; delete `expectExceptionMessage`, `assertJsonPath('message', ...)`) and R4 (delete a method left with no assertion and no `expectException`) to `tests/**/*Test.php`, using the catalogue matcher of SP/match_strong.py. It writes no change to an assertion it cannot classify; it lists it in SP/manual.json instead (lists mixing copy and data, structures, helpers taking a sentence)
- [X] T004 Dry run T003 on three files (`tests/Feature/Controllers/Settings/EntityTypeControllerTest.php`, `tests/Unit/Mcp/Tools/DeleteRelationshipCategoryTest.php`, `tests/Unit/ViewModels/Vaults/ShareLinkViewModelTest.php` or the nearest existing equivalents), read the diff, fix the script

## Phase 3: User Story 1 - Change a wording without touching a test (P1)

**Goal**: no assertion of a multi-word catalogue string remains.

**Independent Test**: quickstart.md section 1 and 2.

- [X] T005 [US1] Run SP/strip_copy_assertions.py over `tests/Unit`
- [X] T006 [US1] Run SP/strip_copy_assertions.py over `tests/Feature`
- [X] T007 [US1] Run the affected tests of T005 and T006, fix any syntax or runtime breakage the rewrite caused
- [X] T008 [P] [US1] Hand-rewrite the entries of SP/manual.json under `tests/Unit` per research R5
- [X] T009 [P] [US1] Hand-rewrite the entries of SP/manual.json under `tests/Feature` per research R5
- [X] T010 [US1] Search data providers and test constants in `tests/` for catalogue strings that reach an assertion, and remove them per R2 to R4
- [X] T011 [US1] Review the 885 single-word methods listed by SP/methods_catalogue.json, split by folder across subagents: remove the word assertion where the application wrote the word, keep it where the test created it (R1). Review each subagent diff before accepting it
- [X] T012 [US1] Remove wording assertions from the translation machinery tests in `tests/Unit/Translation`, `tests/Feature/Localization` and `tests/Feature/Translation`, keeping what is provable without a wording (FR-005)

## Phase 4: User Story 2 - Keep the behaviour the removed tests also proved (P1)

**Goal**: every behavioural assertion survives.

**Independent Test**: quickstart.md section 3.

- [X] T013 [US2] Compare per-file non-copy assertion counts with SP/baseline_counts.json and review every unexplained drop
- [X] T014 [US2] Delete local variables that only fed a deleted assertion and have no side effect (R4), in the files changed by T005 to T012
- [X] T015 [US2] Delete data providers, test files and `tests/Fixtures` classes left with no user (FR-006)
- [X] T016 [US2] Run `tests/Unit/Policies/PolicyCoverageTest.php` and `tests/Feature/RouteProtectionTest.php`, restore any ability or route test the removal took (FR-007)

## Phase 5: Polish

- [X] T017 Rerun the audit matcher: zero multi-word catalogue matches, and `grep -rnE 'assert\w*\([^;]*\b(__|trans_choice)\(' tests` prints nothing (SC-001)
- [X] T018 Run the whole suite with `php artisan test --compact` and compare run time with SP/baseline.txt (SC-003)
- [X] T019 Reword five strings locally (flash, view model label, refusal, notification subject, heading), run the suite, revert (SC-002)
- [X] T020 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check`
- [X] T021 Commit on `2026-10-25-location-management` as `test(tests): remove copy assertions`, with the spec folder

## Dependencies

- T001, T002 before T003. T003, T004 before T005 to T012.
- T005 and T006 before T007. T007 before T008 to T012.
- US2 (T013 to T016) after US1. Polish after both.

## Parallel Opportunities

- T008 and T009 touch different folders.
- T011 splits by folder across subagents.

## Implementation Strategy

US1 and US2 ship together: stripping copy (US1) without the checks of US2 risks losing behaviour, so
no commit happens between them. The script does the bulk in one pass, the hand-read cases follow,
then the checks.
