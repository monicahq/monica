# Tasks: Location Management

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/web-routes.md](contracts/web-routes.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md` (The Office data).

## Phase 1: Setup

- [X] T001 Amend principle VII to list Location as a fixed concept, with Sync Impact Report and MINOR version, in `.specify/memory/constitution.md` (research R1)
- [X] T002 [P] List Location among the fixed concepts in `spec/spec.md` and write `spec/models/058-location-in-progress.md` (name, parent, emoji, address, the cycle, vault and delete rules)
- [X] T003 [P] Add the `locations` flag in `app/Features/Locations.php` with `#[Name('locations')]`, and list it among the flags defined as on in `tests/TestCase.php`

## Phase 2: Foundational

- [X] T004 Create `database/migrations/2026_10_25_000000_create_locations_table.php`: `uuid` unique, `account_id` and `vault_id` with `foreignIdFor()->constrained()->cascadeOnDelete()`, `parent_id` with `foreignIdFor(Location::class, 'parent_id')->nullable()->constrained('locations')->cascadeOnDelete()`, `name` string(255), `emoji` string(32) nullable, `address` text nullable, timestamps, index `(vault_id, parent_id)`, a comment on every column, `up()` only
- [X] T005 Create `app/Models/Location.php` with `#[RouteKey('uuid')]`, `#[Fillable]`, `HasUuids`, `ResolvesModelInVault`, `HasFactory`, `account()`, `vault()`, `parent()`, `children()`, full model docblock; add `locations()` to `app/Models/Vault.php`
- [X] T006 [P] Create `database/factories/LocationFactory.php` (account and vault consistent, no parent by default)
- [X] T007 [P] Add `LocationCreation`, `LocationUpdate`, `LocationDeletion` to `app/Enums/UserActionEnum.php` with their descriptions naming `:name`

## Phase 3: User Story 1, add a location (P1) MVP

Independent test: spec US1.

- [X] T008 [P] [US1] Create `app/Rules/SingleEmoji.php` (one grapheme `/^\X$/u` holding `\p{Extended_Pictographic}`, a regional indicator or U+20E3) and `tests/Unit/Rules/SingleEmojiTest.php` (accepts 🏠, 🛋️, 👨‍👩‍👧, 🇫🇷, #️⃣; refuses `ab`, `a`, 🏠🏠)
- [X] T009 [US1] Create `app/Actions/CreateLocation.php`: sanitise name and address with `TextSanitizer`, empty emoji or address to null, refuse a vault of another account and a parent outside the vault with `ModelNotFoundException`, log `LocationCreation` on the `low` queue
- [X] T010 [US1] Write `tests/Unit/Actions/CreateLocationTest.php`: name only at top level, with parent, emoji and address, parent of another vault refused, vault of another account refused, log dispatched
- [X] T011 [US1] Create `app/ViewModels/Vaults/LocationsViewModel.php` with the form side: parent options by full path joined by " › ", form title ("inside :name" when opened from a row), address help naming the nearest ancestor's address, the twelve emoji suggestions of spec Assumptions
- [X] T012 [US1] Create `app/Http/Controllers/App/Vaults/LocationController.php` (`index`, `new`, `create`) and `ChildLocationController.php` (`new`), with the validation of contracts/web-routes.md, and add the four routes under `flag:locations` in `routes/web.php`
- [X] T013 [US1] Create `resources/views/app/vaults/locations/index.blade.php` and `_form.blade.php`: shell, breadcrumb vault › Settings › Locations, sidebar `vault-settings`, panel with name, parent select, emoji `role="radiogroup"` modelled on the colour group of `settings.blade.php` plus a free field, address textarea, panel swapped with `x-target` (research R3)
- [X] T014 [US1] Link to `locations.index` from `resources/views/app/vaults/settings.blade.php` when `FlagState::isOn('locations', $account)`, through `app/ViewModels/Vaults/VaultSettingsViewModel.php`
- [X] T015 [US1] Write `tests/Feature/Controllers/App/Vaults/LocationControllerTest.php` for index, new, children.new and create: answers, redirect with status, name required, parent of another vault refused, `ab` emoji refused, flag off gives 404

## Phase 4: User Story 2, the tree (P1)

Independent test: spec US2.

- [X] T016 [US2] Add the tree to `LocationsViewModel`: one query, rows depth first, `SORT_NATURAL | SORT_FLAG_CASE` by name at each level, level, emoji or icon (`place` at top level, `layers` below), line built from the address, else "at :name's address" for a direct child, else the count via `trans_choice()`, else "No address"
- [X] T017 [US2] Write `tests/Unit/ViewModels/Vaults/LocationsViewModelTest.php`: order and levels, other vault left out, homonyms told apart by path, each line case, empty vault
- [X] T018 [US2] Create `resources/views/app/vaults/locations/_row.blade.php` as nested `<ul>` per level (research R8), and the `x-empty-state` in `index.blade.php`

## Phase 5: User Story 3, edit a location (P2)

Independent test: spec US3.

- [X] T019 [US3] Create `app/Actions/UpdateLocation.php`: same sanitising and vault check as T009, walk up from the new parent and throw `InvalidArgumentException` with a translated "inside itself" message on meeting the location, log `LocationUpdate`
- [X] T020 [US3] Write `tests/Unit/Actions/UpdateLocationTest.php`: values changed, moved with its subtree, parent cleared, itself refused, a grandchild refused, another vault refused, another account refused, log dispatched
- [X] T021 [US3] Leave the edited location and its descendants out of the parent options in `LocationsViewModel`, and test it in `LocationsViewModelTest.php`
- [X] T022 [US3] Add `edit` and `update` to `LocationController.php` and their routes; catch `InvalidArgumentException` into `withErrors(['parent' => ...])`
- [X] T023 [US3] Extend `LocationControllerTest.php`: edit answers prefilled, update redirects with status, the cycle refusal reaches the form on `parent`, a location of another vault gives 404

## Phase 6: User Story 4, delete a location (P2)

Independent test: spec US4.

- [X] T024 [P] [US4] Create `app/Policies/LocationPolicy.php` with `delete` denying while children exist, with `trans_choice(':count location is inside it. Move it first.|:count locations are inside it. Move them first.')`
- [X] T025 [P] [US4] Write `tests/Unit/Policies/LocationPolicyTest.php`: permitted, refused with the count, `it_refuses_when_nobody_is_signed_in`
- [X] T026 [US4] Create `app/Actions/DestroyLocation.php`: account check, `$user->authorize('delete', $location)`, delete, log `LocationDeletion` with the name
- [X] T027 [US4] Write `tests/Unit/Actions/DestroyLocationTest.php`: empty one deleted and logged, one with a child refused and kept, another account refused
- [X] T028 [US4] Add the delete answer per row to `LocationsViewModel` with `$user->inspect('delete', ...)`, and test it in `LocationsViewModelTest.php`
- [X] T029 [US4] In `_row.blade.php`, an allowed delete uses `x-delete-action`; a refused one stays rendered `aria-disabled="true"` with `aria-describedby` on its reason
- [X] T030 [US4] Add `destroy` to `LocationController.php` and its route; catch `AuthorizationException` into `withErrors()`
- [X] T031 [US4] Extend `LocationControllerTest.php`: destroy redirects with status, the refusal reaches the screen as an error

## Phase 7: Polish

- [X] T032 Run `php artisan monica:localize` and translate every reported string in every locale, following `.claude/rules/translations.md`; `php artisan monica:localize --check` passes
- [X] T033 [P] `vendor/bin/phpstan analyse --no-progress` reports nothing; `vendor/bin/pint --dirty --format agent` leaves nothing
- [X] T034 Run the whole suite, `tests/Feature/RouteProtectionTest.php` included
- [X] T035 Record the feature with the `feature-documentator` skill
- [ ] T036 Hand quickstart.md's checks by hand to the user (keyboard, screen reader, phone, 200% zoom); the pull request states the amendment, the API deferral and the three design departures of plan.md

## Dependencies

- Phase 1 and 2 before any story. US1 before US2 (same screen and view model); US3 and US4 after US2, independent of each other.
- Within a story: rule, policy and action before the view model, view model before the controller and views, controller before its test.
- Parallel: T002, T003; T006, T007; T008 with T009; T024 with T025.

## Strategy

MVP is phases 1 to 4: locations can be added and read as a tree. US3 and US4 follow, then polish.

## Departures while building

- T013: links and forms navigate through Turbo rather than `x-target`, so the URL of the open form stays in the address bar.
- T013: the emoji choice is a group of `aria-pressed` buttons, as the colour choice of the vault settings is, rather than a `radiogroup`.
- T016: names are sorted with `LocaleCollation::sortByCollator()`, which the rest of the application uses, rather than `SORT_NATURAL`.
- After the first build, the user moved locations from the vault to the account (spec Clarifications). Table, model, actions, routes (`settings/account/locations`), controllers, view model, views and tests moved with it; the vault settings link was removed and the account settings sidebar and overview gained a Locations entry. The task lines above keep their original wording.
- The user then asked for the `locations` flag to go: `app/Features/Locations.php`, the route middleware, the sidebar and overview checks and the flag tests are removed (T003 and the flag parts of T012, T014, T015 no longer hold).
