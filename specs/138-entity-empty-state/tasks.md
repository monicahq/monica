# Tasks: Empty list of an invented type

**Input**: `specs/138-entity-empty-state/` (plan.md, research.md, quickstart.md)

**Tests**: required by the constitution (principle III), one layer each per `.claude/rules/testing.md`.

## Phase 1: Setup

None. No dependency, migration or config.

## Phase 2: Foundational

None. Both stories change the same view model and view.

## Phase 3: User Story 1, understand an empty list and add the first record (P1)

**Independent test**: an empty Animals list shows three tinted placeholder rows, the title, the sentence and "Add your first Animal" leading to the add form.

- [X] T001 [US1] In `app/ViewModels/Vaults/EntitiesViewModel.php`, change `emptyTitle()` to `__('Your :name will line up here', ...)` with `name_plural` (research R2)
- [X] T002 [US1] In `app/ViewModels/Vaults/EntitiesViewModel.php`, add `emptyDescription()`: trimmed type description when not empty, else `__('The :name you add to this vault will appear here, one row each.', ...)` with `name_plural` (R1)
- [X] T003 [US1] In `app/ViewModels/Vaults/EntitiesViewModel.php`, add `firstAddLabel()` returning `__('Add your first :name', ...)` with `name_singular`, and `icon()` (falls back to `EntityType::DEFAULT_ICON`) and `colour()` (R2, R4)
- [X] T004 [US1] In `resources/views/app/vaults/entities/index.blade.php`, replace the `empty` slot: three `aria-hidden` rows (opacity 90/55/25, `x-entity-icon` on a `color-mix(in srgb, colour 14%, var(--canvas))` round mark, two `bg-hairline` bars), then `x-empty-state` with the title, `emptyDescription()` as slot, and a secondary `x-button` in its `action` slot; drop `href` and `link-label` (R3, R4, R5)
- [X] T005 [P] [US1] In `tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php`, test the title and button wording, the description used when set, the generic sentence when null or blank, and the default icon fallback
- [X] T006 [P] [US1] In `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`, test the empty screen renders the title, the sentence and "Add your first Animal" linking to `entities.new`, and that the placeholder block is `aria-hidden`

## Phase 4: User Story 2, an empty list where nothing may be added (P2)

**Independent test**: an empty list whose template cannot be filled in shows the reason, the rows and the title, and the panel button is refused and described by the reason.

- [X] T007 [US2] In `resources/views/app/vaults/entities/index.blade.php`, give the panel button the header button's refusal: no `href` when `addRefusal()` is not null, `aria-disabled="true"`, `aria-describedby="entity-add-refusal"` (R6)
- [X] T008 [US2] In `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`, test that an empty list whose template requires a part that cannot be filled in renders "Add your first Animal" without a link to `entities.new` and with `aria-describedby="entity-add-refusal"`

## Phase 5: Polish

- [X] T009 Run `php artisan monica:localize`, translate the three new strings in every `lang/*.json` with Sonnet subagents, then `php artisan monica:localize --check`
- [X] T010 [P] Add a changelog entry under `changelog/` per the changelog skill
- [X] T011 Run `vendor/bin/pint --dirty --format agent`, PHPStan, and `composer test:parallel`
- [X] T012 Walk `quickstart.md` by hand (screenshot light, dark, 320px) and run the `definition-of-done` skill checks

## Dependencies

- T001 to T003 before T004; T004 before T005, T006, T007; T007 before T008.
- US2 depends on US1 (same button). Polish after both.
- Parallel: T005 with T006; T010 with T009.

## Implementation strategy

US1 is the MVP and ships the design. US2 is a few attributes on the same button and should ship with it.
