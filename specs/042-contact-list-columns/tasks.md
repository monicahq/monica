---
description: "Task list for the contact list columns"
---

# Tasks: Contact list columns

**Input**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)

**Tests**: required by principle III. Test data uses The Office. Follow `.claude/rules/testing.md`.

## Phase 1: Setup

- [X] T001 Read `.claude/rules/components.md`, `.claude/rules/testing.md`, `.claude/skills/{actions,controllers,models,view-models,views,routes,migrations,api-writer,api-docs-writer,translations}/SKILL.md` before writing any file

## Phase 2: Foundational

- [X] T002 Generate `database/migrations/2026_09_22_000000_create_contact_list_columns_table.php` with `php artisan make:migration`, then rename it. Columns per data-model.md: `source` string(32), `section_id` nullable, `field_key` string(64) nullable, `heading` string(255) nullable, `position` unsigned int, `is_visible` bool, unique `vault_id, position`. FKs at the bottom, cascading on vault and section, with no `constrained()`, only `up()`, and a `->comment()` on every column
- [X] T003 [P] Create `app/Enums/ContactListColumnSourceEnum.php` with the eight cases and values from data-model.md. Add `defaultHeading()` (translated), `sort(): ?ContactSortEnum`, and `static defaults()`, which returns email, phone and last-spoke shown and template hidden
- [X] T004 [P] Create `app/Models/ContactListColumn.php` with `#[Fillable]`, the `source` enum cast, the `is_visible` bool cast, `vault()`, `section()`, and a full model docblock. Create `database/factories/ContactListColumnFactory.php` with a `field()` state
- [X] T005 Add `contactListColumns(): HasMany`, ordered by `position`, to `app/Models/Vault.php`
- [X] T006 [P] Test the enum in `tests/Unit/Enums/ContactListColumnSourceEnumTest.php`: the defaults, and which cases sort

## Phase 3: User Story 1, the People list is a table (P1)

**Goal**: People shows the vault's columns, favourites first in Everybody, with sortable headings.
**Independent test**: a vault with no saved columns shows Name, Email, Phone number and Last spoke, with right values for contacts on two templates.

- [X] T007 [US1] Create `app/ViewModels/Vaults/ContactListCells.php`. It takes the user, the vault, the visible columns and a page of contacts. It resolves the field ids once: email and phone by type, birthday by the `birthday` key, and a field column by `section_id` and `field_key` across every section version. It loads values in one query (`is_detached = false`, ordered by field position then row position) and formats them with `FieldValueFormatter`. `nickname`, `template`, `last-spoke` and `added` come from the contact (R8)
- [X] T008 [US1] Update `app/ViewModels/Vaults/ContactsViewModel.php`:
  - `columns()` returns the saved columns, or `ContactListColumnSourceEnum::defaults()`, minus hidden and unsupplied ones;
  - `headings()` gives each heading's label, sort link and `aria-sort` value (R11);
  - `groups()` returns the Favourites and Everybody else groups, each with its count and carried across pages (R10), and rows carrying cells from `ContactListCells`;
  - remove the letter headings, `sorts()` and `directions()`
- [X] T009 [US1] Order Everybody by `is_favourite` descending, before `sortedBy`, in `ContactsViewModel::ordered()`
- [X] T010 [US1] Rewrite `resources/views/app/vaults/contacts/index.blade.php` to the design:
  - the chips, the Customise view icon button with `x-tooltip` linking to `contactListColumns.edit`, and the search;
  - drop the sort form;
  - a search placeholder that states what search matches (FR-007)
- [X] T011 [US1] Rewrite `resources/views/app/vaults/contacts/_groups.blade.php` as table body groups:
  - a native `<table>` with a screen-reader `<caption>`, `th scope="col"` and `aria-sort`;
  - a name link stretched over the row, avatar and star;
  - a dash for no value, "Not yet" for last spoke;
  - a "+N" button with `aria-expanded` revealing the rest in place (contracts/web.md)
- [X] T012 [US1] In `app/Http/Controllers/Api/ContactController.php` index, add the `columns` array per contact through `ContactListCells`, as in contracts/api.md
- [X] T013 [P] [US1] Test in `tests/Unit/ViewModels/Vaults/ContactListCellsTest.php` what quickstart.md lists for it
- [X] T014 [P] [US1] Extend `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php`: the defaults, favourites first with counts, no repeated heading mid-group, a hidden column left out. Delete the letter heading and sort select tests
- [X] T015 [US1] Update `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` for the table, a heading link, and the `aria-sort` value
- [X] T016 [US1] Extend `tests/Feature/Controllers/Api/ContactControllerTest.php` with the `columns` structure

**Checkpoint**: People works with the defaults. Run the full suite, since the old markup is gone.

## Phase 4: User Story 2, choose the columns (P2)

**Goal**: the customise screen and its API. **Independent test**: add Company, rename it Employer, put it second, save, and see it on People.

