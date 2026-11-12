# Tasks: Possession Tags

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: thirteen fixed concepts, Possession tag added after Possession, one sentence saying it belongs to the account, shared by every vault, and carries a name and a vault colour; Sync Impact Report; version 2.9.0, amended 2026-10-26 (research R1)
- [X] T002 [P] Name Possession tag in `spec/spec.md` beside Possession, and write `spec/models/064-possession-tag-in-progress.md` in the shape of `spec/models/059-possession-type-in-progress.md`, from data-model.md

## Phase 2: Foundational

- [X] T003 Add `PossessionTagCreation = 'possession_tag_created'`, `PossessionTagUpdate = 'possession_tag_updated'`, `PossessionTagDeletion = 'possession_tag_deleted'` after the possession cases of `app/Enums/UserActionEnum.php`, described "Added the possession tag :name", "Changed the possession tag :name", "Deleted the possession tag :name"
- [X] T004 Create `database/migrations/2026_10_26_000003_create_possession_tags_table.php`, `up()` only, every column with a `->comment()` as `2026_10_26_000000_create_possession_types_table.php`: `id`; `uuid` unique; `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()`; `name` string(50) "1 to 50 characters, unique in the account ignoring case"; `colour` string(7) "one of the vault colours, as a hexadecimal value"; timestamps; index `(account_id, name)`; no unique index, no soft deletes (research R3)
- [X] T005 Create `app/Models/PossessionTag.php` (`HasFactory`, `HasUuids`, `ResolvesModelInAccount`, `#[RouteKey('uuid')]`, `#[Fillable(['account_id', 'name', 'colour'])]`, full model docblock as `PossessionType`) with `account()`; add `possessionTags()` to `app/Models/Account.php` after `possessionTypes()`
- [X] T006 [P] Create `database/factories/PossessionTagFactory.php`: `account_id` from `Account::factory()`, a name from The Office ("Insured", "Dundie award", "To sell", "Fragile"), `colour` a random `VaultColourEnum` value

## Phase 3: User Story 1, add and list tags (P1) MVP

Independent test: spec US1. Add "Insured" in blue and "To sell" in green; open the screen.

- [X] T007 [US1] Create `app/Actions/CreatePossessionTag.php` in the shape of `CreatePossessionType`: inputs `user`, `name`, `colour` (`VaultColourEnum`, default `Blue`); `TextSanitizer::plainText()` the name; refuse an empty one with `ValidationException` on `name` "Write a name for this tag."; refuse a name any tag of the account already has, compared with `mb_strtolower()` as `CreateStructureVersionPage` compares labels, with `ValidationException` on `name` "You already have a tag with this name."; create; log `PossessionTagCreation` `{name}` on `low`
- [X] T008 [US1] Write `tests/Unit/Actions/CreatePossessionTagTest.php`: created with Blue when no colour is given and the log dispatched; an empty name after sanitising refused; "insured" refused beside "Insured"; the same name in another account accepted
- [X] T009 [US1] Create `app/ViewModels/Settings/Account/PossessionTags/PossessionTagsViewModel.php` per data-model.md: `user()`, `title()` "Possession tags", `description()` "Words you put on possessions to sort them across types and locations, like “insured” or “to sell”. Tags belong to your account, so every vault uses the same list.", `createAction()`, `colours()` (`list<{value, label, isDefault}>`, Blue default), `tags()` sorted with `LocaleCollation::sortByCollator` (`list<{uuid, name, colour, colourName, updateAction, destroyAction}>`), `countLine()` `trans_choice(':count tag|:count tags', n)` and `''` at zero, `isEmpty()`
- [X] T010 [US1] Write `tests/Unit/ViewModels/Settings/Account/PossessionTags/PossessionTagsViewModelTest.php`: sorted by name, another account's tags left out, the count line at one and at three, empty at zero, eight colours with Blue as default
- [X] T011 [US1] Create `app/Http/Controllers/App/Settings/Account/PossessionTags/PossessionTagController.php` with `index` and `create` (inline validation: `name` `['required', 'string', 'max:50']`, `colour` `['required', Rule::enum(VaultColourEnum::class)]`; redirect to the index with `status` "“:name” is added."); add `settings.possessionTags.index` and `settings.possessionTags.create` to `routes/web.php` after the possession type routes, one per line
- [X] T012 [US1] Create `resources/views/app/settings/account/possession-tags/_colours.blade.php`: a `<fieldset>` with `<legend>` "Colour" and eight `<input type="radio" name="colour">` drawn as swatches, each labelled with its colour name, the checked one given by a prop; opening comment and `@var` block per `.claude/rules/code-style.md` (research R6)
- [X] T013 [US1] Create `resources/views/app/settings/account/possession-tags/index.blade.php` in `x-settings.page` (`current="possession-tags"`) with `x-page-header`; a section "Add a tag" holding an `x-panel` with the form (`x-input` `name` `maxlength="50"` placeholder "Needs repair", `_colours`, "Add tag" `x-button` with the `plus` icon) and a footer "Preview" showing the tag with the typed name (or "Your tag") and the chosen colour through Alpine, as `resources/views/app/vaults/form.blade.php` previews its colour, inside `aria-live="polite"`; a section "Your tags" with the count line and `<div id="possession-tags">` holding a `<ul aria-label="Your tags">` of rows, or `x-empty-state` "No tags yet" with the `hash` icon and "Tags you add here will be put on possessions, to sort them across types and locations."; every form `x-target="possession-tags"` so the list re-renders in place. Read each component before calling it (`.claude/rules/components.md`)
- [X] T014 [US1] Create `resources/views/app/settings/account/possession-tags/_row.blade.php` for viewing: the swatch with its colour name in `sr-only`, the name; the edit and delete controls come in US2 and US3
- [X] T015 [US1] Add `['key' => 'possession-tags', 'label' => __('Possession tags'), 'icon' => 'hash', 'url' => route('settings.possessionTags.index')]` after Possession types in `resources/views/components/settings/sidebar.blade.php` (research R8)
- [X] T016 [US1] Write `tests/Feature/Controllers/App/Settings/Account/PossessionTags/PossessionTagControllerTest.php`: the index answers and shows "Possession tags"; create redirects with its flash; a name of 51 characters and a colour outside the palette refused; the duplicate refusal reaches the form as an error on `name`

