---
description: "Task list for the Relationships empty state"
---

# Tasks: Relationships empty state

**Input**: Design documents from `specs/044-relationships-empty-state/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/relationships-tab.md

**Tests**: Required. Constitution principle III: no change ships without a test covering the
behaviour it adds. Tests follow `.claude/rules/testing.md` (strict types, `#[Test]`, snake_case,
named arguments, The Office data, no comments, no class string or markup assertion).

**Organization**: grouped by user story. US1 is the empty tab, US2 is the per-category Add.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story the task belongs to

---

## Phase 1: Setup

No setup. No dependency, migration or configuration changes (plan.md, Technical Context).

---

## Phase 2: Foundational

**Purpose**: the shape both stories read. Blocks US1 and US2.

- [X] T001 In `app/ViewModels/Vaults/RelationshipTypeChoices.php`, add `'key' => $category->key` to each group and `'name' => $type->name` to each type in `groups()`; widen the `@return` shape of `groups()` to `array<int, array{key: string|null, label: string, types: array<int, array{value: int, label: string, name: string, roleA: string, roleB: string, isSymmetric: bool}>}>`, and the matching `@return` of `typeGroups()` in `app/ViewModels/Vaults/RelationshipFormViewModel.php`
- [X] T002 In `tests/Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php`, add `it_says_which_supplied_category_a_group_is` (a supplied category's key, and `null` for one the account added) and `it_carries_the_name_of_each_type` (the name as the settings screen lists it, translated for a supplied type)

**Checkpoint**: `php artisan test --compact tests/Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php` passes, and `resources/views/app/vaults/contacts/relationships/form.blade.php` renders unchanged.

---

## Phase 3: User Story 1 - The empty tab shows where links will go (Priority: P1) MVP

**Goal**: a contact with no relationship sees one row per category the account offers, in the
account's order, each naming its types (spec FR-001, FR-002, FR-003, FR-006, FR-007, FR-008).

**Independent Test**: open the Relationships tab of a contact with no relationship and read the rows
(quickstart.md steps 1, 3 and 6).

### Tests for User Story 1

- [X] T003 [P] [US1] In `tests/Unit/ViewModels/Vaults/RelationshipsBlockTest.php`, add `it_lists_the_categories_the_account_offers_in_its_order` (reordered categories come back in the new order; a category added by the account is included), `it_leaves_out_a_category_that_offers_nothing` (switched off, or whose types are all switched off), `it_names_up_to_four_types_under_a_category` (a category with five active types names the first four by position, joined by `, `), `it_marks_each_supplied_category_with_its_own_icon` (`family` → `family`, `romantic` → `heart`, `friendship` → `people`, `professional` → `organization`, `other` and a `null` key → `relationship`), and `it_links_to_the_relationship_types_settings` (`settingsUrl()` is `route('settings.relationshipTypes.index')`)
- [X] T004 [P] [US1] In `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`, add one test `it_shows_where_the_relationships_will_go_when_there_are_none`: GET `contacts.show` with `page=relationships` for Michael Scott with no relationship answers 200 and shows each category name and "Add to Family"; and `it_points_to_the_settings_when_no_relationship_type_is_offered`: with every category switched off, the tab shows "Nobody is written down as connected to Michael yet." and the link `route('settings.relationshipTypes.index')`

### Implementation for User Story 1

- [X] T005 [US1] In `app/ViewModels/Vaults/RelationshipsBlock.php`, add `emptyCategories(): array` returning `array<int, array{label: string, icon: string, line: string, addUrl: string, addLabel: string}>`, built from `new RelationshipTypeChoices(accountId: $this->contact->account_id)` held in a private `once()` method; `line` is `implode(__(', '), ...)` of the first four `name`s, `icon` is a `match` on `key`, `addLabel` is `__('Add to :category', ['category' => $group['label']])`, and `addUrl` is `route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->contact, 'type' => <id of the group's first type>])`. Add `settingsUrl(): string` returning `route('settings.relationshipTypes.index')`. Leave `emptyLine()` as it is
- [X] T006 [US1] In `resources/views/app/vaults/contacts/_relationships.blade.php`, replace the `holdsNothing()` branch: when `emptyCategories()` is not empty, draw `<x-panel :flush="true">` with one `<x-row :icon :title :description>` per category and, in its `end` slot, `<x-button.ghost :href="$category['addUrl']" data-turbo="true" size="small" aria-label="{{ $category['addLabel'] }}">` with the `plus` icon and the text `{{ __('Add') }}`; otherwise keep today's `x-panel` with `emptyLine()` and replace its Add button with `x-button.secondary` to `settingsUrl()` reading `{{ __('Set up relationship types') }}`. No panel title and no family tree in either case. Read `resources/views/components/row.blade.php` and `panel.blade.php` first (`.claude/rules/components.md`)

**Checkpoint**: T003 and T004 pass; the filled tab is unchanged (existing `RelationshipsBlockTest` and `FamilyTreeTest` still pass).

---

## Phase 4: User Story 2 - Add starts from the chosen category (Priority: P1)

**Goal**: pressing Add opens the add form with the category's first type chosen, and a type the
form does not offer is ignored (spec FR-004, FR-005; contracts/relationships-tab.md).

**Independent Test**: press Add on a row and check which type the form has chosen (quickstart.md
steps 2 and 4).

### Tests for User Story 2

- [X] T007 [P] [US2] In `tests/Unit/ViewModels/Vaults/RelationshipFormViewModelTest.php`, add `it_chooses_the_type_it_was_opened_with` (`values()['typeId']` and `projection()['typeId']` equal the preselected id), `it_ignores_a_type_it_does_not_offer` (another account's, a switched off one, one in a switched off category, and `'abc'` all give `''`), and `it_keeps_what_was_submitted_over_the_type_it_was_opened_with` (after `session()->flashInput(['relationship_type_id' => ...])`, the old value wins)
- [X] T008 [P] [US2] In `tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php`, add `it_opens_the_form_with_the_type_it_was_asked_for`: GET `contacts.relationships.new` with `type=<id>` answers 200 and the `<option>` of that type is `selected`; and GET with `type=999999` answers 200

### Implementation for User Story 2

- [X] T009 [US2] In `app/ViewModels/Vaults/RelationshipFormViewModel.php`, add the constructor parameter `private readonly string $preselectedTypeId = ''` after `$query`; in `values()`, default `typeId` to it only when `array_key_exists((int) $this->preselectedTypeId, $this->typeMap())`, otherwise `''`, and keep `old('relationship_type_id', ...)` on the outside so a submitted value wins
- [X] T010 [US2] In `app/Http/Controllers/App/Vaults/ContactRelationshipController.php`, in `new()`, pass `preselectedTypeId: (string) $request->string('type')` to `RelationshipFormViewModel`. Leave `create()`, its validation and `edit()` unchanged

**Checkpoint**: T007 and T008 pass; the existing controller and form view model tests still pass.

---

## Phase 5: Polish & cross-cutting

- [X] T011 Run `php artisan monica:localize`, translate every new string ("Add", "Add to :category", "Set up relationship types") in every configured locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T012 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, and fix what they report
- [X] T013 Run the whole suite with `php artisan test --compact`, since `_relationships.blade.php` and `RelationshipTypeChoices` feed several screens; the known failure `it_opens_a_contact_named_in_another_alphabet_written_either_way` predates this feature
- [X] T014 Walk through `specs/044-relationships-empty-state/quickstart.md` and take a screenshot of the empty tab with the take-screenshot skill, written to `/tmp/relationships-empty-state.png`

---

## Dependencies & execution order

- **Foundational (T001–T002)** blocks both stories: US1 reads `key` and `name`, US2 reads the same groups through `typeMap()`.
- **US1 (T003–T006)** and **US2 (T007–T010)** are independent of each other once T001 is done. US1's Add links carry `?type=`, which works (as a no-op) before US2 lands, and chooses the type once it does.
- **Polish (T011–T014)** after both stories.

Within a story: tests first, then the view model, then the view or controller.

## Parallel opportunities

- T003 and T004 (different test files).
- T007 and T008 (different test files).
- After T001, the US1 phase and the US2 phase can be worked side by side: they touch different files, except that both read `RelationshipTypeChoices`, which only T001 changes.

```text
After T001:
  US1: T003 ∥ T004 → T005 → T006
  US2: T007 ∥ T008 → T009 → T010
```

## Implementation strategy

1. **MVP**: T001–T006. The empty tab shows the account's categories; each Add opens the form, with
   nothing chosen yet.
2. **Increment**: T007–T010. Add chooses the category's first type.
3. **Finish**: T011–T014, then the pull request with the screenshot.
