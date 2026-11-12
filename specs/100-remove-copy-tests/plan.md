# Implementation Plan: Remove Copy Tests

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/100-remove-copy-tests/spec.md`

## Summary

Delete every assertion in `tests/` whose expected value is application copy, and keep every
assertion about behaviour. A rewrite script kept out of the repository applies four mechanical
rules to the 1,929 assertions the audit matched: delete a standalone assertion, delete one link of
an assertion chain, weaken a refusal assertion to the key or the fact of the error, and delete a
method left with nothing to assert. Lists mixing copy with data, and the 885 methods matching a
single word, are read by hand. Then dead files and fixtures go, and the suite, PHPStan and Pint run.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: PHPUnit, `laravel/mcp` testing response (`assertHasErrors`)

**Storage**: N/A

**Testing**: PHPUnit through `php artisan test --compact`, PHPStan, Pint

**Target Platform**: the test suite only. No application code changes.

**Project Type**: web application (Laravel monolith)

**Performance Goals**: suite run time does not increase (SC-003)

**Constraints**: no change to `app/`, `resources/`, `lang/`, `.claude/rules/` or the constitution
(FR-008). The rewrite script is a throwaway in the scratchpad, never committed (Boost guideline:
no verification scripts in the repository).

**Scale/Scope**: 8,189 test methods in 1,120 files. 1,405 methods and 1,929 assertions matched with
a multi-word string, 885 further methods with a single word.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status |
| --- | --- |
| III. Tested Before Merged | Pass. Behavioural assertions stay (FR-002, SC-004), refusal tests keep proving the refusal (research R3). Every class keeps a test: a file is deleted only when all its methods asserted copy, and `PolicyCoverageTest` guards the policies (FR-007). |
| IV. Laravel Conventions | Pass. No new structure. |
| Done conditions (localize, PHPStan, Pint) | Pass. No string changes, so `monica:localize --check` is unaffected; PHPStan and Pint run at the end. |
| Governance (rules consistent with principles) | Accepted tension. `.claude/rules/testing.md` still asks a screen test and a notification test to assert copy, and the user chose to leave it (Clarifications, 2026-10-27). Recorded in Complexity Tracking. |

Post-design re-check: unchanged, pass.

## Project Structure

### Documentation (this feature)

```text
specs/100-remove-copy-tests/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── tasks.md             # /speckit-tasks
```

No `contracts/`: the change exposes no interface.

### Source Code (repository root)

```text
tests/
├── Feature/             # Controllers, Views, Auth, Mcp, Dav, Localization: most chain removals
├── Unit/                # ViewModels, Actions, Mcp, Notifications, Helpers: most statement removals
└── Fixtures/            # deleted only when no test uses a class any more
```

**Structure Decision**: edits stay inside `tests/`. The scratchpad holds the matcher and the rewrite
script.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Rules keep asking for copy assertions | The user decided the rules stay | Amending them was offered and declined |