## Phase 4: User Story 2, rename and recolour a tag (P1)

Independent test: spec US2. Rename "Paperwork filed" to "Paperwork done" and make it teal. Depends on US1.

- [X] T017 [US2] Create `app/Actions/UpdatePossessionTag.php` in the shape of `UpdatePossessionType`: inputs `user`, `possessionTag`, `name`, `colour`; refuse a tag of another account with `ModelNotFoundException`; the same sanitising and refusals as T007, the duplicate check leaving the tag itself out so a change of case of its own name passes; save; log `PossessionTagUpdate` `{name}`
- [X] T018 [US2] Write `tests/Unit/Actions/UpdatePossessionTagTest.php`: renamed and recoloured with the log; "INSURED" accepted for "Insured"; another tag's name refused ignoring case; empty refused; another account's tag answers `ModelNotFoundException`
- [X] T019 [US2] Add `update` to `PossessionTagController` (same validation as T011; redirect with `status` "Saved.") and `settings.possessionTags.update` (`PUT`, `whereUuid('possessionTag')`) to `routes/web.php`
- [X] T020 [US2] In `_row.blade.php`, add "Edit" (`x-button` ghost, small, `aria-label` "Edit :name") turning the row into a form with `x-input` `name`, `_colours` with the tag's colour checked, "Cancel" (secondary) and "Save changes" (primary), in the shape of the rename of `resources/views/app/settings/account/relationship-types/_category.blade.php`: `x-data="{ editing: … }"` opened again when the server returns an error on `name` for this tag, Escape and "Cancel" close it, focus goes to the name when it opens and back to "Edit" when it closes
- [X] T021 [US2] Extend `PossessionTagControllerTest.php`: update redirects with its flash and saves; the validation of `name` and `colour` refused

## Phase 5: User Story 3, delete a tag (P1)

Independent test: spec US3. Delete "Needs repair". Depends on US1.

- [X] T022 [US3] Create `app/Actions/DestroyPossessionTag.php` in the shape of `DestroyPossessionType` without the cascade: refuse another account's tag with `ModelNotFoundException`; delete; log `PossessionTagDeletion` `{name}`
- [X] T023 [US3] Write `tests/Unit/Actions/DestroyPossessionTagTest.php`: deleted with the log; another account's tag refused
- [X] T024 [US3] Add `destroy` to `PossessionTagController` (redirect with `status` "“:name” is deleted.") and `settings.possessionTags.destroy` (`DELETE`, `whereUuid('possessionTag')`) to `routes/web.php`
- [X] T025 [US3] In `_row.blade.php`, add "Delete" (ghost `x-button`, danger tone, `aria-label` "Delete :name") opening `x-modal.warning`, titled "Delete the tag “:name”?", confirming "Delete tag", saying "This cannot be undone.", targeting `possession-tags` (research R7)
- [X] T026 [US3] Extend `PossessionTagControllerTest.php`: destroy redirects with its flash and the tag is gone

