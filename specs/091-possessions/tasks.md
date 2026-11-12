# Tasks: Possessions

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api-and-mcp.md](contracts/api-and-mcp.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: twelve fixed concepts, Possession added after Possession type, one sentence saying it belongs to a vault and carries a type, an optional location and one value per field of its type; Sync Impact Report; version 2.8.0, amended 2026-10-26 (research R1)
- [X] T002 [P] Name Possession in `spec/spec.md` beside Possession type, and write `spec/models/062-possession-in-progress.md` and `spec/models/063-possession-field-value-in-progress.md` in the shape of `spec/models/059-possession-type-in-progress.md`, from data-model.md

## Phase 2: Foundational

- [X] T003 Create `app/Enums/PossessionStatusEnum.php` implementing `Translatable`: Owned `owned`, Ordered `ordered`, Lent `lent`, Sold `sold`, GivenAway `given_away`, Lost `lost`, Stolen `stolen`, ThrownAway `thrown_away`, `label()` in sentence case, `translationKeys()` (research R7); test in `tests/Unit/Enums/PossessionStatusEnumTest.php`
- [X] T004 Add `PossessionCreation = 'possession_created'`, `PossessionUpdate = 'possession_updated'`, `PossessionDeletion = 'possession_deleted'` after the possession field cases of `app/Enums/UserActionEnum.php`, with their descriptions in the enum's existing shape
- [X] T005 Create `database/migrations/2026_10_26_000002_create_possessions_table.php`, `up()` only, both tables exactly as data-model.md (every column with a `->comment()`, as `2026_10_26_000000_create_possession_types_table.php`): `current_location_id` nullable `nullOnDelete()`, every other foreign key `cascadeOnDelete()`; `name` string 255; `description` text nullable; `identifier` string 255 nullable; `status` string default `owned`; `quantity` unsigned integer default 1; `is_featured` boolean default false; `position` unsigned integer nullable; unique `(possession_id, possession_field_id)` on values
- [X] T006 Create `app/Models/Possession.php` (`HasFactory`, `HasUuids`, `ResolvesModelInVault`, `#[RouteKey('uuid')]`, `#[Fillable]`, status cast to `PossessionStatusEnum`, full model docblock) with `account`, `vault`, `possessionType`, `location` (`current_location_id`) and `values` relations, and `app/Models/PossessionFieldValue.php` with `possession` and `field`; add `possessions()` to `Vault` and `PossessionType`, `values()` to `PossessionField`
- [X] T007 [P] Create `database/factories/PossessionFactory.php` (a type and vault of one account; state `featured(int $position)`) and `PossessionFieldValueFactory.php`

## Phase 3: User Story 1, record a possession (P1) MVP

Independent test: spec US1.

- [X] T008 [US1] Create `app/Actions/CreatePossession.php` in the shape of `CreatePossessionType`: inputs `user`, `vault`, `possessionType`, `name`, `description`, `location`, `identifier`, `status`, `quantity`, `isFeatured`; refuse a vault, type or location of another account with `ModelNotFoundException`; name 1 to 255, description up to 2,000, identifier up to 255, quantity 1 to 1,000,000 (`ValidationException` naming the attribute); when featured, `position = max(position in vault) + 1`; log `PossessionCreation` on `low`
- [X] T009 [US1] Create `app/Actions/UpdatePossession.php`: same inputs without the type, same checks; joining the featured order appends, leaving it sets `position` null and decrements the vault's featured positions after it; log `PossessionUpdate`
- [X] T010 [US1] Create `app/Actions/DestroyPossession.php`: delete it for good with its values, close the featured gap; log `PossessionDeletion`
- [X] T011 [US1] Write `tests/Unit/Actions/CreatePossessionTest.php`, `UpdatePossessionTest.php`, `DestroyPossessionTest.php`: defaults (owned, 1, not featured); every refusal; another account's vault, type, location; featured append, leave and gap closing; values gone on destroy; log dispatched
- [X] T012 [US1] Create `app/Http/Resources/PossessionResource.php` with the answer of the contract, `values` empty until US2
- [X] T013 [US1] Create `app/Http/Controllers/Api/Possessions/PossessionController.php` (`index`, `show`, `create`, `update`, `destroy`) in the shape of `Api\EntityController`: vault through `$request->user()->account->vaults()->addressedBy()`, possession by uuid inside the vault, inline validation, `type_id` and `location_id` looked up in the account and refused 422 on that attribute; `index` paginated as `Api\ContactController` (`per_page` 25, max 100), filters `type`, `status`, `featured`, `sort` `name` or `position`
- [X] T014 [US1] Add the five routes of the contract to `routes/api.php` next to `contacts`, `{possession}` constrained to a uuid
- [X] T015 [US1] Write `tests/Feature/Controllers/Api/Possessions/PossessionControllerTest.php` with a shared `$jsonStructure`: each route answers; validation 422s; a type of another account is 422; another vault's possession is 404; the list leaves out other vaults and accounts and honours each filter

## Phase 4: User Story 2, the details its type asks for (P1)

Independent test: spec US2. Depends on T006, T012.

- [X] T016 [US2] Create `app/Actions/UpdatePossessionFieldValues.php`: input `values` as `array<string, mixed>` keyed by field uuid; every key must be a field of the possession's type; validate by kind (text up to 2,000, numeric, date `Y-m-d`, boolean, one of the field's current `options`, integer 1 to `PossessionFieldKindEnum::MAX_RATING`) and collect every failure into one `ValidationException` keyed `values.{uuid}`; then, in one transaction, upsert normalised values and delete those given `null` or `""`; log `PossessionUpdate` (research R2, R5)
- [X] T017 [US2] In `app/Actions/UpdatePossessionField.php`, delete the field's values when the kind changes (research R3)
- [X] T018 [US2] Fill `values` in `PossessionResource` in the type's order (groups then fields by position, standalone fields first as the type resource does), each `{field_id, name, kind, group, value}`, `value` cast back by kind
- [X] T019 [US2] Create `app/Http/Controllers/Api/Possessions/PossessionFieldValueController.php` (`update`) and its route `api.v1.possessions.values`
- [X] T020 [US2] Write `tests/Unit/Actions/UpdatePossessionFieldValuesTest.php` (one of each kind stored and read back, every misfit named, a field of another type refused, null removes, all or nothing), extend `tests/Unit/Actions/UpdatePossessionFieldTest.php` (kind change deletes values, rename keeps them), and `tests/Feature/Controllers/Api/Possessions/PossessionFieldValueControllerTest.php`

