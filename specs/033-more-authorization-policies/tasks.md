---

description: "Task list for More Authorization Policies"
---

# Tasks: More Authorization Policies

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: included. Principle III is non-negotiable and `.claude/rules/testing.md` asks for a test per change.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different file, no dependency on an unfinished task
- **[Story]**: the user story the task belongs to
- The inventory of every rule and the line each copy sits on is [research.md](research.md) section 1

---

## Phase 1: Setup

- [X] T001 028 rides on this branch rather than being merged first, decided 2026-09-14, so `User::authorize()` and `PolicyCoverageTest` are already present
- [X] T002 Confirm `PolicyCoverageTest` fails for a class under `app/Policies` with no matching test file, since eight new classes rely on it

---

## Phase 2: Foundational (Blocking Prerequisites)

No blocking work. 028 shipped the way an ability is declared, asked and proven, and each story below creates its own policy classes and touches its own screens. `ContactPolicy` is the only file two capabilities share and both are inside US4.

**Checkpoint**: the four stories may start in any order, or together.

---

## Phase 3: User Story 1 - The settings screens say why (Priority: P1) 🎯 MVP

**Goal**: five delete and add rules on the account settings screens each have one home, and no control disappears without saying why.

**Independent Test**: the first five rows of the table in [quickstart.md](quickstart.md). Change the wording in `RelationshipTypePolicy::delete()` and confirm the form and the refusal both follow.

### Tests for User Story 1

- [X] T003 [P] [US1] Write `tests/Unit/Policies/RelationshipTypePolicyTest.php`: permitted, refused when the product supplied it, refused when relationships use it, refused with nobody signed in
- [X] T004 [P] [US1] Write `tests/Unit/Policies/ValueListPolicyTest.php` covering `delete` and `addItem`, each permitted and refused, and refused with nobody signed in
- [X] T005 [P] [US1] Write `tests/Unit/Policies/ValueListItemPolicyTest.php`: permitted, refused when records point at it, refused with nobody signed in
- [X] T006 [P] [US1] Write `tests/Unit/Policies/EntityTypePolicyTest.php`: permitted, refused when it holds records, refused with nobody signed in

### Implementation for User Story 1

- [X] T007 [P] [US1] Add `delete()` to `app/Policies/RelationshipTypePolicy.php`, carrying both refusal wordings
- [X] T008 [P] [US1] Add `delete()` and `addItem()` to `app/Policies/ValueListPolicy.php`
- [X] T009 [P] [US1] Add `delete()` to `app/Policies/ValueListItemPolicy.php`
- [X] T010 [P] [US1] Add `delete()` to `app/Policies/EntityTypePolicy.php`
- [X] T011 [P] [US1] Rewrite `validate()` in `app/Actions/DestroyRelationshipType.php` to ask the ability, leaving the `account_id` check where it is
- [X] T012 [P] [US1] Do the same in `app/Actions/DestroyValueList.php`
- [X] T013 [P] [US1] Do the same in `app/Actions/CreateValueListItem.php`
- [X] T014 [P] [US1] Do the same in `app/Actions/DestroyValueListItem.php`
- [X] T015 [P] [US1] Do the same in `app/Actions/DestroyEntityType.php`
- [X] T016 [US1] Rewrite `canBeDeleted()` in `app/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypeFormViewModel.php` to ask the ability, keeping `deleteBlockedReason()` as the screen's own guidance
- [X] T017 [US1] Rewrite the three keys in `app/ViewModels/Settings/Account/ValueLists/ValueListsViewModel.php` (lines 31, 32, 39) to ask the ability, and carry each reason
- [X] T018 [P] [US1] Rewrite `canBeDeleted()` in `app/ViewModels/Settings/Account/EntityTypes/EntityTypeFormViewModel.php` to ask the ability, and add the reason
- [X] T019 [US1] In `resources/views/app/settings/account/relationship-types/_delete.blade.php`, stop hiding the control and render it unavailable, pointing `aria-describedby` at its reason
- [X] T020 [P] [US1] Do the same in `resources/views/app/settings/account/entity-types/_delete.blade.php`
- [X] T021 [P] [US1] Do the same in `resources/views/app/settings/account/value-lists/_list.blade.php`, writing the reason the row does not carry today
- [ ] T022 [P] [US1] **Not done, and should not be.** `resources/views/app/settings/account/value-lists/_value.blade.php` does not hide the control: it swaps Delete for Stop offering, a different working control, and prints the usage as the reason. FR-003 governs a control removed because its rule refuses, not one substituted
- [X] T023 [US1] Catch the framework refusal in `app/Http/Controllers/Api/EntityTypeController.php` line 113, answering 422 with the same field and wording
- [X] T024 [P] [US1] Delete `app/Exceptions/CannotDeleteARelationshipTypeInUse.php`, `CannotDeleteASuppliedRelationshipType.php`, `CannotDeleteASuppliedList.php`, `CannotDeleteAValueInUse.php`, `CannotDeleteAnEntityTypeInUse.php` and `ValueListIsClosed.php`
- [X] T025 [P] [US1] Update `tests/Unit/Actions/DestroyRelationshipTypeTest.php`, `DestroyValueListTest.php`, `CreateValueListItemTest.php`, `DestroyValueListItemTest.php` and `DestroyEntityTypeTest.php` for the refusal type and the wording
- [X] T026 [US1] Assert each control renders unavailable with its reason when refused, and active otherwise, in `tests/Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypeControllerTest.php`, `EntityTypes/EntityTypeControllerTest.php`, `ValueLists/ValueListControllerTest.php` and `ValueLists/ValueListItemControllerTest.php`
- [X] T027 [US1] Assert the unchanged 422 in `tests/Feature/Controllers/Api/EntityTypeControllerTest.php`

