# Tasks: Edit a Possession

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

None: no table, no enum, no flag.

## Phase 2: Foundational

- [X] T001 Create `app/Helpers/PossessionFieldValues.php` (research R2): `fields(PossessionType|int $type, array $values): Collection<string, PossessionField>` returns the type's fields named in `$values` keyed by uuid, or throws `ValidationException` keyed `values.<uuid>` with the wordings `CreatePossession::validateValues()` uses today ("This field is not a field of the type of this possession." and `PossessionFieldValueFormat::refusal()`), skipping empty values; `write(Possession $possession, Collection $fields, array $values): void` sets each value with `updateOrCreate` or deletes it when empty, then `unsetRelation('values')`, exactly as `UpdatePossessionFieldValues::write()` does without its transaction
- [X] T002 (No file written: `UpdatePossessionFieldValuesTest` and `CreatePossessionTest` already prove every rule the helper holds, and a rule is tested once.) Test `tests/Unit/Helpers/PossessionFieldValuesTest.php`: fields returned keyed by uuid, a field of another type refused, a malformed value refused with its field's wording, empty values skipped by the check; write creates, replaces and deletes
- [X] T003 Move `app/Actions/CreatePossession.php` (validation only; its insert stays) and `app/Actions/UpdatePossessionFieldValues.php` (validation and write, inside its own `DB::transaction`) onto the helper; `tests/Unit/Actions/CreatePossessionTest.php` and `tests/Unit/Actions/UpdatePossessionFieldValuesTest.php` still pass unchanged
- [X] T004 `app/Actions/UpdatePossession.php` (research R1): add `?array $values = null` as the last constructor parameter (docblock `@param array<string, mixed>|null $values keyed by the uuid of the field; null leaves the values as they are`); in `validate()` call `PossessionFieldValues::fields()` with the possession's type when not null, before anything is written; in `update()` call `PossessionFieldValues::write()` inside the existing transaction; one journal entry as today; update the class docblock
- [X] T005 Tests in `tests/Unit/Actions/UpdatePossessionTest.php`: values written and an empty one cleared; a refused value leaves the name and every value unchanged and dispatches no `LogUserAction`; `values: null` leaves the stored values; a field of another type refused

## Phase 3: User Story 1, change what is known of a possession (P1) MVP

Independent test: open Edit from the possession page, change the name, save; the page shows the new name and the confirmation.

- [X] T006 [US1] Routes in `routes/web.php` beside `possessions.show`: `GET vaults/{vault}/possessions/{possession}/edit` → `edit`, `possessions.edit`; `PUT vaults/{vault}/possessions/{possession}` → `update`, `possessions.update`; both `->whereUuid('possession')`
- [X] T007 [US1] `app/ViewModels/Vaults/PossessionFormViewModel.php` (research R4): constructor gains `?Possession $possession = null`; `isEditing()`; `formAction()` and `formMethod()` (`post` to `possessions.create`, `put` to `possessions.update`) replacing `createAction()` and its callers; `cancelUrl()` to `possessions.show` when editing; `name()`, `description()`, `chosenLocation()` (location uuid), `declaredValue()`, `currency()`, `chosenTags()` (tag names), `isFeatured()` fall back from `old()` to the possession; `chosenType()` is the possession's type uuid when editing; `fieldsBlock()` passes the stored values keyed by field uuid when no old input; `title()` ("Add a possession" or "Edit :name"); update the class docblock
- [X] T008 [US1] Test `tests/Unit/ViewModels/Vaults/PossessionFormViewModelTest.php`: editing fills every value from the possession, old input wins over it, the update action and method, cancel goes to the possession page; the add mode unchanged
- [X] T009 [US1] `app/Http/Controllers/App/Vaults/PossessionController.php`: `edit()` renders `app.vaults.possessions.form` with the view model in editing mode; `update()` validates inline as `create()` does without `type`, resolves the location the same way, calls `UpdatePossession` with `identifier`, `status` and `quantity` taken from the possession (research R3), `isFeatured` from the request, `tagNames` and `values` from the request (`values` defaulting to `[]`), and redirects to `possessions.show` with `status` ":name is saved."
- [X] T010 [US1] `resources/views/app/vaults/possessions/form.blade.php`: title and page header from the view model (description line only when adding), trail ending with the possession's name then "Edit" when editing, `x-form` method and action from the view model, foot bar with "Save changes" and no "New :type" line when editing; first comment line kept under 110 characters and accurate for both modes
- [X] T011 [US1] `app/ViewModels/Vaults/PossessionViewModel.php` gains `editUrl()`; `resources/views/app/vaults/possessions/_header.blade.php` passes `:href="$page->editUrl()"` to `x-button.split` and drops `:unavailable`; the link is asserted once, in the controller test of the possession page
- [X] T012 [US1] Tests in `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`: edit renders with "Save changes"; update redirects to the possession page with the flash and changes the name; status, quantity and identifier unchanged after an update; an empty name returns with an error on `name`; the possession reached through another vault of the same account answers not found

## Phase 4: User Story 2, change the values of its type's fields (P1)

Independent test: change one field value and empty another, save; the page shows the new value and no longer the emptied one.

- [X] T013 [US2] In `form.blade.php`, when editing, replace the `_type-picker` include with the type shown as text with its colour chip and name, followed by `@include('app.vaults.possessions._type-fields', ...)` fed by `fieldsBlock()` (research R5); no `type` input sent; check `_type-fields.blade.php` renders outside the picker's Alpine scope and adjust it only if it reads picker state
- [X] T014 [US2] Controller test: update with a changed and an emptied value writes and clears them; a malformed value returns with the error on `values.<uuid>` and the name unchanged

## Phase 5: User Story 3, move, tag or feature it from the form (P2)

Independent test: change the location, remove a tag, untick Feature; the page reflects all three.

- [X] T015 [US3] Controller test: update moves the possession, replaces its tags with those sent, and unfeatures it; covered by the view model fallbacks of T007, no new code expected; fix what the test reveals

## Phase 6: Polish

- [X] T016 [P] `docs/portal/en/9-templates-and-customisation/16-possessions.md`: replace "Editing a possession also comes later." with how to edit, through the `writer` skill
- [X] T017 [P] Entry in `docs/FEATURES.md`
- [X] T018 `php artisan monica:localize`, translate every reported string in every locale, `--check` passes
- [X] T019 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T020 The quickstart's checks by hand at monica.test

## Dependencies

Foundational (T001 to T005) before every story. US1 before US2 and US3, which share its form and controller. Polish last.
Parallel: T002 beside T003 once T001 exists; T016 and T017 beside any story.

## Strategy

MVP is US1 plus US2, both P1: the form opens filled and saves every value it shows. US3 is tests over what US1 already wires.
