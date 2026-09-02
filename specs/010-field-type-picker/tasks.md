---
description: "Task list for the field type picker"
---

# Tasks: Field Type Picker

**Input**: `/specs/010-field-type-picker/` (spec.md, plan.md, research.md)

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it touches no file another unfinished task touches.

**Tests**: included, and not optional here. Principle III makes a test part of the change.

**Trimmed**: Principle XI caps this file at one line per task plus the phase headings and the dependency list, so the template's parallel examples, strategy and notes sections are left out. The dependency list carries what they said.

## Phase 1: Setup

- [X] T001 Run `php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php` and record the green baseline before touching the screen
- [X] T002 Build the assets with `bun run build` so Tailwind picks up the new utilities in `resources/views/app/settings/account/templates/field-type.blade.php`

---

## Phase 2: Foundational

**Purpose**: the marks and the catalogue data every story below reads. No story can render without them.

- [X] T003 Draw the twelve glyphs (`text`, `text-lines`, `hash`, `radio`, `tag`, `calendar-repeat`, `clock`, `layers`, `coin`, `divider`, `heading`, `swatch`) on the 16px grid with the 1.4 stroke in `resources/views/components/icon.blade.php`
- [X] T004 [P] Assert each of the twelve new names draws its own path, and that an unknown name still falls back, in `tests/Feature/Views/Components/IconTest.php`
- [X] T005 Add a `mark` key to every entry of `catalogue()` in `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, reusing the nineteen existing glyphs and the twelve new ones
- [X] T006 Add a `preview` shape to every entry of `catalogue()` in `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, one of the nine kinds named in research.md, carrying only what the illustration draws
- [X] T007 Add `searchIndex()`, `countPhrases()` and `sentences()` to `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, the count phrases written with `trans_choice()` one per possible count
- [X] T008 [P] Cover `mark`, `preview`, `searchIndex()`, `countPhrases()` and `sentences()`, including that every enum case has a mark and a shape, in `tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php`
- [X] T009 Rewrite `resources/views/app/settings/account/templates/field-type.blade.php` down to its skeleton: the `lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]` two columns, the Alpine root holding `selected`, `peek` and `query`, the label input untouched, and the families as compact single line cards carrying the mark and the label
- [X] T010 Take the technical key off the cards and keep the "Later" badge on the types the enum does not ship, in `resources/views/app/settings/account/templates/field-type.blade.php`

**Checkpoint**: the screen renders as a compact catalogue, still submits, still refuses a type the enum does not ship.

---

## Phase 3: User Story 1 - See a type before committing to it (P1) 🎯 MVP

**Goal**: a panel that draws the field the previewed type produces.

**Independent Test**: select each type in turn and confirm the panel changes to a rendering that matches it, with the filter never built.

- [X] T011 [US1] Create `resources/views/app/settings/account/templates/_field-type-preview.blade.php`, drawing one illustration from a preview shape across the nine kinds (input, textarea, toggle, dropdown, list, pills, upload, rows, layout)
- [X] T012 [US1] Render the panel in `resources/views/app/settings/account/templates/field-type.blade.php`: mark, name, key, "Later" badge, description, the "On the record" block including the partial, and the example line, one per type with `x-show`
- [X] T013 [US1] Make the panel `lg:sticky lg:top-0` as `resources/views/app/settings/account/templates/_inspector.blade.php` does, and put it under the catalogue below that breakpoint
- [X] T014 [US1] Wire the peek in `resources/views/app/settings/account/templates/field-type.blade.php`: pointer enter and focus set `peek`, pointer leave and blur clear it, the panel shows `peek || selected`, and the radio alone moves `selected`
- [X] T015 [US1] Show the typed label as the field label in the panel, falling back to the type name, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T016 [US1] Give the panel a resting state naming what it is for while nothing is selected, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T017 [US1] Add the screen reader only `aria-live="polite"` region reading the previewed type's sentence in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T018 [US1] Write the footer sentence naming the section and the chosen type, and disable the submit until a selectable type is chosen, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T019 [US1] Assert the panel, every type's illustration, the resting state, the live region and the footer sentence in `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php`

**Checkpoint**: somebody can read what every type produces without leaving the screen.

---

## Phase 4: User Story 2 - Find the right type among all of them (P2)

**Goal**: a filter, a count, and an empty state.

**Independent Test**: type into the filter and confirm only matching types remain, that the count follows, and that clearing it restores every family.

- [X] T020 [US2] Add the labelled search input bound to `query` above the catalogue in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T021 [US2] Show and hide each card, and each family heading, from the search index with `x-show` in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T022 [US2] Show the count of types showing out of the total, read from `countPhrases()` by index, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T023 [US2] Say nothing matches and how to clear the filter when the count reaches zero, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T024 [US2] Keep the selection, the panel and the footer intact while the filter hides the selected card, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T025 [US2] Assert the search input, the resting count phrase, the search index reaching the page and the empty state copy in `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php`

**Checkpoint**: any of the thirty one types is reachable in four keystrokes.

---

## Phase 5: User Story 3 - Add a field that offers choices in one pass (P3)

**Goal**: the value list question survives the rewrite.

**Independent Test**: select "Dropdown", pick a value list, submit, and confirm the field is created with that list.

- [X] T026 [US3] Carry the "Where do the choices come from?" section over, shown when the selected type is one of `typesNeedingAValueList()`, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T027 [US3] Keep the notice that says where to make a value list when the account has none, in `resources/views/app/settings/account/templates/field-type.blade.php`
- [X] T028 [US3] Confirm the four existing choice field and validation tests still pass unchanged, and assert the section appears for each choice type, in `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php`

**Checkpoint**: a dropdown, a multiple choice and a radio field are each created in one submission.

---

## Phase 6: Polish and Cross-Cutting

- [X] T029 Return the typed label and the chosen type to the screen after a validation failure, and assert it, in `resources/views/app/settings/account/templates/field-type.blade.php` and `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php`
- [X] T030 Run `php artisan monica:localize` and translate every reported string in the nine locales under `lang/`, following the typography rules of each
- [ ] T031 Walk the screen with the keyboard alone and with a screen reader, at 200% zoom and at phone width, and fix what that finds in `resources/views/app/settings/account/templates/field-type.blade.php`

**T031 stands open on purpose.** What was verified from the rendered markup: the catalogue is one `fieldset` with a screen reader only `legend`, all 21 shipped types are one radio group, all 10 unshipped ones are focusable and carry `aria-disabled`, every card and the filter draw a focus ring, the 31 illustrations are `aria-hidden`, the previewed type is announced through one polite live region, the "Later" state is a word rather than a shade, the columns collapse below `lg` and the cards reflow with `auto-fill`. Since then a real browser at 1440x900 has confirmed the layout: the window cannot scroll, and the action bar is flush at the foot of the window at rest, halfway down and at the end of the scroll. What nobody has done is put a real screen reader on it, or open it at 200% zoom on a real phone. That needs a person.
- [X] T032 Run `php artisan monica:localize --check`, `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse` and the full suite, and fix what they report

---

## Dependencies and Execution Order

- T001 and T002 are independent of everything and of each other.
- T003 blocks T004 and T005. T005, T006 and T007 all edit `FieldCatalogueViewModel.php`, so they run in that order, and together they block T008.
- T009 depends on T005 and T006, and blocks every task in Phases 3, 4 and 5, all of which edit the same Blade file and therefore run one at a time. T010 follows T009.
- Phase 3 depends on T006 and T007. Phase 4 depends on T007. Phase 5 depends on nothing but T009. T024 depends on T021 and on Phase 3 being in place.
- Phase 6 depends on every phase above. T030 depends on all copy being written, T032 runs last.
- Only T004 and T008 carry `[P]`: they are the two test files nothing else touches. The rest of the work funnels through one Blade file and one view model.
- MVP is Phases 1, 2 and 3. The screen is worth shipping at that point: the panel is the reason the redesign exists, and the catalogue reads better compact even with no filter.