**Checkpoint**: five rules have one home and five settings screens explain themselves.

---

## Phase 4: User Story 2 - The template builder tells the same story everywhere (Priority: P2)

**Goal**: sharing a template stops being written three times, and removing a page stops being written twice.

**Independent Test**: rows six and seven of the table in [quickstart.md](quickstart.md), including requesting the share address directly.

### Tests for User Story 2

- [ ] T028 [P] [US2] Write `tests/Unit/Policies/TemplatePolicyTest.php`: `share` permitted when a published version exists, refused otherwise, refused with nobody signed in, and the refusal answers as not found
- [ ] T029 [P] [US2] Write `tests/Unit/Policies/TemplateVersionPagePolicyTest.php`: `delete` permitted, refused for the last page, refused for a page holding sections, refused with nobody signed in

### Implementation for User Story 2

- [ ] T030 [P] [US2] Add `share()` to `app/Policies/TemplatePolicy.php`, refusing as not found so the three guards keep their answer
- [ ] T031 [P] [US2] Add `delete()` to `app/Policies/TemplateVersionPagePolicy.php`, carrying both refusal wordings
- [ ] T032 [P] [US2] Rewrite the guard in `app/Actions/ExportTemplate.php` line 41 to ask the ability
- [ ] T033 [P] [US2] Rewrite `validate()` in `app/Actions/DestroyTemplateVersionPage.php` to ask the ability
- [ ] T034 [US2] Replace `abort_if(..., 404)` with the ability in `app/Http/Controllers/App/Settings/Account/Templates/TemplateShareController.php` line 17
- [ ] T035 [US2] Do the same in `app/Http/Controllers/App/Settings/Account/Templates/TemplateExportController.php` lines 20 and 32
- [ ] T036 [US2] Rewrite `isShareable()` and `canRemovePage()` in `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php` to ask the abilities, and carry each reason
- [ ] T037 [US2] In `resources/views/app/settings/account/templates/builder.blade.php` line 70, render the share control unavailable with its reason instead of hiding it
- [ ] T038 [US2] Do the same for the page control in `resources/views/app/settings/account/templates/_pages.blade.php` line 78
- [ ] T039 [US2] Catch the framework refusal in `app/Http/Controllers/Api/TemplateVersionPageController.php` line 73, where two types collapse into one
- [ ] T040 [P] [US2] Delete `app/Exceptions/CannotDeleteAPageInUse.php` and `CannotDeleteTheLastPage.php`, leaving `ListingHasNothingToPublish.php` because `ExportSection` still throws it
- [ ] T041 [P] [US2] Update `tests/Unit/Actions/ExportTemplateTest.php` and `DestroyTemplateVersionPageTest.php`
- [ ] T042 [US2] Assert the three template addresses still answer as not found, in `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateShareControllerTest.php` and `TemplateExportControllerTest.php`
- [ ] T043 [US2] Assert both builder controls render unavailable with their reason, and active otherwise, in `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateBuilderControllerTest.php`

