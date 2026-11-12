# Tasks: Possession Conditions

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: fourteen fixed concepts, Possession condition added after Possession tag, one sentence saying it belongs to the account, shared by every vault, and carries a name, a vault colour, an optional icon and a position in the account's order; Sync Impact Report; version 2.10.0, amended 2026-10-26 (research R1)
- [X] T002 [P] Name Possession condition in `spec/spec.md` beside Possession tag; write `spec/models/065-possession-condition-in-progress.md` in the shape of `spec/models/064-possession-tag-in-progress.md`, from data-model.md; add its row to `spec/models/README.md`

## Phase 2: Foundational

- [X] T003 Add to `app/Enums/UserActionEnum.php`, after the possession tag cases: `PossessionConditionCreation = 'possession_condition_created'`, `PossessionConditionUpdate = 'possession_condition_updated'`, `PossessionConditionPositionUpdate = 'possession_condition_position_updated'`, `PossessionConditionDeletion = 'possession_condition_deleted'`, `PossessionConditionsSeeded = 'possession_conditions_seeded'`, described "Added the possession condition :name", "Changed the possession condition :name", "Moved the possession condition :name", "Deleted the possession condition :name", "Added the default possession conditions"
- [X] T004 Create `database/migrations/2026_10_26_000004_create_possession_conditions_table.php`, `up()` only, every column with a `->comment()`: `id`; `uuid` unique; `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()`; `name` string(50) "1 to 50 characters, unique in the account ignoring case"; `colour` string(7) "one of the vault colours, as a hexadecimal value"; `icon` string(30) nullable "one of the glyphs of the section catalogue, or none"; `position` unsigned integer "1 to n within the account, without gaps"; timestamps; index `(account_id, position)`; no unique index, no soft deletes
- [X] T005 Create `app/Models/PossessionCondition.php` (`HasFactory`, `HasUuids`, `ResolvesModelInAccount`, `#[RouteKey('uuid')]`, `#[Fillable(['account_id', 'name', 'colour', 'icon', 'position'])]`, full model docblock in the shape of `PossessionTag`) with `account()`; add `possessionConditions()` to `app/Models/Account.php` after `possessionTags()`, ordered by `position`
- [X] T006 [P] Create `database/factories/PossessionConditionFactory.php`: `account_id` from `Account::factory()`, a name from ["Mint", "Like new", "Well loved", "Needs repair", "Water damaged"], `colour` a random `VaultColourEnum` value, `icon` null, `position` 1; no `unique()`

## Phase 3: User Story 1, start with a sensible list (P1)

Independent test: spec US1. A new account in French holds the six conditions in French, in order.

- [X] T007 [US1] Create `app/Enums/PossessionConditionEnum.php` implementing `Translatable`: cases `New = 'new'`, `LikeNew = 'like_new'`, `Used = 'used'`, `Worn = 'worn'`, `Damaged = 'damaged'`, `Broken = 'broken'`; `label()` "New", "Like new", "Used", "Worn", "Damaged", "Broken"; `colour(): VaultColourEnum` Green, Teal, Blue, Amber, Rust, Slate; `translationKeys()` the label (research R2)
- [X] T008 [US1] Create `app/Actions/CreateDefaultPossessionConditions.php` in the shape of `CreateDefaultPossessionTypes`: return `[]` when the account holds any condition; otherwise, in a transaction, create the six of the enum in case order with `name` `__($case->label(), [], $locale)` (user locale, else `app.locale`), `colour` `$case->colour()->value`, `icon` null, `position` 1 to 6; log `PossessionConditionsSeeded` once on `low`
- [X] T009 [US1] Write `tests/Unit/Actions/CreateDefaultPossessionConditionsTest.php`: six created in order with their colours and positions 1 to 6 and the log; written in French for a French user; nothing added when the account holds one condition
- [X] T010 [US1] Create `app/Jobs/SeedPossessionConditions.php` in the shape of `SeedPossessionTypes` and append `new SeedPossessionConditions(user: $this->user)` after `SeedPossessionTypes` in the chain of `app/Actions/CreateAccount.php::seedTheAccount()`; write `tests/Unit/Jobs/SeedPossessionConditionsTest.php` (dispatched, the account holds six); extend the existing `CreateAccount` test's chain assertion if it names the jobs
- [X] T011 [US1] Create `database/migrations/2026_10_26_000005_backfill_possession_conditions.php` in the shape of `2026_10_26_000001_backfill_possession_types.php`: `User::query()->whereDoesntHave('account.possessionConditions')->chunkById(200, …)` running `CreateDefaultPossessionConditions`

