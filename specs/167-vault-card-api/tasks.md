# Tasks: Vault card values in the API

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)

**Tests**: requested by the spec (FR-012) and required by the constitution (principle III).

## Phase 1: Setup

- [X] T001 Read `.claude/skills/api-writer/SKILL.md`, `.claude/skills/api-docs-writer/SKILL.md`, `.claude/skills/changelog/SKILL.md` and `.claude/rules/testing.md` before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 Add `scopeAwaitingAnswerUntil()` and make `scopeAwaitingAnswerInVault()` call it, per research R3, in `app/Models/ReminderOccurrence.php`
- [X] T003 Add `faces()`, `scopeWithCardValues(User $user)` and `scopeOrderedByRecentVisit()` per research R1 to R4 in `app/Models/Vault.php`
- [X] T004 [P] Test `scopeAwaitingAnswerUntil()` (switched off rule, answered occurrence, day boundary) in `tests/Unit/Models/ReminderOccurrenceTest.php`
- [X] T005 [P] Test `faces()` (four newest, partial and archived left out), the count with a reader timezone ahead of UTC, and the recent order in `tests/Unit/Models/VaultTest.php`
- [X] T006 Add `show()` per research R6 in `app/Http/Controllers/Api/ContactAvatarController.php` and register `contacts.avatar.show` next to the avatar routes in `routes/api.php`
- [X] T007 Make `VaultsViewModel` read `orderedByRecentVisit()`, `withCardValues()`, `$vault->faces` and `upcoming_reminder_count` instead of its own queries in `app/ViewModels/Vaults/VaultsViewModel.php`
- [X] T008 Run `tests/Unit/ViewModels/Vaults/VaultsViewModelTest.php` and the vaults page feature tests; adjust only assertions that named the removed queries

## Phase 3: User Story 1 - The Android app draws the same vault card (P1)

**Independent test**: list and show with a token return the three values the web card shows.

- [X] T009 [US1] Add `last_visited_at`, `faces` and `upcoming_reminder_count` per [contracts/api.md](contracts/api.md) in `app/Http/Resources/VaultResource.php`
- [X] T010 [US1] Validate `sort`, apply `withCardValues()` in `index()` and `find()`, apply `orderedByRecentVisit()` for `sort=recent`, and re-read through `find()` after create and update, per research R5 and R7, in `app/Http/Controllers/Api/VaultController.php`
- [X] T011 [US1] Add the `$jsonStructure` attributes and tests for list and show: never opened, more than four contacts, partial contact left out, count of 2, other account absent and not found, `sort=recent` order, invalid `sort` 422, query count equal for 2 and 5 vaults (research R8), in `tests/Feature/Controllers/Api/VaultControllerTest.php`

## Phase 4: User Story 2 - The app shows an avatar with only a token (P1)

**Independent test**: `avatar_url` returns the thumbnail with the token and 404 otherwise.

- [X] T012 [US2] Test `show()`: thumbnail returned, no avatar 404, missing file 404, other vault 404, other account 404, `avatar_url` of a face resolving to it, in `tests/Feature/Controllers/Api/ContactAvatarControllerTest.php`

## Phase 5: User Story 3 - Assistants and developers read the same thing (P2)

**Independent test**: `list-vaults` matches the API; the reference and OpenAPI describe every addition.

- [X] T013 [US3] Add `sort` to the schema and confirm `answerThroughApi()` forwards it in `app/Mcp/Tools/ListVaults.php`
- [X] T014 [P] [US3] Document the three attributes in `$vaultAttributes`, the example responses and the `sort` parameter in `resources/docs/api/05-vaults.php`
- [X] T015 [P] [US3] Document the avatar address and correct the `avatar.url` description in `resources/docs/api/06-contacts.php`
- [X] T016 [P] [US3] Add the API changelog entry (three fields, `sort`, the endpoint, `breaking => false`) in `resources/docs/api-changelog/2026-11-27.php`
- [X] T017 [P] [US3] Add the product changelog entry, no screenshot, in `changelog/`
- [X] T018 [US3] Run the OpenAPI and docs portal tests and the MCP catalogue and walk tests; fix what they report, in `tests/`

## Phase 6: Polish

- [X] T019 Run `vendor/bin/pint --dirty --format agent` and PHPStan on the changed files
- [X] T020 Run `composer test:parallel`
- [X] T021 Run the `definition-of-done` skill, reporting item 11 (product guide, help bubbles) as skipped because no screen changes
- [X] T022 Commit with a conventional scoped message, push to origin and open the pull request per the `pull-requests` skill

## Dependencies

- T002 before T003; T003 before T005, T007, T009, T010; T006 before T009 (the face needs the route).
- US1 (T009 to T011) before US2's face assertion in T012 and before US3.
- T014 to T017 run in parallel; T018 after them. Phase 6 last.

## Implementation strategy

MVP is Phases 2 and 3: the API returns the card values. Phase 4 lets the app show photographs
instead of initials, and Phase 5 makes the change visible to assistants and developers.