**Checkpoint**: the sharing rule is stated once, and reaching it by its address answers exactly as before.

---

## Phase 5: User Story 3 - Giving up a way of signing in (Priority: P3)

**Goal**: the two rules guarding the last way into an account each have one home.

**Independent Test**: rows eight and nine of the table in [quickstart.md](quickstart.md).

### Tests for User Story 3

- [ ] T044 [P] [US3] Write `tests/Unit/Policies/UserPolicyTest.php` covering `removePassword` and `disconnectProvider`, each permitted and refused for every branch, and refused with nobody signed in

### Implementation for User Story 3

- [ ] T045 [US3] Add `removePassword()` and `disconnectProvider()` to `app/Policies/UserPolicy.php`, carrying the wordings the two actions hold today
- [ ] T046 [P] [US3] Rewrite `validate()` in `app/Actions/DestroyUserPassword.php` to ask the ability
- [ ] T047 [P] [US3] Rewrite `validate()` in `app/Actions/DisconnectProviderFromUser.php` to ask the ability
- [ ] T048 [US3] Convert the refusal to the field error the form shows today in `app/Http/Controllers/App/Settings/Account/Security/PasswordController.php`, since these two refuse as validation rather than as a domain error
- [ ] T049 [US3] Do the same in `app/Http/Controllers/App/Settings/Account/Security/SsoController.php`
- [ ] T050 [US3] Rewrite `canRemoveThePassword()` and `canDisconnectTheProvider()` in `app/ViewModels/Settings/Account/Security/SecurityViewModel.php` to ask the abilities, and carry each reason
- [ ] T051 [US3] In `resources/views/app/settings/account/security/_remove-password.blade.php` line 36, render the control unavailable with its reason instead of hiding it
- [ ] T052 [US3] Do the same in `resources/views/app/settings/account/security/_sso.blade.php` line 15
- [ ] T053 [P] [US3] Update `tests/Unit/Actions/DestroyUserPasswordTest.php` and `DisconnectProviderFromUserTest.php`
- [ ] T054 [US3] Assert both controls render unavailable with their reason, and that the form still reports the refusal on its field, in `tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php`, `PasswordControllerTest.php` and `SsoControllerTest.php`

**Checkpoint**: nobody is told nothing when they cannot give up a way in.

---

## Phase 6: User Story 4 - The last two copies (Priority: P4)

**Goal**: the contact representing somebody is offered and refused rather than left out, moderation states its rule once, and the ceiling rule 028 split is made whole.

**Independent Test**: rows ten and eleven of the table in [quickstart.md](quickstart.md).

### Tests for User Story 4

- [ ] T055 [P] [US4] Add `absorb` cases to `tests/Unit/Policies/ContactPolicyTest.php`: refused for the contact representing somebody, permitted for any other, refused with nobody signed in
- [ ] T056 [P] [US4] Write `tests/Unit/Policies/MarketplaceListingVersionPolicyTest.php`: `decide` permitted while awaiting moderation, refused otherwise, refused with nobody signed in
- [ ] T057 [US4] Add cases to `tests/Unit/Policies/ContactPolicyTest.php` proving `create` permits a partial contact and the contact representing somebody at the ceiling (same file as T055)

### Implementation for User Story 4

