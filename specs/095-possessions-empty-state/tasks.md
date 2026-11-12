# Tasks: Possessions Empty State

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: the Possession type sentence becomes "A Possession type belongs to the account and carries a name, a vault colour, an optional icon, and its own ordered groups and fields, ..."; Sync Impact Report; version 2.12.0, amended 2026-10-26 (research R2)
- [X] T002 [P] Add `icon  string (30)  one of the glyphs of the section catalogue, or none` to the Fields of `spec/models/059-possession-type-in-progress.md`, and the rules: defaults carry an icon, a type without one is drawn with the stand-in glyph, export and import carry it

## Phase 2: Foundational

- [X] T003 Edit `database/migrations/2026_10_26_000000_create_possession_types_table.php`: after `colour`, `$table->string('icon', 30)->nullable()->comment('one of the glyphs of the section catalogue, or none');` (research R1); leave the backfill migration alone
- [X] T004 Edit `app/Models/PossessionType.php`: `icon` in `#[Fillable]`, `@property string|null $icon`; `database/factories/PossessionTypeFactory.php` sets `icon` null
- [X] T005 Add `icon(): string` to `app/Enums/PossessionTypeEnum.php` beside `colour()`, a `match` with the mapping of data-model.md; test in `tests/Unit/Enums/PossessionTypeEnumTest.php` that every case's icon is in `Section::ICONS` and none is `Section::DEFAULT_ICON`
- [X] T006 Write `$case->icon()` in `app/Actions/CreateDefaultPossessionTypes.php`; add to `tests/Unit/Actions/CreateDefaultPossessionTypesTest.php` that Cars carries `car` and Jewellery `gift`
- [X] T007 Run `php artisan migrate:fresh --seed` and `--filter PossessionType` to prove nothing broke

## Phase 3: User Story 1, understand the screen and add the first possession (P1) MVP

Independent test: spec US1. An empty vault shows "Nothing here yet" and "Add your first possession" leading to the form.

- [X] T008 [US1] Create `app/ViewModels/Vaults/PossessionsViewModel.php` (constructor `User $user`, `Vault $vault`): `vault()`, `isEmpty()` (no possession in the vault, any status), `newUrl()` to `possessions.new`, `locationsSettingsUrl()` to `settings.locations.index`; class docblock of one sentence
- [X] T009 [US1] Test it in `tests/Unit/ViewModels/Vaults/PossessionsViewModelTest.php`: empty true with none; false with one sold possession; another vault's possession does not count
- [X] T010 [US1] Make `PossessionController::index` in `app/Http/Controllers/App/Vaults/PossessionController.php` pass `'viewModel' => new PossessionsViewModel(user: ..., vault: $vault)` instead of `user` and `vault`
- [X] T011 [US1] Rewrite `resources/views/app/vaults/possessions/index.blade.php` (first line comment and `@var` of the view model): title "All possessions" in `x-shell`, the breadcrumb and `x-page-header` (description "Everything you own in this vault, where it is kept and what it is worth."); when `isEmpty()`, the intro half of the panel: a 48px tile with the `key` icon `aria-hidden`, `h2` "Nothing here yet", the paragraph "Write down the things you own: the car, the watch your grandmother left you, the camera on the top shelf. Each possession keeps where it is, what it is worth and who it came from, so you are not searching the day you need it.", `x-button` "Add your first possession" with the `plus` icon and `x-button.secondary` "Set up locations"; otherwise the current "Possessions are coming" panel and the header's "Add a possession" action. Read `x-panel`, `x-page-header`, `x-button` first (`.claude/rules/components.md`)
- [X] T012 [US1] Add to `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`: an empty vault answers with "All possessions", "Nothing here yet" and a link to `possessions.new`; a vault with a possession still shows "Possessions are coming"

## Phase 4: User Story 2, start from a type (P2)

Independent test: spec US2. Selecting the Cars tile opens the form with Cars chosen and its fields shown.