## Phase 5: User Story 3, featured order (P2)

Independent test: spec US3. Depends on T008 to T010.

- [X] T021 [US3] Create `app/Actions/UpdatePossessionPosition.php` in the shape of `UpdatePossessionFieldPosition`: refuse a possession not featured (`ValidationException` on `position`), clamp to 1..count, shift the others; log `PossessionUpdate`
- [X] T022 [US3] Create `app/Http/Controllers/Api/Possessions/PossessionPositionController.php` (`update`) and its route `api.v1.possessions.position`
- [X] T023 [US3] Write `tests/Unit/Actions/UpdatePossessionPositionTest.php` and `tests/Feature/Controllers/Api/Possessions/PossessionPositionControllerTest.php`

## Phase 6: Cascades (FR-009)

- [X] T024 In `app/Actions/DestroyPossessionType.php`, count the type's possessions, delete the type, close the featured order of every vault that held one, put the count in the log entry and return it (research R3); extend `tests/Unit/Actions/DestroyPossessionTypeTest.php` with the cascade, the count and the closed order, and add a test that deleting a location clears `current_location_id` and keeps the possession
- [X] T025 Make `app/Mcp/Tools/DeletePossessionType.php` answer with the count through `trans_choice()` (zero handled with its own sentence, per `.claude/rules/code-style.md`), and extend its test

## Phase 7: User Story 4, the assistant and the docs (P3)

Independent test: spec US4. Depends on phases 3 to 5.

- [X] T026 [US4] Add `possession(Vault $vault, string $address): Possession` to `app/Mcp/Tools/MirrorsAnApiCapability.php` answering "That possession does not exist."
- [X] T027 [US4] Create the seven tools of the contract in `app/Mcp/Tools` (`ListPossessions`, `GetPossession`, `CreatePossession`, `UpdatePossession`, `DeletePossession`, `SetPossessionPosition`, `SetPossessionValues`) in the shape of the contact tools, each mirroring its route, calling the same action, answering `PossessionResource`; register them in `app/Mcp/Servers/MonicaServer.php`
- [X] T028 [US4] Write one test per tool under `tests/Unit/Mcp/Tools`, in the shape of the possession type tool tests
- [X] T029 [US4] Add "Possessions" and "Possessions, changing" groups to `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T030 [P] [US4] Write `resources/docs/api/27-possessions.php` in the shape of `26-possession-types.php`, per the `api-docs-writer` skill: every endpoint, attribute, status, value format and refusal, with examples from The Office

## Phase 8: Polish

- [X] T031 [P] In `docs/portal/en/9-templates-and-customisation/12-possession-types.md`, say that deleting a type deletes the possessions of that type and their details; set `last_updated`
- [X] T032 [P] Add lines to `docs/FEATURES.md` per the `feature-documentator` skill
- [ ] T033 Run `php artisan monica:localize`; translate the status labels and every new refusal, French first under `.claude/rules/translations.md`, then every other locale; `--check` passes
- [ ] T034 Run Pint, PHPStan (`--memory-limit=2G`) and the whole suite (`php -d memory_limit=4G vendor/bin/phpunit --no-progress`)

## Dependencies

- T001 to T007 before every story. US1 before US2, US3 and the cascades (they extend its actions and resource). US4 after US1 to US3.
- T033 after every string exists. T034 last.

## Parallel

- T002 and T007 alongside the rest of their phase. T030, T031 and T032 alongside T026 to T029.
- Within US1, the three action tests (T011) once T008 to T010 exist.

## Strategy

MVP is US1: a possession recorded, read, changed and deleted through the API. US2 adds the values,
US3 the order, the cascades close FR-009, US4 opens it to the assistant and documents it.
