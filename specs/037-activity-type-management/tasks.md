---
description: "Task list for activity type management"
---

# Tasks: Activity type management

**Input**: `spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/routes.md`, `quickstart.md`

**Tests**: required, not optional. Constitution principle III is non-negotiable, so every action,
policy, controller and view model carries its test, and `PolicyCoverageTest` fails the suite for a
policy shipped without three cases per ability.

**Length**: principle XI asks for one line per task, phase headings and a dependency list. Views that
are written together against one screen are grouped on one line where they are one gesture.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- **[Story]**: US1 to US4 from `spec.md`

---

## Phase 1: The written record

**Purpose**: three statements in `spec/models` are made false by this feature. They are corrected
first, so the target is reviewable before it is built.

- [X] T001 [P] Edit `spec/models/035-activity-type-in-progress.md`: the status line says the screens exist and the API follows, and Management screens drops what is deferred (supplied catalogue, search, inline creation)
- [X] T002 [P] Edit `spec/models/028-activity-category-done.md`: the closing note stops saying the settings screen is not built and `activity_type_count` is always zero
- [X] T003 [P] Edit `spec/models/README.md`: model 035 has a table and a model, so it leaves the "no table and no model at all" list and joins the list above it

**Checkpoint**: the model specifications describe what is about to exist.

---

## Phase 2: Foundational

**Purpose**: the engine, the count, the actions and the policies every story reads. Nothing here is
user visible and everything after it depends on all of it.

- [X] T004 [P] Widen `StructureVersionSection::isReachableThrough()` in `app/Models/StructureVersionSection.php` from `Template $template` to any owner model, and update its callers in `app/Http/Controllers/App/Settings/Account/Templates/`
- [X] T005 [P] Make `ActivityCategory::activityTypeCount()` in `app/Models/ActivityCategory.php` count the undeleted types filed under the category, dropping the comment that defers it
- [X] T006 Extend `tests/Unit/Models/StructureVersionSectionTest.php` and `tests/Unit/Models/ActivityCategoryTest.php` for T004 and T005
- [X] T007 [P] Add `ActivityTypeUpdate`, `ActivityTypePositionUpdate`, `ActivityTypeActivated` and `ActivityTypeDeactivated` to `app/Enums/UserActionEnum.php`, each with its wording
- [X] T008 [P] Write `app/Actions/UpdateActivityType.php`: name, category, description, icon and colour, sanitised, refusing a category of another account
- [X] T009 [P] Write `app/Actions/UpdateActivityTypePosition.php`, renumbering the types of one category from one
- [X] T010 [P] Write `app/Actions/ActivateActivityType.php` and `app/Actions/DeactivateActivityType.php`, on the shape `ActivateActivityCategory` already has
- [X] T011 [P] Write `tests/Unit/Actions/UpdateActivityTypeTest.php`, `UpdateActivityTypePositionTest.php`, `ActivateActivityTypeTest.php` and `DeactivateActivityTypeTest.php`
- [X] T012 Write `app/Policies/ActivityTypePolicy.php` and `app/Policies/ActivityCategoryPolicy.php`, each a `delete()` denying with the wording the reader already sees (research D4)
- [X] T013 Write `tests/Unit/Policies/ActivityTypePolicyTest.php` and `ActivityCategoryPolicyTest.php`: permitted, refused, and refused with nobody signed in
- [X] T014 Move the refusals in `app/Actions/DestroyActivityType.php` and `app/Actions/DestroyActivityCategory.php` onto the policies through `User::authorize()`, then delete `app/Exceptions/CannotDeleteASuppliedActivityType.php`, `CannotDeleteASuppliedActivityCategory.php` and `CannotDeleteAnActivityCategoryInUse.php`
- [X] T015 Catch `AuthorizationException` in `app/Http/Controllers/Api/ActivityCategoryController.php` in place of the deleted exceptions, keeping the 422 and its wording
- [X] T016 Update `tests/Unit/Actions/DestroyActivityTypeTest.php`, `DestroyActivityCategoryTest.php` and the API category controller test for T014 and T015

**Checkpoint**: the count is honest, the four actions exist and every refusal is stated once.

---

## Phase 3: User Story 1, read the vocabulary (P1) 🎯 MVP

**Goal**: Settings carries an Activity types screen listing the categories, their counts and the
types of whichever one is selected.

**Independent Test**: seed categories and types, open the screen, and read the rail, the counts, the
selected list and the marks on whatever is switched off.