## Phase 4: User Story 2, add and list conditions (P1) MVP screen

Independent test: spec US2. Add "Mint" in teal with the star; it appears last.

- [X] T012 [US2] Create `app/Actions/CreatePossessionCondition.php` in the shape of `CreatePossessionTag`: inputs `user`, `name`, `colour` (`VaultColourEnum`, default `Blue`), `icon` (`?string`, default null); `TextSanitizer::plainText()` the name; refuse empty with `ValidationException` on `name` "Write a name for this condition."; refuse a name the account already has, compared with `mb_strtolower()`, "You already have a condition with this name."; turn `''` into null, refuse an icon not in `Section::ICONS` with `ValidationException` on `icon` "Pick an icon from the list."; `position` = the account's highest + 1 (1 when none); log `PossessionConditionCreation` `{name}` on `low`
- [X] T013 [US2] Write `tests/Unit/Actions/CreatePossessionConditionTest.php`: created with Blue and no icon, appended after the existing ones, the log dispatched; `''` icon stored as null; an icon outside the catalogue refused; empty name refused; "mint" refused beside "Mint"; the same name in another account accepted
- [X] T014 [US2] Create `app/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModel.php` per data-model.md: `user()`, `title()` "Possession conditions", `description()` "The states a possession can be in, from best to worst, like “new” or “broken”. Conditions belong to your account, so every vault uses the same list.", `createAction()`, `colours()` as `list<{value, label, tint, swatch}>` for `x-mark-picker` in the shape of `LifeEventTypeFormViewModel::colours()` without the neutral entry, `glyphs()` `Section::ICONS`, `conditions()` in position order (`list<{uuid, name, colour, colourName, icon, position, isFirst, isLast, updateAction, positionAction, destroyAction}>`), `countLine()` `trans_choice(':count condition, from best to worst.|:count conditions, from best to worst.', n)` and `''` at zero, `isEmpty()`
- [X] T015 [US2] Write `tests/Unit/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModelTest.php`: in position order, another account's left out, first and last flags, the count line at one and at three, empty at zero, eight colours and no neutral one
- [X] T016 [US2] Create `app/Http/Controllers/App/Settings/Account/PossessionConditions/PossessionConditionController.php` with `index` and `create` (inline validation: `name` `['required', 'string', 'max:50']`, `colour` `['required', Rule::enum(VaultColourEnum::class)]`, `icon` `['nullable', 'string', Rule::in(Section::ICONS)]`, bag `newPossessionCondition`, an action's `ValidationException` rethrown with `errorBag()`; redirect with `status` "“:name” is added."); add `settings.possessionConditions.index` and `.create` to `routes/web.php` after the possession tag routes, one per line
- [X] T017 [US2] Create `resources/views/app/settings/account/possession-conditions/index.blade.php` in the shape of `possession-tags/index.blade.php`: `x-settings.page` (`current="possession-conditions"`), `x-page-header`; section "Add a condition" with an `x-panel :clip="false"` holding the form: `x-data` with `name`, `icon`, `colour`; `x-input` `name` `maxlength="50"` placeholder "Mint"; `x-mark-picker` `layout="split"` with the view model's glyphs and colours (research R5); "Add condition" with the `plus` icon; a footer "Preview" (`aria-hidden="true"`) showing the tinted swatch, the icon when one is picked and the name (or "Your condition"); section "Your conditions" with the count line and `<div id="possession-conditions" x-merge="replace" x-data="sortableRows" data-sortable>` holding an `x-panel :flush="true" :clip="false"` with an `<ol aria-label="Your conditions">` of rows, or `x-empty-state` "No conditions yet" with the `star` icon and "Conditions you add here will say what state a possession is in."; every form `x-target="possession-conditions"`. Read each component before calling it (`.claude/rules/components.md`)
- [X] T018 [US2] Create `resources/views/app/settings/account/possession-conditions/_row.blade.php` for viewing: an `<li>` holding an `x-sortable-row` (`row-id` the position or id, `action` the row's `positionAction`, `target` `possession-conditions`) with the tinted swatch carrying the icon when there is one (`aria-hidden`), the colour name in `sr-only`, the name
- [X] T019 [US2] Add `['key' => 'possession-conditions', 'label' => __('Possession conditions'), 'icon' => 'star', 'url' => route('settings.possessionConditions.index')]` after Possession tags in `resources/views/components/settings/sidebar.blade.php` (research R8)
- [X] T020 [US2] Write `tests/Feature/Controllers/App/Settings/Account/PossessionConditions/PossessionConditionControllerTest.php`: the index answers and shows "Possession conditions" with the add form's `name`, `icon` and `colour` fields and the `<ol aria-label="Your conditions">`; create redirects with its flash; a name of 51 characters, a colour outside the palette and an unknown icon refused; the duplicate refusal reaches the form as an error on `name`

## Phase 5: User Story 3, change a condition (P1)

Independent test: spec US3. Rename "Worn" to "Well loved", amber, heart icon; then remove the icon. Depends on US2.

- [X] T021 [US3] Create `app/Actions/UpdatePossessionCondition.php` in the shape of `UpdatePossessionTag`: inputs `user`, `possessionCondition`, `name`, `colour`, `icon` (`?string`); refuse another account's with `ModelNotFoundException`; the same sanitising and refusals as T012, the duplicate check leaving the condition itself out; `''` stored as null; position untouched; log `PossessionConditionUpdate` `{name}`
- [X] T022 [US3] Write `tests/Unit/Actions/UpdatePossessionConditionTest.php`: renamed, recoloured and given an icon, with the log; icon removed with null; "USED" accepted for "Used"; another condition's name refused ignoring case; empty refused; unknown icon refused; position unchanged; another account's answers `ModelNotFoundException`
- [X] T023 [US3] Add `update` to `PossessionConditionController` (same validation as T016, bag `possessionCondition{uuid}`; redirect with `status` "Saved.") and `settings.possessionConditions.update` (`PUT`, `whereUuid('possessionCondition')`) to `routes/web.php`
- [X] T024 [US3] In `_row.blade.php`, add "Edit" (ghost, small, `aria-label` "Edit :name") turning the row into a form with `x-data` holding `editing`, `icon`, `colour`: `x-input` `name`, `x-mark-picker` (`id` unique per row) with the condition's icon and colour, "Cancel" and "Save changes", in the shape of `possession-tags/_row.blade.php`: reopened when its bag holds an error, Escape and "Cancel" close it and restore the icon and colour, focus to the name on open and back to "Edit" on close
- [X] T025 [US3] Extend `PossessionConditionControllerTest.php`: update redirects with its flash and saves; validation of `name`, `colour` and `icon` refused

## Phase 6: User Story 5, delete a condition (P1)

Independent test: spec US5. Delete "Damaged"; the rest close the gap. Depends on US2.

- [X] T026 [US5] Create `app/Actions/DestroyPossessionCondition.php`: refuse another account's with `ModelNotFoundException`; in a transaction delete it and renumber the account's remaining conditions 1..n in their order; log `PossessionConditionDeletion` `{name}`
- [X] T027 [US5] Write `tests/Unit/Actions/DestroyPossessionConditionTest.php`: deleted with the log, the others renumbered without a gap and in the same relative order; another account's refused
- [X] T028 [US5] Add `destroy` to `PossessionConditionController` (redirect with `status` "“:name” is deleted.") and `settings.possessionConditions.destroy` (`DELETE`, `whereUuid('possessionCondition')`) to `routes/web.php`
- [X] T029 [US5] In `_row.blade.php`, add "Delete" (ghost, danger, `aria-label` "Delete :name") opening `x-modal.warning` titled "Delete the condition “:name”?", confirming "Delete condition", saying "This cannot be undone.", targeting `possession-conditions` (research R7)
- [X] T030 [US5] Extend `PossessionConditionControllerTest.php`: destroy redirects with its flash and the condition is gone

## Phase 7: User Story 4, reorder conditions (P2)

Independent test: spec US4. Move "Mint" from last to first from the keyboard. Depends on US2.

- [X] T031 [US4] Create `app/Actions/UpdatePossessionConditionPosition.php` in the shape of `UpdateEntityTypePosition`: inputs `user`, `possessionCondition`, `position`; refuse another account's with `ModelNotFoundException`; reject the condition from the ordered list, splice it at `max(0, position - 1)`, renumber 1..n in a transaction; log `PossessionConditionPositionUpdate` `{name}`
- [X] T032 [US4] Write `tests/Unit/Actions/UpdatePossessionConditionPositionTest.php`: moved up and down with the others shifted and the log; a position past the end puts it last; another account's refused
- [X] T033 [US4] Create `app/Http/Controllers/App/Settings/Account/PossessionConditions/PossessionConditionPositionController.php` (`update`; `position` `['required', 'integer', 'min:1']`; redirect to the index with `status` "“:name” is moved to position :position.", `moved` the condition's uuid and `direction` up or down) in the shape of `PossessionFieldGroupPositionController`; add `settings.possessionConditionPosition.update` (`PUT`, `settings/account/possession-conditions/{possessionCondition}/position`, `whereUuid`) to `routes/web.php`
- [X] T034 [US4] In `_row.blade.php`, add "Move :name up" (absent on the first row) and "Move :name down" (absent on the last) `x-icon-button`s, each in a `PUT` form to `positionAction` with the neighbouring `position`, targeting `possession-conditions`, taking focus after a move through `session('moved')` and `session('direction')` as `possession-types/_group.blade.php` does (research R6)
- [X] T035 [US4] Write `tests/Feature/Controllers/App/Settings/Account/PossessionConditions/PossessionConditionPositionControllerTest.php`: update redirects with its flash, `moved` and `direction`; a position of 0 refused; the index shows "Move :name up" on every row but the first

## Phase 8: User Story 6, through the API and an assistant (P2)

Independent test: spec US6. Depends on the five actions.

- [X] T036 [US6] Create `app/Http/Resources/PossessionConditionResource.php`: `type` `possession_condition`, `id`, `attributes {name, colour, icon, position, created_at, updated_at}`, `links {self: api.v1.possessionConditions.show, web: settings.possessionConditions.index}`
- [X] T037 [US6] Create `app/Http/Controllers/Api/PossessionConditions/PossessionConditionController.php` (`index`, `show`, `create`, `update`, `destroy`) in the shape of `Api\PossessionTags\PossessionTagController`: `index` in position order, paginated in memory with `meta`; looked up in the account by uuid (`firstOrFail`); `create` takes `name` `['required', 'string', 'max:50']`, `colour` `['nullable', Rule::enum(VaultColourEnum::class)]` (Blue when left out), `icon` `['nullable', 'string', Rule::in(Section::ICONS)]`, answers 201; `update` takes `name` and `colour` required and `icon` nullable (left out or null removes it); `destroy` answers 204. Create `PossessionConditionPositionController` (`update`, `position` `['required', 'integer', 'min:1']`, answers the resource)
- [X] T038 [US6] Add the six routes of the contract to `routes/api.php` after the possession tag routes, `{possessionCondition}` constrained to `[0-9a-fA-F-]{36}`, names `api.v1.possessionConditions`, `.show`, `.create`, `.update`, `.position`, `.destroy`
- [X] T039 [US6] Write `tests/Feature/Controllers/Api/PossessionConditions/PossessionConditionControllerTest.php` and `PossessionConditionPositionControllerTest.php` with a shared `$jsonStructure`: each route answers; listed in position order; another account's left out; another account's 404; 422 on `name`, `colour`, `icon` and `position`
- [X] T040 [US6] Add `possessionCondition(Request $request, string $address): PossessionCondition` to `app/Mcp/Tools/MirrorsAnApiCapability.php` answering "That possession condition does not exist."
- [X] T041 [US6] Create `ListPossessionConditions`, `GetPossessionCondition` (both `#[IsReadOnly]`), `CreatePossessionCondition`, `UpdatePossessionCondition` (keeps what is left out; `icon: ""` removes it), `SetPossessionConditionPosition` (in the shape of `SetEntityTypePosition`), `DeletePossessionCondition` (`#[IsDestructive]`, "The possession condition is deleted.") in `app/Mcp/Tools`; register them in `app/Mcp/Servers/MonicaServer.php` after the possession tag tools
- [X] T042 [US6] Write one test per tool under `tests/Unit/Mcp/Tools`, in the shape of the possession tag tool tests
- [X] T043 [US6] Add "Possession conditions" and "Possession conditions, changing" groups to `app/ViewModels/Docs/McpDocsViewModel.php`; update `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php` (124 to 130 tools)
- [X] T044 [P] [US6] Write `resources/docs/api/29-possession-conditions.php` in the shape of `28-possession-tags.php`, per the `api-docs-writer` skill, six sections, examples from The Office; update `ApiDocsControllerTest` (188 to 194), `ApiDocsViewModelTest`, `ApiDocsReturnFieldsTest`

## Phase 9: Polish

- [X] T045 [P] Write `docs/portal/en/9-templates-and-customisation/15-possession-conditions.md` per the `writer` skill, id `customise.possession-conditions`; add it to `tests/Unit/ViewModels/Docs/PortalCatalogueTest.php`; no dash used as punctuation
- [X] T046 [P] Add a line to `docs/FEATURES.md` per the `feature-documentator` skill
- [X] T047 Confirm again that no screen shows the key "New" (research R3); run `php artisan monica:localize`; translate every new string, French first, under `.claude/rules/translations.md`, choosing the glossary word for "condition" from the existing translations of "Condition" and adding its row; set every locale's "New" to the condition sense; `--check` passes
- [X] T048 Run Pint, PHPStan (`--memory-limit=2G`) and the whole suite (`php -d memory_limit=4G vendor/bin/phpunit --no-progress`)
- [ ] T049 Look at the screen in light and dark, at phone width and zoomed to 200%, with the keyboard alone, per quickstart.md "By hand"

## Dependencies

- T001 to T006 before every story. US1 needs only the foundation. US2 before US3, US4 and US5 (they extend its controller, routes and row). US6 after the five actions (T008, T012, T021, T026, T031).
- T047 after every string exists. T048 and T049 last.

## Parallel

- T002 and T006 alongside the rest of their phase.
- US1 alongside US2: they touch different files.
- US3, US4 and US5 once US2 is done, except where they edit `_row.blade.php`, `PossessionConditionController.php` and `routes/web.php`: those in sequence.
- T044, T045 and T046 alongside T040 to T043.

## Strategy

US1 and US2 together are the MVP: an account starts with six conditions and the screen lists them
and adds more. US3 and US5 complete the management. US4 gives the order its meaning. US6 opens it to
the API and the assistant and documents it, which principle X requires before the feature is done.
