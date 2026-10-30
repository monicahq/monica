# Tasks: Tag contacts with categories

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution III, written per layer as `.claude/rules/testing.md` assigns and listed in plan.md, The Office for data.

## Phase 1: Setup

- [X] T001 Create branch `2026-10-29-contact-tags` from an up to date `main` and carry specs/085-contact-tags/ onto it (if `.git/config.lock` blocks `checkout -b`, build it with plumbing)

## Phase 2: Foundational (R1, blocks US1, US3, US4, US5)

- [X] T002 [P] Create app/Exceptions/ValueListItemIsStopped.php, a bare `RuntimeException` like its siblings; the action gives it the message "“:label” is no longer offered. Offer it again from the value lists in your settings." (FR-003)
- [X] T003 Create app/Actions/FindOrCreateValueListItem.php (research R1): promoted `user`, `valueList`, `wording`, `heldItemIds`; `execute(): ValueListItem` refuses another account with `ModelNotFoundException`, trims, compares `mb_strtolower` to every item of the list, returns an active match, returns a stopped match only when held, throws `ValueListItemIsStopped` otherwise, and with no match runs `CreateValueListItem` (which asks `addItem` and journals)
- [X] T004 Write tests/Unit/Actions/FindOrCreateValueListItemTest.php: case and surrounding spaces reuse, stopped refused, stopped held returned, `addItem` refused when the list refuses user values, one `ValueListItemCreation` journalled, another account's list not found

Checkpoint: one rule matches or creates a category for every path.

## Phase 3: User Story 1, create a category while editing a contact (P1) MVP

- [X] T005 [US1] In app/Helpers/FieldValueRules.php add `new_choices` (`array`), `new_choices.{fieldId}` (`array`, only for Multiselect fields of the section) and `new_choices.{fieldId}.*` (`string`, `max:255`), so app/Http/Requests/SaveFieldValuesRequest.php validates them (research R2)
- [X] T006 [US1] In app/Actions/SetFieldValues.php add the promoted `newChoices` array; inside the existing `DB::transaction`, run T003 for each wording of a Multiselect field with the field's held ids, and merge the returned ids, without duplicates, into that field's chosen ids before `chosenItems()`
- [X] T007 [US1] In app/Http/Controllers/App/Vaults/ContactSectionController.php pass `newChoices: $request->validated('new_choices', [])` and turn `ValueListItemIsStopped` and the `addItem` `AuthorizationException` into the existing redirect with `withErrors(['fields' => ...])`, as every other field refusal there
- [X] T008 [US1] In the Multiselect case of resources/views/components/field-input.blade.php (lines 236-261), when `auth()->user()->can('addItem', $field->valueList)`, draw a labelled text input and an Add button; inline Alpine checks the chip whose label matches (trimmed, case-insensitive) or appends a checked chip carrying `new_choices[{fieldId}][]`, on Enter or Add (the box itself is named `new_choices[{fieldId}][]` too, so a wording typed and never added is still saved), and announces "Added :label" in a polite live region (FR-012; read `.claude/rules/components.md` on `$el` and `@js()` first)
- [X] T009 [P] [US1] Extend tests/Unit/Actions/SetFieldValuesTest.php: new wordings join the choice once, an existing wording reuses its item, a stopped wording writes nothing at all
- [X] T010 [P] [US1] Extend tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php: `new_choices` validation, a stopped wording returns as an error on `fields`
- [X] T011 [P] [US1] Extend tests/Feature/Views/Components/FieldInputTest.php: the offer and its live region render for a list accepting values, and are absent when `allows_user_values` is false

## Phase 4: User Story 2, show only the people of some categories (P1)