- [X] T017 [US1] Declare `settings.activityTypes.index` and `settings.activityTypes.category` in `routes/web.php`, both on `ActivityTypeController::index` with an optional bound category
- [X] T018 [US1] Write `app/Http/Controllers/App/Settings/Account/ActivityTypes/ActivityTypeController.php` with `index`
- [X] T019 [US1] Write `app/ViewModels/Settings/Account/ActivityTypes/ActivityTypesViewModel.php`: the rail with counts, the selected category, its types in position order, and the every-type ordering by category then type
- [X] T020 [US1] Write `resources/views/app/settings/account/activity-types/index.blade.php` on `x-settings.page` with `current="activity-types"`, `x-page-header` and `x-list-panel`
- [X] T021 [P] [US1] Write `resources/views/app/settings/account/activity-types/_rail.blade.php`: one link per category with `aria-current`, its count, and the every-type entry above them
- [X] T022 [P] [US1] Write `resources/views/app/settings/account/activity-types/_row.blade.php`: the mark, the name, the not-offered badge and the way into the type
- [X] T023 [US1] Add the `activity-types` entry to `resources/views/components/settings/sidebar.blade.php`, next to Relationship types
- [X] T024 [US1] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/ActivityTypeControllerTest.php` for `index`, including a category of another account answering not found
- [X] T025 [P] [US1] Write `tests/Unit/ViewModels/Settings/Account/ActivityTypes/ActivityTypesViewModelTest.php`
- [X] T026 [P] [US1] Write `tests/Feature/Views/Settings/ActivityTypesIndexTest.php` for the copy, the links and `aria-current`, asserting no class string

**Checkpoint**: the screen exists and is honest about an account that holds no type.

---

## Phase 4: User Story 2, add and edit a type (P1)

**Goal**: a type is added on a screen of its own, edited there, retired there and deleted there.

**Independent Test**: add a type, reopen it, move it to another category, switch it off, delete it.

- [X] T027 [US2] Declare `settings.activityTypes.new`, `.create`, `.edit`, `.update`, `.destroy`, `settings.activityTypePosition.update` and `settings.activityTypeActivation.update` in `routes/web.php`
- [X] T028 [US2] Extend `ActivityTypeController` with `new`, `create`, `edit`, `update` and `destroy`, validating inline and catching `AuthorizationException` on the delete
- [X] T029 [P] [US2] Write `ActivityTypePositionController` and `ActivityTypeActivationController` in the same namespace
- [X] T030 [US2] Write `app/ViewModels/Settings/Account/ActivityTypes/ActivityTypeFormViewModel.php`: the type, the choosable categories with a switched-off one marked, the glyphs from `Section::ICONS` and the colours from `VaultColourEnum` (research D6)
- [X] T031 [US2] Write `resources/views/app/settings/account/activity-types/form.blade.php`: name, category, description, the mark picker, availability, and the delete panel carrying the policy's reason when it refuses
- [X] T032 [US2] Add the move controls to `_row.blade.php` with `x-position-form` and `x-sortable-row`, one button per direction (principle IX)
- [X] T033 [US2] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/ActivityTypeControllerTest.php` cases for create, update, destroy and validation failure
- [X] T034 [P] [US2] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/ActivityTypePositionControllerTest.php` and `ActivityTypeActivationControllerTest.php`
- [X] T035 [P] [US2] Write `tests/Unit/ViewModels/Settings/Account/ActivityTypes/ActivityTypeFormViewModelTest.php`
- [X] T036 [P] [US2] Write `tests/Feature/Views/Settings/ActivityTypeFormTest.php` for the field names, the form action and the refused delete carrying `aria-describedby`

**Checkpoint**: an account can build its own vocabulary from nothing.

---

## Phase 5: User Story 3, manage the categories in place (P2)

**Goal**: a category is added in a modal, renamed inline, moved, switched off and on, and deleted.

**Independent Test**: walk steps 3, 4 and 8 of `quickstart.md`.

- [X] T037 [US3] Declare `settings.activityCategories.create`, `.update`, `.destroy`, `settings.activityCategoryPosition.update` and `settings.activityCategoryActivation.update` in `routes/web.php`
- [X] T038 [US3] Write `ActivityCategoryController` with `create`, `update` and `destroy` in `app/Http/Controllers/App/Settings/Account/ActivityTypes/`
- [X] T039 [P] [US3] Write `ActivityCategoryPositionController` and `ActivityCategoryActivationController` in the same namespace
- [X] T040 [US3] Add the add-a-category `x-modal.form` to `index.blade.php`, reopening on an error with what was typed
- [X] T041 [US3] Write `resources/views/app/settings/account/activity-types/_category-actions.blade.php`: the inline rename, and an `x-dropdown` carrying move up, move down, switch off or on, and delete
- [X] T042 [US3] Write the two warnings: switching off says how many types leave with `trans_choice()`, and deleting says what stays or why it is refused
- [X] T043 [US3] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/ActivityCategoryControllerTest.php` for create, rename, empty name, delete allowed and delete refused
- [X] T044 [P] [US3] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/ActivityCategoryPositionControllerTest.php` and `ActivityCategoryActivationControllerTest.php`
- [X] T045 [P] [US3] Extend `tests/Feature/Views/Settings/ActivityTypesIndexTest.php` for the modal, the menu and the refusal wording

**Checkpoint**: the rail is editable and every refusal says what to do instead.

---

## Phase 6: User Story 4, the custom fields of a type (P2)

**Goal**: a type's fields are built through the engine templates use, and the words template,
structure and version reach nobody.

**Independent Test**: step 7 of `quickstart.md`, then grep the new views for the three words.

- [X] T046 [US4] Declare the eight custom fields routes of `contracts/routes.md` in `routes/web.php`
- [X] T047 [US4] Write `CustomFieldsController` with `index`, resolving the type's structure and its editable or published version
- [X] T048 [P] [US4] Write `CustomFieldSectionController` and `CustomFieldPositionController`, calling `CreateSection`, `AddSectionToStructureVersion`, `MakeSectionVersionEditable` and the existing position actions
- [X] T049 [P] [US4] Write `CustomFieldController` with `new`, `create`, `update` and `destroy`, reusing `FieldCatalogueViewModel` and the value lists of the account
- [X] T050 [P] [US4] Write `CustomFieldPublicationController`, calling `PublishStructureVersion`, which already names the activity type in its log
- [X] T051 [US4] Write `app/ViewModels/Settings/Account/ActivityTypes/ActivityTypeCustomFieldsViewModel.php`: one page, its sections, their fields and the draft state, with no page, history, copy, export, import or share
- [X] T052 [US4] Write `resources/views/app/settings/account/activity-types/custom-fields.blade.php`, `_section.blade.php` and `_field.blade.php`
- [X] T053 [US4] Branch `ActivityTypeController::create` on the named submit value, redirecting to the type's custom fields instead of the list (research D3), and add the button to `form.blade.php`
- [X] T054 [US4] Show the fields panel on `form.blade.php`, saying how many fields the type asks for and leading into the builder
- [X] T055 [US4] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/CustomFieldControllerTest.php` and `CustomFieldSectionControllerTest.php`, including a section of another type answering not found
- [X] T056 [P] [US4] Write `tests/Feature/Controllers/App/Settings/Account/ActivityTypes/CustomFieldPublicationControllerTest.php`: publishing makes a version and leaves the previous one alone
- [X] T057 [P] [US4] Write `tests/Unit/ViewModels/Settings/Account/ActivityTypes/ActivityTypeCustomFieldsViewModelTest.php`
- [X] T058 [US4] Write `tests/Feature/Views/Settings/ActivityTypeCustomFieldsTest.php`, asserting the three forbidden words appear in none of the four screens (FR-014)