- [X] T017 [US2] Create `app/Actions/UpdateContactListColumns.php`. It replaces the vault's set in a transaction and enforces every rule under Validation in data-model.md, quoted: "No source is repeated, and no `section_id` and `field_key` pair is repeated", "A column already saved that no longer qualifies may be saved again unchanged", and a heading "at most 255 characters. Empty becomes null". It dispatches one `LogUserAction` (add the `UserActionEnum` case)
- [X] T018 [US2] Create `app/ViewModels/Vaults/ContactListColumnsViewModel.php`:
  - the saved or default rows, each with its source label, note and `isSupplied` (R9);
  - the add menu's two groups with template badges, with the exclusions of R5, R6 and R7;
  - the templates line, the default set as JSON for Reset, and the form and cancel URLs
- [X] T019 [US2] Create `app/Http/Controllers/App/Vaults/ContactListColumnController.php` with `edit` and `update`: inline validation per contracts/web.md, `abort_unless` on the vault, a redirect with the flash, and `withErrors()` on a refusal
- [X] T020 [US2] Add `contactListColumns.edit` and `contactListColumns.update` to `routes/web.php`, per contracts/web.md
- [X] T021 [US2] Create `resources/js/contact-list-columns.js` and register it in `resources/js/app.js`:
  - show or hide, rename, remove, add from a `<template>`, and Reset from the default JSON;
  - reordering by drag, and by ArrowUp and ArrowDown on the grip, announced in an `aria-live` region;
  - the unsaved-changes flag;
  - the root found through `el.closest()` (R13)
- [X] T022 [US2] Create `resources/views/app/vaults/contact-list-columns/edit.blade.php`:
  - `x-shell` with the vault breadcrumb;
  - the Columns section as `role="list"`, the lock on Name, and the checkbox, heading input and data source line;
  - `x-actions-menu` for Add a column (R14, and check that an item may be a `<button>`);
  - a footer with Reset to defaults, Unsaved changes, Cancel and Save changes;
  - no preview
- [X] T023 [US2] Create `app/Http/Controllers/Api/ContactListColumnController.php` (`show`, `update`) and `app/Http/Resources/ContactListColumnResource.php`, and add both routes to `routes/api.php`, per contracts/api.md, naming a section by its uuid
- [X] T024 [P] [US2] Test in `tests/Unit/Actions/UpdateContactListColumnsTest.php` what quickstart.md lists for it
- [X] T025 [P] [US2] Test in `tests/Unit/ViewModels/Vaults/ContactListColumnsViewModelTest.php` what quickstart.md lists for it
- [X] T026 [P] [US2] Test in `tests/Feature/Controllers/App/Vaults/ContactListColumnControllerTest.php`: the screen renders, update redirects with the flash, one validation refusal, the wrong vault gives 404
- [X] T027 [P] [US2] Test in `tests/Feature/Controllers/Api/ContactListColumnControllerTest.php`: the `GET` and `PUT` structure, and a 422
- [X] T028 [US2] Document both endpoints and the `columns` array in `resources/docs/api/06-contacts.php` (api-docs-writer skill)

## Phase 5: Polish

- [X] T029 Run `php artisan monica:localize`, translate every reported string in every locale in British-spelled English source copy (R17), then run `php artisan monica:localize --check`
- [X] T030 Run `php artisan test --compact` over the whole suite, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T031 Take screenshots of both screens with Iris (take-screenshot skill), at desktop and phone width, and walk both with the keyboard alone

## Dependencies

Phase 2 blocks everything. US1 needs T002 to T005. US2 needs Phase 2 and `ContactListCells` (T007) for nothing but the API list, so US2 can start once Phase 2 is done. T010 and T011 need T008. T022 needs T018 and T021. Polish comes last.

**Parallel**: T003, T004 and T006. Then T013 and T014. Then T024 to T027.

**MVP**: Phases 1 to 3. People is the new table with the default columns. The customise screen follows as its own increment.

## What the implementation changed about the plan

- `ContactListCells` and the catalogue (`ContactListCatalogue`) live in `app/Helpers`, not in `app/ViewModels`. The API contact list reads them too, and a view model may only be consumed by Blade.
- The web form and the API both name a section by its uuid, so the action has one lookup to scope.
- `x-actions-menu` gained `text`, `align` and a `columns` width. Its defaults leave its two other screens as they were.
- A column already in the list is hidden from the add menu rather than disabling the trigger, since an Alpine binding cannot go on a component tag.
- An empty save puts the defaults back: a vault with no rows shows the defaults. Name alone is every column sent hidden.
- `ApiDocsCodeSamples` printed neither an array nor null in a sample body. It does now. As a side effect, a body parameter documented as null reads `null` rather than `''` in the JavaScript sample.
- T031 is open: screenshots need `MONICA_SCREENSHOT_LINK_ENABLED` and a local `migrate`, both left to the owner.
- Review pass: shared fields are decided by template id rather than name, the customise screen shows every error and refills the editor from what was sent after a refusal, a row is draggable only while its grip is held, and an emptied group hides from the add menu.