- [X] T012 [P] [US2] Add `scopeHoldingAnyCategory(Builder $query, array $itemIds)` to app/Models/Contact.php: a `whereExists` on `field_values` of the contact with one `orWhereJsonContains('value_list_item_ids', $id)` per id (research R3)
- [X] T013 [P] [US2] Add `categoriesHeldByContacts(ContactGroupingEnum $grouping): Collection` to app/Models/Vault.php: the items of the account's `contact_categories` list held by at least one contact of the vault in that grouping, active or stopped, ordered by position, each with `contacts_count` counted per contact in PHP from one query (FR-006)
- [X] T014 [US2] In app/ViewModels/Vaults/ContactsViewModel.php take `categoryIds`, apply T012 in `matching()` after `inGrouping`, expose T013's items with whether each is chosen, and keep `categories[]` on every grouping, sort and pagination link (FR-005)
- [X] T015 [US2] In app/Http/Controllers/App/Vaults/ContactController.php validate `categories` (`array`) and `categories.*` (an id of the account's `contact_categories` list), keep the query string on the canonical redirect, and pass the ids to the view model
- [X] T016 [US2] In resources/views/app/vaults/contacts/index.blade.php add a GET form: a fieldset with a legend, one `x-option-row chip` per offered category named `categories[]` with its count, and an Apply button
- [X] T017 [P] [US2] Extend tests/Unit/Models/ContactTest.php (any-of matching, none held) and tests/Unit/Models/VaultTest.php (counts per contact, stopped offered, other vault and unused items left out)
- [X] T018 [P] [US2] Extend tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php (filter combined with Favourites, links keep the filter) and tests/Feature/Controllers/App/Vaults/ContactControllerTest.php (an id outside the list refused, the redirect keeps `categories[]`)

## Phase 5: User Story 3, keep categories through vCard and CardDAV (P2)

- [X] T019 [US3] In app/Http/Dav/OwnedFields.php add `categoryFields()`, the Multiselect fields whose `valueList->key` is `contact_categories`. They stay out of `propertyFor()`, because `CATEGORIES` is one property listing every field's labels and the slot loop writes one value per property (research R4)
- [X] T020 [US3] In app/Http/Dav/VCardMerger.php write one `CATEGORIES` with the labels held by every such field, union in list position order, and none when nothing is held (FR-007)
- [X] T021 [US3] In app/Actions/ImportVCard.php give the parts of every `CATEGORIES` on the card to the first such field only, each through T003 with the field's held ids, skipping a part refused by `ValueListItemIsStopped` or `AuthorizationException`; amend the `labelOf()` docblock to name this exception
- [X] T022 [P] [US3] Extend tests/Unit/Http/Dav/VCardMergerTest.php and tests/Unit/Actions/ImportVCardTest.php: export, known and unknown parts, refused part skipped, card without `CATEGORIES` empties the field, structure without a Category field keeps `CATEGORIES` on the stored card

## Phase 6: User Story 4, import Monica 4 tags as categories (P2)

- [X] T023 [US4] In app/Actions/WritesMonicaFourItems.php (lines 276-294) give the tags to the contact's first Category field through T003 and `SetFieldValue`, write the "Tags" note only for tags refused or when no such field exists, and record the ids of items created (`wasRecentlyCreated`) in the contact item's payload under `value_list_items`, as `whatTheCopyMade()` records `value_lists` (app/Actions/WritesMonicaItems.php:698-702)
- [X] T024 [US4] In app/Actions/RollBackAccountImport.php read `value_list_items` from every item of the import (the ledger at line 231 reads only structure versions), and after `releaseTheChoicesOf()` delete those items whose `usage_count` is zero (FR-009)
- [X] T025 [P] [US4] Extend tests/Unit/Actions/WriteAccountImportItemTest.php (tags as categories, case-insensitive reuse, note fallback, no "Tags" note when the field exists) and tests/Unit/Actions/RollBackAccountImportTest.php (created and unused removed, pre-existing kept, still used kept)

## Phase 7: User Story 5, do all of it through the API (P3)

- [X] T026 [US5] Create app/Http/Controllers/Api/ContactSectionController.php `update`, mirroring the web controller with inline `FieldValueRules::for()` validation as app/Http/Controllers/Api/EntityController.php does, answering per [contracts/api.md](contracts/api.md), and add its route to routes/api.php, named `contacts.sections.update`
- [X] T027 [US5] In app/Http/Controllers/Api/ContactController.php `index` validate `categories[]` as T015 does and apply T012
- [X] T028 [US5] Create app/Http/Controllers/Api/ContactCategoryController.php `index` returning T013 in the `filter` grouping, and its route `vaults/{vault}/contact-categories` named `contactCategories`
- [X] T029 [P] [US5] Add `categories` to app/Http/Resources/ContactResource.php and the `categories` argument to app/Mcp/Tools/ListContacts.php
- [X] T030 [P] [US5] Write tests/Feature/Controllers/Api/ContactSectionControllerTest.php and tests/Feature/Controllers/Api/ContactCategoryControllerTest.php; extend tests/Feature/Controllers/Api/ContactControllerTest.php and tests/Unit/Mcp/Tools/ListContactsTest.php (filter, 422 on a foreign id, another account left out)
- [X] T031 [US5] Document the three API changes in the docs portal with the `api-docs-writer` skill

## Phase 8: Polish

- [X] T032 Run `php artisan monica:localize` and translate every new key in every locale, French typography included; then `php artisan monica:localize --check`
- [X] T033 Run `php artisan view:clear`, `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T034 Walk through quickstart.md, keyboard and screen reader included, screenshots with the `take-screenshot` skill, and the SC-002 timing
- [X] T035 Record the change with the `feature-documentator` skill in docs/FEATURES.md

## Dependencies

- Phase 1 blocks everything. Phase 2 blocks US1, US3, US4 and US5; US2 needs only Phase 1.
- US5 needs T006 (US1) and T012, T013 (US2). US3 and US4 are independent of each other and of US1 and US2.
- Tasks touching the same file run in order; `[P]` tasks touch files nobody else in the phase touches.

## Parallel examples

- Phase 2 beside Phase 4: T002 to T004 with T012, T013.
- After Phase 2: Phases 5 and 6 together.
- Inside a phase: the `[P]` test tasks together once their implementation tasks are done.

## Implementation strategy

Phases 1 to 3 are the MVP: categories created from the contact. Phase 4 makes them useful, Phases 5
and 6 carry them in and out, and Phase 7 completes the API. Phase 8 closes the pull request.
