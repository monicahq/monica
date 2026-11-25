# Tasks: Default Locations

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. No test asserts markup or classes.

## Phase 1: Setup

- [X] T001 Add `LocationsSeeded = 'locations_seeded'` to `app/Enums/UserActionEnum.php` beside `LocationCreation`, described "Set up the locations the account starts with" (research R5)

## Phase 2: Foundational

- [X] T002 Create `app/Enums/DefaultLocationEnum.php` implementing `Translatable`, in the shape of `PossessionConditionEnum`: cases `House = 'house'`, `LivingRoom = 'living_room'`, `Kitchen = 'kitchen'`, `Bedroom = 'bedroom'`, `Bathroom = 'bathroom'`, `StorageSpace = 'storage_space'`; `label()` giving "My house", "Living room", "Kitchen", "Bedroom", "Bathroom", "Storage space"; `emoji()` giving 🏠 🛋️ 🍳 🛏️ 🚿 📦 exactly as in the spec's catalogue; `isInsideHouse(): bool` false for `House` only; `translationKeys()` returning `[$this->label()]` (research R1)
- [X] T003 Write `tests/Unit/Enums/DefaultLocationEnumTest.php`: every `emoji()` passes `App\Rules\SingleEmoji` through `Validator::make()`, and only `House` is outside the house (research R8)

## Phase 3: User Story 1, a new account starts with a house and its rooms (P1) MVP

Independent test: spec US1.

- [X] T004 [US1] Create `app/Actions/CreateDefaultLocations.php` in the shape of `CreateDefaultPossessionConditions`: return `[]` when `$this->user->account->locations()->exists()`; otherwise in one `DB::transaction` create `House` with `parent_id` null, then every other case with `parent_id` set to it, each with `name` = `__($case->label(), [], $this->user->locale ?? config('app.locale'))`, `emoji` = `$case->emoji()`, `address` null, through `Location::query()->create()` and not `CreateLocation`; log `LocationsSeeded` once on `low`; return `list<Location>` (research R2, R3, R4)
- [X] T005 [US1] Write `tests/Unit/Actions/CreateDefaultLocationsTest.php`: six locations, My house at the top level and the five rooms with it as parent, each with its emoji and no address; a French user gets "Maison", "Salon", "Cuisine", "Chambre", "Salle de bain", "Rangement"; one `LogUserAction` dispatched
- [X] T006 [P] [US1] Create `app/Jobs/SeedLocations.php` in the shape of `SeedPossessionConditions`, with its one sentence class docblock, calling the action (research R6)
- [X] T007 [US1] Append `new SeedLocations(user: $this->user)` after `SeedPossessionConditions` in the chain of `app/Actions/CreateAccount.php::seedTheAccount()`
- [X] T008 [US1] Write `tests/Unit/Jobs/SeedLocationsTest.php` in the shape of `SeedPossessionConditionsTest` (dispatching seeds the user's account) and extend `tests/Unit/Actions/CreateAccountTest.php` so the chain asserts `SeedLocations`

## Phase 4: User Story 2, the seeded locations are ordinary locations (P1)

Independent test: spec US2.

- [X] T009 [US2] Add to `tests/Unit/Actions/CreateDefaultLocationsTest.php`: an account holding a location of its own gets nothing and no log; running the action twice leaves six; after deleting a seeded room, running it again does not bring it back (US2.3, SC-002)

## Phase 5: User Story 3, accounts that existed before (P2)

Independent test: spec US3.

- [X] T010 [US3] Create `database/migrations/2026_10_26_000007_backfill_locations.php`, `up()` only, in the shape of `2026_10_26_000005_backfill_possession_conditions.php`: `User::query()->whereDoesntHave('account.locations')->chunkById(200, ...)` running `CreateDefaultLocations` (research R7)
- [X] T011 [US3] Write `tests/Unit/Migrations/BackfillLocationsTest.php` in the shape of `BackfillPossessionTypesTest`: seeds an account with none, leaves one holding a location, adds nothing on a second run

## Phase 6: Polish

- [X] T012 Run `php artisan monica:localize`; write `lang/fr_FR.json` first under the French typography and glossary of `.claude/rules/translations.md` ("location" is "emplacement"; the six names are those of the spec's catalogue), then the other locales in one batch through a Sonnet subagent with the French file as reference; `php artisan monica:localize --check` passes
- [X] T013 [P] Add one sentence to `docs/portal/en/9-templates-and-customisation/13-locations.md` saying an account starts with a house and five rooms inside it, which can be renamed, moved or deleted, with no dash used as punctuation (FR-010)
- [X] T014 [P] Replace "no location is created automatically, for an account" in `specs/086-location-management/spec.md` line 119 with a pointer to specification 097, keeping "for an owned house" (research R9)
- [X] T015 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, then the whole suite with `php artisan test --compact`
- [ ] T016 Walk quickstart.md by hand on monica.test, never migrating the local database
- [X] T017 Record the feature in `docs/FEATURES.md` with the feature-documentator skill (always the last task)

## Dependencies

- T001, T002 → everything. T003 after T002.
- US1: T004 → T005; T006 after T004; T007 after T006; T008 after T007.
- US2: T009 after T005.
- US3: T010 after T004; T011 after T010.
- Polish: T012 after T002; T013, T014 any time; T015 to T017 last, in order.

Parallel: T006 beside T005; T013 and T014 beside anything; US2 and US3 beside each other once T004 exists.

MVP: Phases 1 to 3. US2 is tests only; US3 matters before release, though locations are unreleased so no account holds one yet.