- [ ] T058 [US4] Add `absorb()` to `app/Policies/ContactPolicy.php`
- [ ] T059 [P] [US4] Add `decide()` to `app/Policies/MarketplaceListingVersionPolicy.php`
- [ ] T060 [US4] Give `create()` in `app/Policies/ContactPolicy.php` the two facts it lacks as context, per FR-005 and [research.md](research.md) section 6 (same file as T058)
- [ ] T061 [US4] Remove the early returns from `refuseAboveTheCeiling()` in `app/Actions/CreateContact.php` and pass the context instead
- [ ] T062 [P] [US4] Rewrite the `is_me` guard in `app/Actions/PreviewContactMerge.php` line 75 to ask the ability
- [ ] T063 [P] [US4] Rewrite `validate()` in `app/Actions/ApproveMarketplaceListingVersion.php` to ask the ability
- [ ] T064 [P] [US4] Do the same in `app/Actions/RejectMarketplaceListingVersion.php`
- [ ] T065 [US4] Stop filtering the contact representing somebody out at `app/ViewModels/Vaults/ContactMergeViewModel.php` line 70; offer them and carry the reason
- [ ] T066 [P] [US4] Rewrite `awaitsModeration()` in `app/ViewModels/Admin/ModerationSubmissionViewModel.php` to ask the ability, and carry the reason
- [ ] T067 [US4] In `resources/views/app/vaults/contacts/merge/new.blade.php`, render the refused candidate unavailable with its reason
- [ ] T068 [US4] In `resources/views/app/admin/moderation/show.blade.php`, render both decisions unavailable with their reason instead of hiding them
- [ ] T069 [P] [US4] Delete `app/Exceptions/CannotAbsorbTheMeContact.php` and `ListingIsNotAwaitingModeration.php`
- [ ] T070 [P] [US4] Update `tests/Unit/Actions/PreviewContactMergeTest.php`, `ApproveMarketplaceListingVersionTest.php`, `RejectMarketplaceListingVersionTest.php` and `CreateContactTest.php`
- [ ] T071 [US4] Assert the refused candidate and the refused decisions are offered with their reason, in `tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php` and `tests/Feature/Controllers/App/Admin/ModerationControllerTest.php`

**Checkpoint**: every rule in FR-007 has one home, and the ceiling rule is whole.

---

## Phase 7: Polish and Cross-Cutting Concerns

- [ ] T072 Run `php artisan monica:localize` and translate every reason added, in all 31 locales, never editing `lang/*.json` by hand
- [ ] T073 Run `php artisan monica:localize --check` and fix what it reports
- [ ] T074 Walk every screen in scope for keyboard operation, screen reader announcement of the unavailable state, visible focus and 200% zoom, per Principle IX
- [ ] T075 Run `vendor/bin/pint --dirty --format agent` and PHPStan at level 5
- [ ] T076 Run the full suite on SQLite, MySQL and PostgreSQL
- [ ] T077 Walk [quickstart.md](quickstart.md) end to end, including the "What must not change" list

---

## Found while implementing

- **A twelfth duplication, out of scope.** `RelationshipTypeFormViewModel::directionIsEditable()` reads the same condition as `canBeDeleted()` but answers a different question, and mirrors `UpdateRelationshipType::directionIsFrozen()`. FR-007 does not list it. [research.md](research.md) section 1 said lines 116 and 170 were one rule; they are two.
- **Two screens explained themselves already.** The relationship type and entity type delete partials printed the reason and dropped the control. Only the control had to come back, so no wording was written for them.
- **One new string in the whole story**, the closed value list reason. Every other wording already shipped, because the web controllers carried it.

## Dependencies and Execution Order

- **T001 blocks everything.** This branch calls `User::authorize()`, which 028 adds.
- **Phase 2 is empty**, so the four stories depend only on Phase 1 and may run in any order or together.
- **Within a story**: the policy tests, then the policy, then the actions and the view models, then the templates, then the controllers, then the test updates.
- **T016, T017 and T018 depend on T007 to T010**, since a view model cannot ask an ability that does not exist yet.
- **T019 to T022 depend on the view model that feeds them**, T016 for T019, T017 for T021 and T022, T018 for T020.
- **T034 and T035 depend on T030.** They are the second and third copies of the sharing rule, and removing them before the policy exists leaves the address unguarded.
- **T024, T040 and T069 must follow every caller of the exception they delete**, which is why each sits after its story's actions and controllers.
- **T057 and T060 follow T055 and T058**, both pairs writing `ContactPolicyTest.php` and `ContactPolicy.php`.
- **T060 blocks T061.** The action may only drop its early returns once the policy carries them.
- **FR-006 carries no task.** It forbids rather than requires, and is proven by T024, T040 and T069 deleting types rather than adding any.
- **Phase 7 depends on every story being shipped.** T072 and T073 must follow every reason written; T074 must follow every template.

### Parallel opportunities

- T003 to T006 together.
- T007 to T015 together, all different files.
- T020, T021 and T022 together, once their view models are done.
- T028 and T029 together, then T030 to T033 together.
- T046 and T047 together.
- T055, T056 and T059 together.
- T062, T063 and T064 together.
- The four stories together, if more than one person is working.
