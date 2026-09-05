# Tasks: Section Icons and Colours

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md)

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means parallelisable, different file,
no dependency on unfinished work.

Tests are not optional here: Principle III makes them a delivery condition, so each story carries its
own. Per Principle XI this file is one line per task plus the dependency list, and every goal and
independent test criterion stays in [spec.md](spec.md) rather than being restated.

## Phase 1: Setup

No tasks. No dependency, folder, namespace or tooling change, so there is nothing to initialise.

## Phase 2: Foundational (blocks every story)

- [X] T001 [P] Add the 13 new glyph paths to the `$paths` map in `resources/views/components/icon.blade.php`
- [X] T002 [P] Add the nullable `colour` column, commented, to `database/migrations/2026_08_09_000002_create_sections_table.php`
- [X] T003 [P] Add the per theme foreground and background for each of the 8 hues to `app/Enums/VaultColourEnum.php`
- [X] T004 [P] Add `ICONS` (35 names), `DEFAULT_ICON` and `colour` to the fillable list and property docblock in `app/Models/Section.php`
- [X] T005 [P] Add `colour` to `database/factories/SectionFactory.php`
- [X] T006 Create `resources/views/components/section-mark.blade.php`, drawing the tile from an icon name and a colour
- [X] T007 Let the header mark take a tint instead of always `bg-card text-body` in `resources/views/components/panel.blade.php`
- [X] T008 [P] Create `tests/Unit/Enums/VaultColourEnumTest.php`, computing contrast for all 9 colours over canvas and card in both themes and asserting each clears AA
- [X] T009 [P] Create `tests/Feature/Views/Components/SectionMarkTest.php` for the tile, the neutral default and an unknown glyph name
- [X] T010 [P] Assert in `tests/Unit/Models/SectionTest.php` that every name in `Section::ICONS` is drawn by the icon component
- [X] T011 [P] Extend `tests/Feature/Views/Components/PanelTest.php` for the tinted mark

## Phase 3: User Story 1, mark a section as it is created (P1, MVP)

