# Tasks: Default Possession Types

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. No test asserts markup or classes.

## Phase 1: Setup

- [X] T001 Add `PossessionTypesSeeded = 'possession_types_seeded'` to `app/Enums/UserActionEnum.php` beside `RelationshipTypesSeeded`, described "Set up the possession types the account starts with" (research R5)

## Phase 2: Foundational

- [X] T002 Create `app/Enums/PossessionTypeEnum.php` implementing `Translatable`, one case per catalogue entry of spec.md in its order, with `label()`, `colour(): VaultColourEnum` per the colour table of data-model.md, `blocks()` returning `list<{name: ?string, fields}>`, the unnamed block first, with each field `{name, kind: PossessionFieldKindEnum, options: list<string>}` copied exactly from spec.md, static `codes()` listing every option written in `code` style in spec.md, and `translationKeys()` returning type, group, field and option names without the codes and without duplicates (research R1, R2)
- [X] T003 Write `tests/Unit/Enums/PossessionTypeEnumTest.php`: 25 cases, `translationKeys()` holds no code and no duplicate, every option of every select field is either a translation key or a code

## Phase 3: User Story 1, a new account starts with the 25 types (P1) MVP

Independent test: spec US1.

- [X] T004 [US1] Create `app/Actions/CreateDefaultPossessionTypes.php` in the shape of `CreateDefaultRelationshipTypes`: return `[]` when `$this->user->account->possessionTypes()->exists()`, otherwise in one `DB::transaction` write each case as a `PossessionType` (`name` = `__($case->label(), [], $this->user->locale ?? config('app.locale'))`, `colour` = `$case->colour()->value`), its groups (`position` from 1) and its fields (`position` from 1 within the group or among the standalone fields, `possession_field_group_id` null when standalone, `options` translated the same way for a select and `[]` when it has none, null for every other kind); log `PossessionTypesSeeded` once on `low`; return the 25 types (research R3, R4)
- [X] T005 [US1] Write `tests/Unit/Actions/CreateDefaultPossessionTypesTest.php`: 25 types; Comics has My rating standalone then Publishing info (Issue #, Publisher, Writer, Artist, Cover date) then Condition & grading (Variant, Signed) with their kinds; Coins Grade offers the 18 grades in order; every select field offers at least one choice; Other has no group and no field; a French user gets French names while `PSA 10` and `NTSC` stay as they are; one `LogUserAction` dispatched; for every case, `ExportPossessionType` of the seeded type matches its `blocks()` (SC-002)
- [X] T006 [P] [US1] Create `app/Jobs/SeedPossessionTypes.php` in the shape of `SeedRelationshipTypes`, with a class docblock saying why it waits on the queue, calling the action
- [X] T007 [US1] Append `new SeedPossessionTypes(user: $this->user)` to the chain in `app/Actions/CreateAccount.php::seedTheAccount()`
- [X] T008 [US1] Write `tests/Unit/Jobs/SeedPossessionTypesTest.php` (dispatching seeds the user's account) and extend `tests/Unit/Actions/CreateAccountTest.php` so the chain asserts `SeedPossessionTypes`

## Phase 4: User Story 2, the seeded types are ordinary types (P1)

Independent test: spec US2.

- [X] T009 [US2] Add to `tests/Unit/Actions/CreateDefaultPossessionTypesTest.php`: an account holding a type of its own gets nothing and no log; running the action twice leaves 25 types; after deleting a seeded type, running it again does not bring it back (US2.2, SC-003)

## Phase 5: User Story 3, accounts that existed before (P2)

Independent test: spec US3.

- [X] T010 [US3] Create `database/migrations/2026_10_26_000001_backfill_possession_types.php`, `up()` only, running `CreateDefaultPossessionTypes` for every user whose account holds no type, in chunks of 200 by id (research R7)
- [X] T011 [US3] Write `tests/Unit/Migrations/BackfillPossessionTypesTest.php` in the shape of `BackfillNotificationPreferencesTest`: seeds an account with none, leaves one holding a type, adds nothing on a second run, gives nothing to an account nobody signs in to

## Phase 6: Polish

- [X] T012 Run `php artisan monica:localize`; write `lang/fr_FR.json` first under the French typography and glossary of `.claude/rules/translations.md`, then the other locales in batches through Sonnet subagents with the French file as reference; `php artisan monica:localize --check` passes
- [X] T013 [P] Add a section to `docs/portal/en/9-templates-and-customisation/12-possession-types.md` saying an account starts with 25 types that can be changed or deleted, with no dash used as punctuation (FR-011)
- [X] T014 [P] Remove "No type is seeded" wording anywhere else it remains, checked with `grep -rn "seeded" specs/088-possession-types docs/portal`
- [X] T015 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, then the whole suite with `php artisan test --compact`
- [ ] T016 Walk quickstart.md by hand on erica.test
- [X] T017 Record the feature in `docs/FEATURES.md` with the feature-documentator skill (always the last task)

## Dependencies

- T001, T002 → everything. T003 after T002.
- US1: T004 → T005; T006 after T004; T007 after T006; T008 after T007.
- US2: T009 after T004.
- US3: T010 after T006; T011 after T010.
- Polish: T012 after T002; T013, T014 any time; T015 to T017 last, in order.

Parallel: T006 beside T005; T013 and T014 beside anything; US2 and US3 beside each other once T006 exists.

MVP: Phases 1 to 3. US2 is tests only; US3 matters before release because every production account predates it.