## Phase 6: User Story 4, through the API and an assistant (P2)

Independent test: spec US4. Depends on the three actions.

- [X] T027 [US4] Create `app/Http/Resources/PossessionTagResource.php`: `type` `possession_tag`, `id`, `attributes {name, colour, created_at, updated_at}`, `links {self: api.v1.possessionTags.show, web: settings.possessionTags.index}`
- [X] T028 [US4] Create `app/Http/Controllers/Api/PossessionTags/PossessionTagController.php` (`index`, `show`, `create`, `update`, `destroy`) in the shape of `Api\PossessionTypes\PossessionTypeController`: `index` sorted with `LocaleCollation` and paginated in memory with `meta {page, per_page, total}`; tags looked up in the account by uuid (`firstOrFail`, 404); `create` takes `name` `['required', 'string', 'max:50']` and `colour` `['nullable', Rule::enum(VaultColourEnum::class)]` (Blue when left out), answers 201; `update` takes both required; `destroy` answers 204
- [X] T029 [US4] Add the five routes of the contract to `routes/api.php` after `possession-types`, `{possessionTag}` constrained to `[0-9a-fA-F-]{36}`, names `api.v1.possessionTags`, `.show`, `.create`, `.update`, `.destroy`
- [X] T030 [US4] Write `tests/Feature/Controllers/Api/PossessionTags/PossessionTagControllerTest.php` with a shared `$jsonStructure`: each route answers; another account's tags left out of the list; another account's tag 404; 422 on `name` for a duplicate; 422 on `colour`
- [X] T031 [US4] Add `possessionTag(Request $request, string $address): PossessionTag` to `app/Mcp/Tools/MirrorsAnApiCapability.php` answering "That possession tag does not exist."
- [X] T032 [US4] Create `ListPossessionTags`, `GetPossessionTag` (both `#[IsReadOnly]`), `CreatePossessionTag`, `UpdatePossessionTag` (keeps what is left out), `DeletePossessionTag` (`#[IsDestructive]`) in `app/Mcp/Tools`, in the shape of the possession type tools, each mirroring its route and calling the same action; register them in `app/Mcp/Servers/MonicaServer.php` after the possession tools
- [X] T033 [US4] Write one test per tool under `tests/Unit/Mcp/Tools`, in the shape of the possession type tool tests
- [X] T034 [US4] Add "Possession tags" and "Possession tags, changing" groups to `app/ViewModels/Docs/McpDocsViewModel.php`, and update the counts in `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`
- [X] T035 [P] [US4] Write `resources/docs/api/28-possession-tags.php` in the shape of `26-possession-types.php`, per the `api-docs-writer` skill, with examples from The Office; update the counts in `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and any list in `tests/Feature/Controllers/App/Docs` that names every section

## Phase 7: Polish

- [X] T036 [P] Write `docs/portal/en/9-templates-and-customisation/14-possession-tags.md` per the `writer` skill, after checking how the portal orders that folder; no dash used as punctuation
- [X] T037 [P] Add a line to `docs/FEATURES.md` per the `feature-documentator` skill
- [X] T038 Run `php artisan monica:localize`; translate every new string, French first under `.claude/rules/translations.md` and its glossary, then every other locale; `--check` passes
- [X] T039 Run Pint, PHPStan (`--memory-limit=2G`) and the whole suite (`php -d memory_limit=4G vendor/bin/phpunit --no-progress`)
- [ ] T040 Look at the screen in light and dark, at phone width and zoomed to 200%, with the keyboard alone, per quickstart.md "By hand"

## Dependencies

- T001 to T006 before every story. US1 before US2 and US3 (they extend its controller, routes and row). US4 after the three actions exist (T007, T017, T022).
- T038 after every string exists. T039 and T040 last.

## Parallel

- T002 and T006 alongside the rest of their phase.
- US2 and US3 once US1 is done, except where both edit `_row.blade.php`, `PossessionTagController.php` and `routes/web.php`: do those in sequence.
- T035, T036 and T037 alongside T031 to T034.

## Strategy

MVP is US1: the screen lists the account's tags and adds one. US2 and US3 complete the management
the design shows. US4 opens it to the API and the assistant and documents it, which principle X
requires before the feature is done.