- [X] T012 [P] [US1] Extend `tests/Unit/Actions/CreateSectionTest.php` for a stored colour and for the defaults when neither is given
- [X] T013 [P] [US1] Extend `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateSectionControllerTest.php` so `create` carries a glyph and a colour and refuses either from outside the set
- [X] T014 [US1] Accept and store `colour` in `app/Actions/CreateSection.php`
- [X] T015 [US1] Validate glyph and colour membership in `TemplateSectionController::create` and pass both to the action, in `app/Http/Controllers/App/Settings/Account/Templates/TemplateSectionController.php`
- [X] T016 [US1] Expose the mark from `sections()` in `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php`
- [X] T017 [US1] Draw the mark in the section header of `resources/views/app/settings/account/templates/_section.blade.php`
- [X] T018 [US1] Add the trigger tile and the popover holding the glyph grid and swatch row to the add block in `resources/views/app/settings/account/templates/builder.blade.php`
- [X] T019 [US1] Read `colour` in `blockFor()` and pass the tint to the panel, in `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T020 [US1] Pass the mark to the panel in `app/ViewModels/Shared/SharedContactViewModel.php` and `resources/views/shared/contact.blade.php`
- [X] T021 [P] [US1] Extend `tests/Unit/ViewModels/Settings/Account/Templates/TemplateBuilderViewModelTest.php` for the mark
- [X] T022 [P] [US1] Extend `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php` for the mark and for the fallback when neither is set
- [X] T023 [P] [US1] Extend `tests/Unit/ViewModels/Shared/SharedContactViewModelTest.php` for the mark
- [X] T024 [P] [US1] Extend `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateBuilderControllerTest.php` so the picker renders, every option is keyboard reachable and the chosen one is announced

## Phase 4: User Story 2, change the mark on a section that exists (P2)

- [X] T025 [P] [US2] Extend `tests/Unit/Actions/UpdateSectionTest.php` for a changed and a cleared colour
- [X] T026 [P] [US2] Extend `TemplateSectionControllerTest.php` so `update` carries all three inputs, refuses a published template version, and returns not found across accounts and through the wrong template
- [X] T027 [P] [US2] Assert in `tests/Unit/Actions/UpdateSectionTest.php` that changing a mark creates no section version and alters no stored value
- [X] T028 [US2] Accept and store `colour` in `app/Actions/UpdateSection.php`
- [X] T029 [US2] Validate the three inputs, guard the freeze with an explicit `isEditable()` check, and call `UpdateTemplateVersionSection` then `UpdateSection`, in `TemplateSectionController::update`
- [X] T030 [US2] Expose the count of other templates carrying the section from `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php`
- [X] T031 [US2] Add the mark controls, the sentence saying the mark is shared while the heading is not, and the count, to the edit panel in `_section.blade.php`
- [X] T032 [US2] Narrow the version notice in `_section.blade.php` so it no longer claims the mark gives the section a new version
- [X] T033 [P] [US2] Extend `TemplateBuilderViewModelTest.php` for the count, including a section on one template only

## Phase 5: User Story 3, carry the mark when the section travels (P3)

- [X] T034 [P] [US3] Extend `tests/Unit/Helpers/StructureFileReaderTest.php` so a glyph or colour outside the set is refused with a message naming it
- [X] T035 [P] [US3] Extend `tests/Unit/Helpers/MarketplacePayloadTest.php` for the written colour
- [X] T036 [P] [US3] Extend `tests/Feature/Controllers/App/Settings/Account/Templates/SectionExportControllerTest.php` and `SectionImportControllerTest.php` for a marked section surviving a round trip, and for a file naming neither arriving on the defaults
- [X] T037 [US3] Write `colour` beside `icon` in `ofSectionVersion()` in `app/Helpers/MarketplacePayload.php`
- [X] T038 [US3] Read `colour` and validate membership of both it and the glyph in `section()` in `app/Helpers/StructureFileReader.php`
- [X] T039 [US3] Store `colour` on the section it creates in `app/Helpers/StructureWriter.php`

## Phase 6: Polish and cross-cutting

- [X] T040 Read the pair table from `VaultColourEnum` instead of holding a copy, in `resources/views/components/avatar-initials.blade.php`
- [X] T041 Confirm `tests/Feature/Views/Components/AvatarInitialsTest.php` still passes unchanged after T040
- [X] T042 Run `php artisan monica:localize` and translate everything it reports in all nine locales, per `.claude/rules/translations.md`
- [ ] T043 **Not done by me.** The structural half is covered by tests (every control a real button, `aria-pressed` on each option, an `sr-only` name on each, the tile `aria-hidden`, two sections told apart by heading and glyph with colour removed). The screen reader and 200% zoom walk needs a person, on: `resources/views/app/settings/account/templates/builder.blade.php`, `resources/views/app/vaults/contacts/_section.blade.php` and `resources/views/shared/contact.blade.php`
- [X] T044 Run `vendor/bin/pint --dirty --format agent` and PHPStan level 5, fixing rather than ignoring
- [ ] T045 **Partly done.** Green on SQLite (3936 tests). MySQL is running locally but rejects this user, and PostgreSQL is not installed, so neither was run. The new code contains no raw SQL, so nothing is driver-specific by inspection. The matrix in `.github/workflows/tests.yml:36` is reduced to `mysql`, so CI will not close this either

## Dependencies

- Phase 2 blocks Phases 3, 4 and 5. Phase 6 depends on every story being done, except T040 and T041 which need only T003.
- T006 needs T001 and T003. T007 needs T003. T008 needs T003. T009 needs T006. T010 needs T001 and T004. T011 needs T007.
- T014 needs T002 and T004. T015 needs T014. T016, T017 and T018 need T006. T019 and T020 need T007.
- T028 needs T002 and T004. T029 needs T028. T031 needs T006 and T030. T033 needs T030.
- T037, T038 and T039 need T002, T003 and T004.
- Within a story, its tests come before its implementation; the story's own tasks are otherwise ordered as listed.
- The three stories are independent of each other and may be taken in any order, or in parallel, once Phase 2 is done.