- [X] T013 [US2] Make `possessions.new` in `routes/web.php` `vaults/{vault}/possessions/new/{possessionType?}` with `->whereUuid('possessionType')`, same name (research R7)
- [X] T014 [US2] Give `PossessionController::new` an optional `?PossessionType $possessionType = null` passed to `PossessionFormViewModel` as `chosenType:`; in `app/ViewModels/Vaults/PossessionFormViewModel.php` add `private ?PossessionType $chosenType = null` and make `chosenType()` and `fieldsBlock()` use `old('type')` first, then the bound type's uuid
- [X] T015 [US2] Add to `PossessionFormViewModelTest.php`: the bound type is chosen and its fields block built; old input wins over it. Add to `PossessionControllerTest.php`: `new` with a type answers and shows its fields; a type of another account answers not found
- [X] T016 [US2] Give `PossessionsViewModel` `types()`: the account's types by name, at most eight, each `{name, colour, tint, icon, fields, newUrl}`; `tint` as the type picker computes it, `icon` `$type->icon ?? Section::DEFAULT_ICON`, `fields` the first two field names in form order (grouped by group position, then standalone) joined with `__(', ')`, or `__('No field yet')`; `newUrl` `route('possessions.new', [$vault, $type])`; `typesSettingsUrl()`
- [X] T017 [US2] Add to `PossessionsViewModelTest.php`: eight by name of nine; fields line with none, one, three fields; the stand-in glyph for a type without icon; another account's types left out
- [X] T018 [US2] Add the type half of the panel to `index.blade.php`: `h3` "Or start from a type" with "Each one comes with its own fields.", a two column grid of `<a href="{{ $type['newUrl'] }}" class="focus-ring ...">` tiles (32px tinted square with `x-icon` in the type colour `aria-hidden`, name, fields line); without types, "Your account has no possession type yet." and "Manage types" to `typesSettingsUrl()`; assert in the controller test a tile linking to `possessions.new` with the type

## Phase 5: User Story 4, give a type an icon (P2)

Independent test: spec US4. An icon set on the edit screen is stored, drawn, returned by API and assistant, and survives export.

- [X] T019 [US4] Add `?string $icon = null` to `app/Actions/CreatePossessionType.php` and `?string $icon` (required, research R5) to `app/Actions/UpdatePossessionType.php`: `''` to null in `sanitize()`, refuse outside `Section::ICONS` on `icon` "Pick an icon from the list.", write it; docblocks say so; update every caller (web, API, MCP, `ImportPossessionType`)
- [X] T020 [US4] Add to `tests/Unit/Actions/CreatePossessionTypeTest.php` and `UpdatePossessionTypeTest.php`: icon saved, `''` cleared, an unknown glyph refused
- [X] T021 [P] [US4] Read `icon` in `app/Helpers/PossessionTypeFileReader.php` beside `colour()`: absent or null gives null, a non string or unknown glyph adds "The icon is not one of the glyphs of the catalogue." (research R6); write it in `app/Actions/ImportPossessionType.php`; write `'icon' => $this->possessionType->icon` after `colour` in `app/Actions/ExportPossessionType.php` and its return shape; tests in `PossessionTypeFileReaderTest.php`, `ImportPossessionTypeTest.php`, `ExportPossessionTypeTest.php`
- [X] T022 [US4] Settings: `PossessionTypeController::update` rule `'icon' => ['nullable', 'string', Rule::in(Section::ICONS)]`, passed as `icon: $validated['icon'] ?? null`; `PossessionTypeViewModel` gains `icon()` and `glyphs()` (`list<{value, label, isCurrent}>`, labels as the mark picker names them); `PossessionTypesViewModel` rows gain `icon` with the stand-in fallback
- [X] T023 [US4] Edit `resources/views/app/settings/account/possession-types/edit.blade.php` (research R4): section "Name, colour and icon"; the name and colour forms gain `<input type="hidden" name="icon">`; a third `x-form` with hidden `name` and `colour`, `role="group"` labelled "Icon", one submit button per glyph (`name="icon"`, `aria-label`, `aria-pressed`) plus "No icon" (`value=""`). In `index.blade.php` draw the icon in the type colour where the dot is
- [X] T024 [US4] Tests: `tests/Unit/ViewModels/Settings/Account/PossessionTypes/PossessionTypeViewModelTest.php` (glyph list, current pressed) and `PossessionTypesViewModelTest.php` (fallback); settings `PossessionTypeControllerTest.php` (icon set, cleared, unknown refused)
- [X] T025 [P] [US4] API: `app/Http/Controllers/Api/PossessionTypes/PossessionTypeController.php` create and update rules `'icon' => ['nullable', 'string', Rule::in(Section::ICONS)]`, update passes `icon: $validated['icon'] ?? null` (full replace); `PossessionTypeResource` `icon` after `colour`; `PossessionResource` `possession_type.icon`; tests in `tests/Feature/Controllers/Api/PossessionTypes/PossessionTypeControllerTest.php` (422 outside, `PUT` without icon clears, structure) and the possessions API test structure
- [X] T026 [P] [US4] MCP: `app/Mcp/Tools/CreatePossessionType.php` `icon` rule and schema `enum(Section::ICONS)` "One of the glyphs of the catalogue. None when left out."; `UpdatePossessionType.php` `icon` `['sometimes', 'nullable', 'string', Rule::in(Section::ICONS)]`, kept when left out via `array_key_exists`, schema description says null clears it; descriptions of Get, List and Export tools mention the icon; tests in `tests/Unit/Mcp/Tools/CreatePossessionTypeTest.php`, `UpdatePossessionTypeTest.php`, `GetPossessionTypeTest.php`
- [X] T027 [P] [US4] Document `icon` in `resources/docs/api/26-possession-types.php` (request and resource) and `27-possessions.php` (nested type); run `ApiDocsReturnFieldsTest`