**Checkpoint**: a type asks for what its activities need, on the one engine.

---

## Phase 7: Polish

- [X] T059 Run `php artisan monica:localize` and translate everything it reports, in every configured locale
- [X] T060 Run `php artisan monica:localize --check` and correct every counted phrase it refuses
- [X] T061 [P] Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=2G`
- [X] T062 Run `php artisan test --compact` over the whole suite, because `x-list-panel`, `x-modal.form` and `x-position-form` are shared
- [ ] T063 Walk `quickstart.md` end to end at 400px and at 200% zoom, with the keyboard alone

---

## Dependencies & Execution Order

- **Phase 1** gates nothing technically and is reviewed before Phase 2 is written.
- **Phase 2** blocks every story. T004 and T005 come before T006; T007 before T008 to T010; T012 before T014; T014 before T015 and T016.
- **Phase 3 (US1)** depends on T005 for the counts and on nothing else in Phase 2.
- **Phase 4 (US2)** depends on Phase 3 for the screen it returns to, and on T008 to T012.
- **Phase 5 (US3)** depends on Phase 3 for the rail it edits, and on T012. It does not depend on Phase 4.
- **Phase 6 (US4)** depends on T004 and on Phase 4, which is where a type is created and where the panel lives.
- **Phase 7** depends on everything.

### On independence

US1 stands alone and is the MVP: an honest, empty screen is already better than no screen. US2 and
US3 each need US1 and are independent of each other, so they can be built in parallel. US4 needs US2,
because there is no type to give fields to until there is a form that makes one.

### Parallel opportunities

- T001 to T003 are three separate specification files.
- T008, T009 and T010 are four disjoint action files; T011 is their four test files.
- T021 and T022, T029, T034 to T036, T039, T044 and T045, T048 to T050, T056 and T057 touch disjoint files.
- US2 and US3 can be built at the same time once Phase 3 is green.

## Implementation Strategy

Phase 1 lands on its own. Phases 2 and 3 land together, because the count is what makes the rail
worth reading. Then US2, then US3, then US4, each with its checkpoint green and the whole suite run
before the next starts, since the screens are assembled from shared components.
