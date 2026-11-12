# Tasks: Possession Locations

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the provenance files of 116 (edit screen included) for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.19.0 (research R13): principle VII lists Possession location stay (twenty five), the possession sentence says it holds its stays and the open one is where it sits, one sentence on the stay; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and create `spec/models/076-possession-location-stay-in-progress.md` from data-model.md, in the shape of 075

## Phase 2: Foundational

- [X] T003 [P] Add `PossessionMove`, `PossessionLocationStayUpdate`, `PossessionLocationStayDeletion` to `app/Enums/UserActionEnum.php` (research R12), copy in the voice of the provenance cases
- [X] T004 Create `database/migrations/2026_11_12_000003_create_possession_location_stays_table.php` per data-model.md, with the backfill of research R5 in the same `up()`
- [X] T005 Create `app/Models/PossessionLocationStay.php` (full docblock, `#[RouteKey('uuid')]`, `ResolvesModelInVault`, date casts, `possession()`, `location()`) and `database/factories/PossessionLocationStayFactory.php` (an `open()` and a `closed()` state); add `locationStays()` ordered per research R6 and `openLocationStay()` to `app/Models/Possession.php`, `stays()` to `app/Models/Location.php`; order test in `tests/Unit/Models/PossessionTest.php`
- [X] T006 Extend `app/Policies/LocationPolicy.php::delete()` per research R7 (children refusal first, then stays); cases in `tests/Unit/Policies/LocationPolicyTest.php`; the settings, API and MCP refusals need no change

## Phase 3: User Story 1, move a possession elsewhere (P1) MVP

Independent test: move a possession with a current location and one without, and find the stays and the current location right.

- [X] T007 [US1] Create `app/Actions/MovePossession.php` per research R3 (account check on possession and location, row lock, refusals as `ValidationException` on `location_id` and `moved_on`, close and open in one transaction, `current_location_id`, journal) and `tests/Unit/Actions/MovePossessionTest.php`
- [X] T008 [US1] Create `app/ViewModels/Vaults/PossessionLocationsViewModel.php` delegating header and sections to `PossessionViewModel`, with the location options as full paths (`LocationTree::path()`) and the open stay the Alpine preview needs (research R9)
- [X] T009 [US1] Routes in `routes/web.php` after the provenance routes: `possessions.locationStays.index` and `possessions.locationStays.create` at `vaults/{vault}/possessions/{possession}/locations`
- [X] T010 [US1] Create `app/Http/Controllers/App/Vaults/PossessionLocationStayController.php` with `index` and `create` (inline validation, location looked up by uuid inside the account, delegating to `MovePossession`)
- [X] T011 [US1] Create `resources/views/app/vaults/possessions/locations.blade.php` and `_location-stay-form.blade.php` from the design: "Manage locations", "Move elsewhere" hidden while the form is open, the preview line computed in Alpine with its three wordings
- [X] T012 [US1] In `PossessionViewModel::sections()` give Locations its URL and count; update `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T013 [US1] `tests/Feature/Controllers/App/Vaults/PossessionLocationStayControllerTest.php`: index renders, create moves and flashes, one refusal reaches the form, wrong possession 404
- [X] T014 [P] [US1] API `create` in `app/Http/Controllers/Api/Possessions/PossessionLocationStayController.php`, `app/Http/Resources/PossessionLocationStayResource.php` per contracts/api.md, routes in `routes/api.php`; tests
- [X] T015 [P] [US1] MCP `app/Mcp/Tools/MovePossession.php`, registered on the server; test

## Phase 4: User Story 2, read where it is and where it has been (P1)

Independent test: with five stays, one open, one unknown arrival and a gap, the current block, list, lengths and lines read right.

- [X] T016 [US2] View model: current block (path split into ancestors and leaf, nearest address via `LocationTree::addressHolder()`, since, time there, reason), newest first, periods in the user's date format, lengths and the count line through `trans_choice()` (research R8), gap lines, empty state, delete wording for open and past stays
- [X] T017 [US2] Render the current block, the empty state, the help mark (`x-help` pointing at the portal page), "Every stay" with "Here now", gap lines and the delete warning (`x-delete-action` if it fits, per `.claude/rules/components.md`) in `locations.blade.php`
- [X] T018 [US2] `tests/Unit/ViewModels/Vaults/PossessionLocationsViewModelTest.php` per quickstart
- [X] T019 [P] [US2] API `index` and `show`; MCP `ListPossessionLocationStays` and `GetPossessionLocationStay`; tests

## Phase 5: User Story 3, correct or delete a stay (P2)

Independent test: correct a past stay and the open one, delete each after its warning, and read the section and the possessions list.

- [X] T020 [US3] `app/Actions/UpdatePossessionLocationStay.php` per research R3 (refusals on `left_on`, `current_location_id` when the open stay's location changes, journal) and its test
- [X] T021 [US3] `app/Actions/DestroyPossessionLocationStay.php` (clears `current_location_id` for the open stay, journal) and its test
- [X] T022 [US3] Routes `possessions.locationStays.edit`, `.update`, `.destroy` with parameter `possessionLocationStay`; controller `edit`, `update`, `destroy`; the correction screen renders the filled form ("Left" only for a past stay); tests
- [X] T023 [P] [US3] API `update` and `destroy`; MCP `UpdatePossessionLocationStay` and `DeletePossessionLocationStay`; tests

## Phase 6: User Story 4, the location chosen on the possession form (P2)

Independent test: add a possession with a location, change it, clear it, save it unchanged, and read the stays after each.

- [X] T024 [US4] `app/Actions/CreatePossession.php` writes the open stay arriving today (research R4); case in `tests/Unit/Actions/CreatePossessionTest.php`
- [X] T025 [US4] `app/Actions/UpdatePossession.php` calls `MovePossession` on a change and closes the open stay when cleared (research R4); cases in `tests/Unit/Actions/UpdatePossessionTest.php`

## Phase 7: Polish

- [X] T026 API reference `resources/docs/api/38-possession-location-stays.php`, and the 422 on location deletion in the locations reference, via the `api-docs-writer` skill
- [X] T027 [P] Guide `docs/portal/en/9-templates-and-customisation/22-possession-locations.md`, a line in `16-possessions.md` and in the locations guide, via the `writer` skill; `docs/FEATURES.md`
- [X] T028 Example stays in the seeded account (a move or two) in `database/seeders/ExampleAccountSeeder.php`, asserted in `tests/Unit/Seeders/ExampleAccountSeederTest.php`
- [X] T029 `php artisan monica:localize`, translate every reported string in every locale (translation subagents on Sonnet), `--check` passes
- [X] T030 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T031 The quickstart's checks by hand at erica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen. US4 needs `MovePossession` (T007). Polish last.
Parallel: T002 beside T001; T003 beside T004; within each story the tasks marked [P] (API, MCP) beside the web work; US4 beside US2 and US3.

## Strategy

MVP is US1 plus US2: move and read. Then US3 and US4, which keep the forms and the stays in agreement.
