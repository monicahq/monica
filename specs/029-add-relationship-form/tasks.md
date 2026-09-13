---

description: "Task list for Add a relationship"
---

# Tasks: Add a relationship

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md), [spec/models/026](../../spec/models/026-relationship-not-yet.md)

**Tests**: required, not optional. Constitution principle III is non-negotiable and every model, action, controller and view model here carries one.

**Format**: `[ID] [P?] [Story] Description with file path`. `[P]` means a different file with no incomplete dependency.

## Phase 1: Setup (the record every story writes)

**Goal**: the table, the model and the enum exist, with nothing reading them yet.

- [X] T001 [P] Add `RelationshipStatusEnum` (`Current`, `Ended`, `label()`, `translationKeys()`, `#[Override]`) in `app/Enums/RelationshipStatusEnum.php`
- [X] T002 [P] Add `RelationshipCreation = 'relationship_created'` and its `description()` sentence in `app/Enums/UserActionEnum.php`
- [X] T003 Write the table in `database/migrations/2026_09_10_000005_create_relationships_table.php`: `up()` only, columns commented, no `constrained()`, foreign keys at the bottom, and the two composite indexes from spec/models/026 given explicit names to stay under 64 characters
- [X] T004 Add `Relationship` in `app/Models/Relationship.php` with `#[RouteKey('uuid')]`, `#[Fillable]`, `HasFactory`, `HasUuids`, `ResolvesModelInVault`, `SoftDeletes`, `casts()` for the enum and the two dates, and `account()`, `vault()`, `relationshipType()`, `contactA()`, `contactB()`
- [X] T005 [P] Add `RelationshipFactory` in `database/factories/RelationshipFactory.php`, both contacts resolved into one vault, with `ended()` and `parentage()` states
- [X] T006 [P] Test the model in `tests/Unit/Models/RelationshipTest.php`: the five relations, the casts, and that `ResolvesModelInVault` refuses another account and another vault
- [X] T007 [P] Test the enum in `tests/Unit/Enums/RelationshipStatusEnumTest.php`

**Checkpoint**: `migrate:fresh` builds the table and the factory makes a row.

## Phase 2: Foundational (the screen exists and chooses a type)

**Goal**: the form opens from the contact, lists the account's types and saves nothing yet. Blocks every story.

- [X] T008 Replace the `return 0;` stub with a real count in `app/Models/RelationshipType.php`
- [X] T009 Rewrite the assertion that `relationshipCount()` is `0` in `tests/Unit/Models/RelationshipTypeTest.php:109`, and cover that a type in use refuses deletion and a direction change
- [X] T010 Add `contacts.relationships.new` (GET) and `contacts.relationships.create` (POST) in `routes/web.php`, above the `contacts/{contact}/{page?}` catch-all at line 230, and add no `GET .../relationships`
- [X] T011 Add `RelationshipFormViewModel` in `app/ViewModels/Vaults/RelationshipFormViewModel.php`: active types grouped by active category with both roles in the label, `action()`, `method()`, the breadcrumb and title copy, the two sentence templates, and `values()` merging `old()`
- [X] T012 [P] Test the view model in `tests/Unit/ViewModels/Vaults/RelationshipFormViewModelTest.php`: only active types in active categories, only this account's, and both roles in each label
- [X] T013 Add `ContactRelationshipController` with `new()` and `create()` in `app/Http/Controllers/App/Vaults/ContactRelationshipController.php`, each guarded by `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`
- [X] T014 Write the form shell in `resources/views/app/vaults/contacts/relationships/form.blade.php`: purpose comment, `@var` line, `x-shell` with breadcrumb and vault sidebar, `x-page-header`, `x-form`, the type `x-select`, the footer hint and a disabled `x-button`
- [X] T015 Replace the `relationships` sentence in `ContactViewModel::unbuilt()` (`app/ViewModels/Vaults/ContactViewModel.php:626`) with a way in to the form
- [X] T016 Test the way in and the compartmentalisation trio in `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`: the form renders, another account 404s, another vault of the same account 404s, a visitor is sent to sign in

**Checkpoint**: the form opens, the picker lists the ten supplied types, and nothing else is shown.

## Phase 3: User Story 1 (P1) 🎯 MVP

**Goal**: link the contact to somebody already in the vault.

**Independent test**: choose a type, search, select, save, and find one row with the two contacts on the right sides.

- [X] T017 [US1] Add `CreateRelationship` in `app/Actions/CreateRelationship.php`: promoted readonly constructor, `execute()` calling `validate()`, `create()` and `log()`, refusing a self-link, a contact or type in another account, a contact in another vault and an inactive type, and logging through `LogUserAction` on the `low` queue with the vault
- [X] T018 [US1] Refuse a parentage cycle in `CreateRelationship` by walking ancestors through types whose `system_behavior` is `GenealogyParentage`, one query per generation
- [X] T019 [US1] Add the vault contact search to `RelationshipFormViewModel` using `Contact::scopeMatchingSearch()` scoped to the vault, excluding the viewed contact, paginated
- [X] T020 [US1] Render the three-way choice in the form as a `fieldset` with a `legend` and three `x-option-row type="radio"`, and reveal the branches with `x-show`/`x-cloak` and `::disabled`
- [X] T021 [US1] Render the search field, the results list and the empty line in the form, refreshed through Alpine Ajax `x-target`, clearing the chosen person when the text changes
- [X] T022 [US1] Render the role captions and the `aria-live="polite"` preview sentence in the form, fed by the view model's templates
- [X] T023 [US1] Validate the submission in the controller with array-notation rules, `Rule::exists` scoped to the account for the type, and `Rule::in` for the mode
- [X] T024 [P] [US1] Test the action in `tests/Unit/Actions/CreateRelationshipTest.php`: the happy path, the self-link, the other account, the other vault, the inactive type and the parentage cycle
- [X] T025 [P] [US1] Test saving with an existing contact in `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`, including that no mirror row is written
- [X] T026 [P] [US1] Test the form's markup in `tests/Feature/Views/` for the three-way choice and the preview region