## Phase 6: User Story 3, learn how it works and reach locations (P3)

Independent test: spec US3. Three cards render; the locations line names and counts the account's own.

- [X] T028 [US3] Give `PossessionsViewModel` `tagsSettingsUrl()` to `settings.possessionTags.index`, `hasLocations()`, `locationsLine()`: top level locations by name, three named, joined `__(':names and :last', ...)` with `__(', ')` as `LifeEvent` does, a fourth and more as `trans_choice('[0,*]:names and :count more', n)`, then `trans_choice(':names, with :count place in all.|:names, with :count places in all.', total, ...)`; `''` without locations
- [X] T029 [US3] Add to `PossessionsViewModelTest.php`: line empty without locations; one, three and five top level locations with nested ones counted in the total; another account's left out
- [X] T030 [US3] Add "How it works" to `index.blade.php` (empty only): `h2` "How it works", "Three things to know. Only the name is required; the rest can come later."; three `<article>` cards with the design's illustrations in a block `aria-hidden="true"` (field "Name" holding "Peugeot 308 SW" and chips "Vehicle", "Instrument"; "Home", "Garage", "1 possession", "12 rue des Lilas, 44400 Rezé"; "Odette’s watch", `star-filled`, chips "Insured", "Heirloom"), labels "Step 1/2/3", titles "Name it and pick a type", "Say where it is kept", "Feature what matters, tag the rest", their texts from the design, step 3 linking "Tags" to `tagsSettingsUrl()`; then, with locations, the bordered line: `place` icon, "Your locations are ready." in ink, `locationsLine()`, "A possession can go straight into any of them.", "Manage locations" link
- [X] T031 [US3] Add to `PossessionControllerTest.php`: "How it works" and a link to `settings.possessionTags.index`; "Manage locations" present with a location, absent without

## Phase 7: Polish

- [X] T032 [P] Update `docs/portal/en/9-templates-and-customisation/12-possession-types.md` (the icon) and `16-possessions.md` (the empty screen, starting from a type) with the writer skill; add an entry to `docs/FEATURES.md`
- [X] T033 Run `php artisan monica:localize`, translate every reported string in every locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T034 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `bun run build` and the whole suite (`RouteProtectionTest` included)
- [ ] T035 Look at the empty screen and the type edit screen with the take-screenshot skill in light and dark and at phone width; walk quickstart "By hand" with the keyboard alone

## Dependencies

- Phase 1 and 2 before everything. US1 before US2 and US3 (they fill its panel and page).
- US4 needs only Phase 2; US2's tiles draw the stand-in glyph until US4 lands, so the two may run in either order.
- US3 is independent of US2 and US4 once US1 is done.
- Parallel: T002 with T003; T021, T025, T026, T027 with each other after T019; T032 with T033.

## Implementation strategy

MVP is Phases 1 to 3: the empty state with its explanation and the way to the form. Then US2 (type tiles), US4 (icons everywhere), US3 (how it works, locations), and polish.
