---
description: "Task list for the Edit relationship form"
---

# Tasks: Edit relationship form

**Input**: Design documents from `specs/045-edit-relationship-form/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/edit-relationship-screen.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup assertion. Each rule is tested once, in the layer that owns it.

**Organization**: US1 is the select of sentences, US2 the other person’s block, US3 the ended checkbox. All three edit the same action, view model, controller and view, so stories run in order, not in parallel.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, migration or configuration change (plan.md).

## Phase 2: Foundational

**Purpose**: the action and the routes know which contact the edit came from (research R2). Blocks every story.

- [X] T001 [P] In `app/Models/Relationship.php`, add `public function involves(Contact $contact): bool` returning whether `$contact->id` is `contact_a_id` or `contact_b_id`, with a model docblock sentence
- [X] T002 In `app/Actions/UpdateRelationship.php`, add `private readonly Contact $contact` after `$vault`, replace `bool $swapped = false` with `bool $contactHoldsRoleA = true`, refuse in `validate()` with `ModelNotFoundException('This contact is not on this relationship.')` when `! $this->relationship->involves($this->contact)`, rewrite `orderTheSides()` so the non symmetric order is `[$contact, $other]` when `$contactHoldsRoleA` and `[$other, $contact]` otherwise (symmetric stays canonical), and update the class docblock
- [X] T003 In `tests/Unit/Actions/UpdateRelationshipTest.php`, pass `contact:` in every call, replace `swapped:` with `contactHoldsRoleA:`, rewrite `it_swaps_which_person_carries_which_role` as `it_gives_the_contact_the_role_it_is_said_to_hold` covering both stored orders, and add `it_refuses_a_contact_the_relationship_does_not_involve`
- [X] T004 In `app/Http/Controllers/App/Vaults/ContactRelationshipController.php`, add `abort_unless($relationship->involves($contact), 404);` to `edit()` and `update()` and pass `contact: $contact` to `UpdateRelationship` (keeping today’s fields until US1)
- [X] T005 In `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`, add `it_refuses_a_relationship_that_does_not_involve_the_contact` for GET edit and PUT update (Jim and Pam’s link opened through Dwight’s URL answers 404)

**Checkpoint**: `php artisan test --compact tests/Unit/Actions/UpdateRelationshipTest.php tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php` passes.

## Phase 3: User Story 1 - Type and direction are one choice (P1) MVP

**Goal**: one "Relationship" select of sentences replaces the type select and "Which way round".

**Independent Test**: reverse a Parent and Child link from the select and read both tabs.

- [X] T006 [US1] In `app/ViewModels/Vaults/RelationshipEditViewModel.php`, add `otherSide(): Contact` (the side that is not `$contact`) and `sentenceGroups()` returning `array<int, array{label: string, options: array<int, array{value: string, label: string, otherRole: string}>}>` from `choices()->groups()` per research R5: value `{typeId}-a` with `__(':one is :two’s :role.')` (page contact, other, `mb_strtolower(role_a)`), plus `{typeId}-b` for an asymmetric type (other, page contact, `role_a`); a nameless other is `__('an unnamed :role', ['role' => mb_strtolower($otherRole)])`, passed through `Str::ucfirst()` when it opens the sentence; `otherRole` is the role the other side holds in that option
- [X] T007 [US1] In the same view model, make `values()` return `relationship` as `old('relationship', "{type id}-{a when the page contact is contact_a, b otherwise}")` (a symmetric type always `-a`) in place of `typeId` and `swapped`, make `description()` name the page contact first and a nameless other as "an unnamed :role", and delete `directions()`, `isSymmetric()` and `typeMap()`
- [X] T008 [US1] Create `tests/Unit/ViewModels/Vaults/RelationshipEditViewModelTest.php` (`php artisan make:test --phpunit --unit`, then move to the namespace) proving: two sentences for Parent and Child, one for Sibling; groups in the account’s category order; the stored sentence chosen for both stored orders; a withdrawn type the link carries still offered; a nameless other reads "Michael Scott is an unnamed child’s parent." and "An unnamed parent is Michael Scott’s parent."; the description names the page contact first
- [X] T009 [US1] In `ContactRelationshipController::update()`, validate `'relationship' => ['required', 'string', 'regex:/^\d+-[ab]$/']`, split it, find the type with `RelationshipType::query()->where('account_id', ...)->findOrFail()`, pass `contactHoldsRoleA: $side === 'a'`, remove `relationship_type_id` and `swapped`, and send `RelationshipTypeIsNotAvailable` and `RelationshipWouldLoopParentage` errors to `relationship`
- [X] T010 [US1] In `resources/views/app/vaults/contacts/relationships/edit.blade.php`, wrap the form in a plain `<div x-data="{ role: @js(...) }">` (research R6), replace the type select and the "Which way round" fieldset with `x-select id="relationship" name="relationship" :label="__('Relationship')"` holding one `<optgroup>` per group and `<option value data-role @selected>` per sentence with `x-on:change="role = $event.target.selectedOptions[0].dataset.role"`, and replace the footer with "Saved on both records at once.", Cancel and `__('Save changes')`
- [X] T011 [US1] In `ContactRelationshipControllerTest.php`, update `it_opens_the_form_that_corrects_a_relationship` to assert "Relationship", the stored sentence and "Save changes"; update `it_changes_a_relationship` to post `relationship`; add `it_refuses_a_relationship_value_it_cannot_read` (`12-x` refused on `relationship`)

**Checkpoint**: US1 tests pass; the screen reverses a link with the select alone.

## Phase 4: User Story 2 - The other person, in one of three states (P1)

**Goal**: the block under the caption, and the rename of a partial other side (research R4).

**Independent Test**: open the screen for a record, a named partial and a nameless partial; rename the two partial ones.

- [X] T012 [US2] In `app/Actions/UpdateRelationship.php`, add `?string $otherFirstName = null` and `?string $otherLastName = null`; when `$otherFirstName !== null`, refuse with `ModelNotFoundException('This contact has a record of their own.')` unless the other side `is_partial`; as the last statement of the transaction, when the first or last name differs, call `new UpdateContactName(...)` passing `prefix`, `middleName`, `suffix`, `maidenName`, `nickname` and `nameOrder` through from the other side unchanged
- [X] T013 [US2] In `UpdateRelationshipTest.php`, add `it_renames_a_partial_contact_on_the_other_side` (with `Queue::fake()` and `Queue::assertPushedOn('low', LogUserAction::class)` for the name change), `it_keeps_every_other_part_of_the_name`, `it_leaves_the_name_alone_when_none_is_given`, `it_refuses_to_rename_a_contact_with_a_record` and `it_leaves_the_name_unchanged_when_the_change_is_refused` (loop in parentage)
- [X] T014 [US2] In `RelationshipEditViewModel.php`, add `otherState(): string` (`record` when not `is_partial`, `nameless` when `isNameless()`, `named` otherwise), `otherRole(): string` (the caption for the chosen sentence) and `renameLine(): string` (`__(':name has no contact record, so their name is changed here.', ['name' => first name])`), and add `mode`, `firstName` and `lastName` to `values()` from `old()` defaulting to the other side’s names, and `mode` to `known` for `named` and `unknown` for `nameless`
- [X] T015 [US2] In `RelationshipEditViewModelTest.php`, prove the three states, the other side found for both stored orders, both sides partial, and the screen opened from the partial contact’s own page giving `record`
- [X] T016 [US2] In `ContactRelationshipController::update()`, validate `'mode' => ['nullable', 'string', Rule::in(['unknown', 'known'])]`, `'first_name' => ['required_if:mode,known', 'nullable', 'string', 'max:255']`, `'last_name' => ['nullable', 'string', 'max:255']`, and pass `otherFirstName`/`otherLastName` only when `mode` is `known`
- [X] T017 [US2] In `edit.blade.php`, add the block per the contract: `x-caption x-text="role"`; `record` draws `x-contact-avatar` and the name; `named` posts a hidden `mode=known` with First name and Last name (`optional`) and the rename line tied to First name by `aria-describedby`; `nameless` draws a `<fieldset>` whose `<legend>` is the caption, two `x-option-row` radios (`__('I do not know their name')`, `__('I know their name now')`) bound to `knows`, and the fields in a `<fieldset x-show="knows" :disabled="! knows">`
- [X] T018 [US2] In `ContactRelationshipControllerTest.php`, add `it_renames_a_partial_contact_through_the_form` and `it_asks_for_a_first_name_when_the_name_is_said_to_be_known_now`

**Checkpoint**: US2 tests pass; each of the three states renders and saves.

## Phase 5: User Story 3 - One checkbox says whether it ended (P1)

**Goal**: the 1c checkbox replaces "Is it still going" (research R3).

**Independent Test**: tick and untick, with and without a date, and read the state on the tab.

- [X] T019 [US3] In `app/Actions/UpdateRelationship.php`, save `ended_on` as null whenever `$status` is `RelationshipStatusEnum::Current`, and check the end before the start only for an ended relationship
- [X] T020 [US3] In `UpdateRelationshipTest.php`, extend `it_marks_a_relationship_as_ended_and_current_again` to prove an end date is cleared when it is current again, and add `it_ends_a_relationship_on_a_date_nobody_wrote_down`
- [X] T021 [US3] In `RelationshipEditViewModel.php`, replace `status` in `values()` with `hasEnded` (bool, `old('has_ended')` after a failed save, the stored status otherwise) and delete `statuses()`
- [X] T022 [US3] In `ContactRelationshipController::update()`, replace the `status` rule with `'has_ended' => ['nullable', 'boolean']` and pass `status: $request->boolean('has_ended') ? RelationshipStatusEnum::Ended : RelationshipStatusEnum::Current`
- [X] T023 [US3] In `edit.blade.php`, delete the "Is it still going" fieldset, add `ended` to `x-data`, add `x-checkbox id="has_ended" x-model="ended"` reading `__('This relationship has ended')`, and put "Ended on" in a `<fieldset x-show="ended" :disabled="! ended">` beside "Started on"
- [X] T024 [US3] In `ContactRelationshipControllerTest.php`, replace `it_refuses_a_change_without_a_status` with `it_ends_a_relationship_when_the_box_is_ticked`

**Checkpoint**: US3 tests pass; SC-003 holds (open and save unchanged, nothing moves).

## Phase 6: Polish

- [X] T025 Run `php artisan monica:localize`, translate every reported string in every configured locale following `.claude/rules/translations.md` and its glossary, then run `php artisan monica:localize --check`
- [X] T026 Run `grep -rn "Still going\|Is it still going\|:name is the :role" app resources` and confirm nothing still reads the removed strings
- [X] T027 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, `php artisan test --compact tests/Unit/Actions tests/Unit/ViewModels/Vaults tests/Feature/Controllers/App/Vaults tests/Feature/RouteProtectionTest.php`
- [X] T028 Walk `quickstart.md` by hand, with screenshots through the `take-screenshot` skill

## Dependencies

- Phase 2 blocks everything. US1 → US2 → US3, since they share `UpdateRelationship.php`, `RelationshipEditViewModel.php`, the controller and `edit.blade.php`.
- Within a story: action → action test; view model → view model test; controller → view → controller test.
- Only T001 is `[P]`. Within a story, the action pair and the view model pair can be split between two agents because they touch different files.

## Implementation Strategy

Build the MVP as Phase 2 plus US1: the screen reverses links with one select and nothing else changes. Then add US2 (renaming) and US3 (the checkbox), checking each at its checkpoint, and finish with Phase 6.