**Checkpoint**: US1 is demoable on its own.

## Phase 4: User Story 2 (P2 by build order, P1 by spec)

**Goal**: write somebody new down while recording the relationship.

**Independent test**: type a first name, save, and find both a new contact and a relationship pointing at it.

- [X] T027 [US2] Extend `CreateRelationship` to create the other contact through `new CreateContact(...)->execute()` inside one `DB::transaction`, passing `isPartial` as the inverse of the checkbox
- [X] T028 [US2] Render the first name and optional last name fields, revealed for the known-name mode, in the form
- [X] T029 [US2] Render the "Create a contact record for this person" checkbox, off by default and hidden for the existing-contact mode, in the form
- [X] T030 [P] [US2] Test in `tests/Unit/Actions/CreateRelationshipTest.php` that both checkbox states create a contact, that only `is_partial` differs, and that a failure rolls both writes back
- [X] T031 [P] [US2] Test saving with a new named person in the controller test, asserting the contact is absent from the people list when partial

**Checkpoint**: US1 and US2 both work.

## Phase 5: User Story 3 (P2)

**Goal**: record a relationship to somebody whose name is unknown.

**Independent test**: save twice with no name and find two distinct contacts and two distinct relationships.

- [X] T032 [US3] Allow every name component to be empty for the unknown-name mode in the controller's rules and in `CreateRelationship`, storing no placeholder
- [X] T033 [US3] Reveal no name fields for the unknown-name mode, and read the other side by role in the preview sentence, in the form
- [X] T034 [P] [US3] Test in `tests/Unit/Actions/CreateRelationshipTest.php` that two nameless saves produce two contacts and two relationships
- [X] T035 [P] [US3] Test the nameless path end to end in the controller test

**Checkpoint**: all three ways of naming the other side work.

## Phase 6: User Story 4 (P2)

**Goal**: put the two people on the right sides of a directed type.

**Independent test**: swap on a directed type, save, and find the viewed contact on the opposite side.

- [X] T036 [US4] Store the two contacts in a canonical order for a symmetric type in `CreateRelationship`, and honour the swap for a directed one
- [X] T037 [US4] Render the swap control in the form, unavailable for a symmetric type, exchanging both captions, both block positions and the sentence, and forgotten when the type changes
- [X] T038 [P] [US4] Test the canonical order and the swap in `tests/Unit/Actions/CreateRelationshipTest.php`
- [X] T039 [P] [US4] Test that saving after a swap puts the viewed contact on the opposite side, in the controller test

**Checkpoint**: every story in the specification is built.

## Phase 7: Delivery conditions

**Goal**: the checks the constitution makes a condition of being done rather than a follow-up.

- [X] T040 Run `php artisan monica:localize` and translate everything it reports, in all 31 locales, honouring each locale's typography and the counted-phrase rules
- [X] T041 Run `php artisan monica:localize --check` and fix what it refuses
- [X] T042 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse` and fix what they report
- [X] T043 Run the whole suite on SQLite, and confirm nothing outside this feature broke through `relationshipCount()`
- [X] T044 Walk the form with the keyboard alone and with a screen reader, confirm focus stays visible, that no meaning is carried by colour alone, that it reads at 200% zoom and on a phone, and that reduced motion removes the reveal
- [X] T045 Walk [quickstart.md](quickstart.md) end to end

## Dependencies

- Phase 1 blocks everything. T003 blocks T004; T004 blocks T005, T006 and every later phase.
- Phase 2 blocks every story. T008 blocks T009. T011 blocks T013 and T014. T010 blocks T013.
- T017 blocks T018, T027, T032 and T036: they all edit `CreateRelationship`.
- T014 blocks T020, T021, T022, T028, T029, T033 and T037: they all edit the one Blade file, so none of them are `[P]` with each other.
- US2, US3 and US4 each depend on US1 being built, because they extend its action and its form rather than standing beside them. They stay independently **testable**, which is what the specification asks for, but they are not independently **buildable**.
- Phase 7 depends on every phase you intend to ship.

## Parallel opportunities

- T001, T002 and T005 through T007 in Phase 1.
- T012 alongside T013 in Phase 2.
- The test tasks closing each story: T024 to T026, T030 and T031, T034 and T035, T038 and T039.
- Nothing that touches `app/Actions/CreateRelationship.php` or the single Blade form runs in parallel with anything else touching it.

## Implementation strategy

Phase 1 and Phase 2 give a screen that opens and chooses a type. Phase 3 is the MVP and is worth stopping at to validate. Phases 4 to 6 are each a day or less on top of it. Phase 7 is not optional and is not a follow-up pull request.
