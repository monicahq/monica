# Tasks: Bulk actions on the contact list

**Input**: `specs/162-contact-bulk-actions/` (spec.md, plan.md, research.md, contracts/)

**Tests**: required by constitution principle III; one layer each (`.claude/rules/testing.md`). No test of
how anything looks. Data from The Office.

**Design**: `People.dc.html`, claude.ai/design project `00fa6920-3a94-478a-b273-a9c3923da137`. Read it again
with `DesignSync get_file` before T008 if anything is unclear. Accepted departures: research R3, R6, R8.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/actions/SKILL.md`, `controllers`, `routes`, `view-models`, `views`, `components`, `api-writer`, `api-docs-writer`, `mcp-development`, `help-bubbles`, `writer`, `changelog` skills, and `.claude/rules/components.md`, `mcp.md`, `policies.md`, `testing.md` before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 In `app/ViewModels/Vaults/ContactsViewModel.php`, add `uuid` to `rowFor()` (and its array shape), and `selectionWordings(): array` returning `CountedPhraseWordings::of(':count selected|:count selected')` (research R5); extend `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` for the `uuid` of a row

## Phase 3: User Story 1, select several people (P1)

**Goal**: ticking rows swaps the filters for the toolbar. **Independent test**: tick three rows, read "3 selected", clear.

- [X] T003 [US1] Create `resources/js/contact-selection.js`, registered as `Alpine.data('contactSelection', …)` in `resources/js/app.js`: state `selected` (list of uuids), `toggle(uuid)`, `toggleAll(uuids)`, `clear()`, getters `count`, `allChecked`, `someChecked`, `label` (the wording `Intl.PluralRules(config.locale).select(count)` picks from `config.wordings`, `:n` replaced, as `reminder-interval.js` does). Escape clears. Rows of the page are read from `[data-contact-uuid]` under the component's own marker (`.claude/rules/components.md`, `$el` and `$root`)
- [X] T004 [US1] In `resources/views/app/vaults/contacts/index.blade.php`, put `x-data="contactSelection(@js([...]))"` on `#contacts` (re-rendered by every filter, search, sort and page change, which empties the selection: FR-002); a first `<th>` with a `role="checkbox"` button, `aria-checked` true, false or `mixed`, labelled `__('Select everybody on this page')`; the filters and search wrapped in `x-show="count === 0"`; a `role="toolbar"` labelled `__('Actions for the selected people')` in `x-show="count > 0"` with the count in `aria-live="polite"`, the two action slots filled by US2 and US3, a divider and an `x-icon-button` labelled `__('Clear the selection')`. Animations from the design behind `motion-safe:` only
- [X] T005 [US1] In `resources/views/app/vaults/contacts/_groups.blade.php`, add a first cell per row with a checkbox button labelled `__('Select :name', ['name' => …])`, `data-contact-uuid`, `aria-checked`; the row marked `aria-selected`; while `count > 0`, a click on the row toggles it instead of following the link (FR-004); widen the group heading `colspan` by one
- [X] T006 [US1] Create `tests/Feature/Views/ContactSelectionTest.php`: the list renders the heading checkbox with its label, one row checkbox named after each person, the toolbar with its `role` and label, and `x-data="contactSelection("`

## Phase 4: User Story 2, delete the selected people (P1)

**Goal**: delete several people after one confirmation. **Independent test**: select three, delete, confirm, three gone.

- [X] T007 [P] [US2] Create `app/Actions/DestroyContacts.php` (research R1): constructor `User $user, Vault $vault, array $contacts` (`list<Contact>`), `execute(): array{deleted: list<Contact>, kept: list<array{contact: Contact, reason: string}>}`. Refuses with `InvalidArgumentException` over 500 contacts; `ModelNotFoundException` when the vault or a contact is outside the account or the vault. Runs `DestroyContact` per contact; an `AuthorizationException` puts the contact in `kept` with `$e->getMessage()`
- [X] T008 [US2] Create `tests/Unit/Actions/DestroyContactsTest.php`: three deleted, each with its `ContactDeletion` log entry and revoked share link; the Me contact kept with the policy's sentence while the others go; another account's contact refused; 501 refused (depends on T007)
- [X] T009 [US2] Create `app/ViewModels/Vaults/ContactDeletionViewModel.php` (research R4, R8): `title()` is `__('Delete :name?')` for one, `trans_choice('Delete :count person?|Delete :count people?', n)` otherwise; `names()` one name, up to three joined, or two names and `trans_choice(':count other|:count others', n - 2)`; `consequence()` `__('Everything written on the record is deleted with them.')`; `keptLine()` naming each contact `$user->inspect('delete', …)` refuses, with its reason, or null; `confirm()` `__('Delete the contact')` for one, `trans_choice('Delete :count person|Delete :count people', n)` otherwise; `action()` `route('contacts.deletion.destroy', $vault)`; `uuids()`
- [X] T010 [P] [US2] Create `tests/Unit/ViewModels/Vaults/ContactDeletionViewModelTest.php`: one, three and five people; the Me contact named in `keptLine()`; nobody deletable leaves only the kept line
- [X] T011 [US2] Create `app/Http/Controllers/App/Vaults/ContactDeletionController.php`: `show()` validates `contacts` (array, 1 to 500, each a string), loads them with `Contact::query()->where('vault_id', $vault->id)->whereIn('uuid', …)`, `abort_unless` the vault is the user's, renders `app.vaults.contacts._deletion`; `destroy()` validates the same, runs `DestroyContacts`, redirects to `contacts.index` with `status` `trans_choice(':count person is deleted.|:count people are deleted.', n)` and, when someone was kept, `status_description` naming them
- [X] T012 [US2] Add both routes in `routes/web.php` before `contacts.show` (contracts/web.md): `contacts.deletion.show`, `contacts.deletion.destroy`
- [X] T013 [US2] Create `resources/views/app/vaults/contacts/_deletion.blade.php`: an `x-modal.warning` open on arrival, title, names, consequence, kept line, a hidden `contacts[]` per uuid in the confirm form. In `index.blade.php`, the toolbar's Delete is a GET form to `contacts.deletion.show` with a hidden `contacts[]` per selected uuid (`<template x-for>`), `x-target` on a `#contact-deletion` placeholder
- [X] T014 [US2] Create `tests/Feature/Controllers/App/Vaults/ContactDeletionControllerTest.php`: `show` answers with the title; `destroy` redirects with the status; 501 refused by validation; a contact of another vault of the same account answers not found
- [X] T015 [P] [US2] Create `app/Http/Controllers/Api/ContactDeletionController.php` and route `api.contacts.deletion.destroy` (`DELETE vaults/{vault}/contacts`, contracts/api.md), contacts addressed with `addressedBy()` as `Api\ContactController::contact()` does; answers `{deleted, kept}`; test in `tests/Feature/Controllers/Api/ContactDeletionControllerTest.php` (200 shape, 422 over 500, 422 on another vault's contact, another account's vault 404) (depends on T007)
- [X] T016 [US2] Create `app/Mcp/Tools/DeleteContacts.php` mirroring `api.contacts.deletion.destroy` through `answerThroughApi()`, destructive like `DeleteContact`; register it in `app/Mcp/Servers/MonicaServer.php` (depends on T015)

## Phase 5: User Story 3, change the category of the selected people (P2)

**Goal**: file several people under one category, with Undo. **Independent test**: select four, Work, four badges.

- [X] T017 [P] [US3] Create `app/Actions/ChangeContactCategories.php` (research R1, R2): constructor `User $user, Vault $vault, array $assignments` (`list<array{contact: Contact, categories: list<ValueListItem>}>`), `execute(): array{changed: list<Contact>, unchanged: list<array{contact: Contact, reason: string}>, previous: array<string, list<int>>}`. Refuses over 500, another account or vault, an item off the account's "Contact categories" list. Per contact, `previous` from `heldCategories(true)`; fields from `new OwnedFields($contact)->categoryFields()`; none: unchanged, `__('Their template has no category field.')`; empty categories on a required field: unchanged, `__('Their category is required.')`; otherwise the first field gets `SetFieldValue(valueListItems: …)` or `DestroyFieldValue`, the others are emptied
- [X] T018 [US3] Create `tests/Unit/Actions/ChangeContactCategoriesTest.php`: Work replaces Family and Friends; empty removes; a contact without the field and a required field left unchanged with their reason; `previous` holds what was there; one `LogUserAction` per written value; an item of another list refused; 501 refused (depends on T017)
- [X] T019 [US3] Create `app/Http/Controllers/App/Vaults/ContactCategoryController.php` `update()`: validates `contacts` (1 to 500) with `contacts.*.contact` string and `contacts.*.categories.*` integer, resolves both inside the vault and account, runs the action, redirects to `contacts.index` flashing `contactCategoryChange` `{line, unchangedLine, previous}`; `line` is `trans_choice(':count person moved to :category|:count people moved to :category', n)` or `trans_choice('Category removed from :count person|Category removed from :count people', n)`; route `contacts.categories.update` in `routes/web.php`
- [X] T020 [US3] In `ContactsViewModel`, add `menuCategories()` (the account's active "Contact categories" items, id and label, in position order) and `categoryChange()` reading the flash into the notice line, the unchanged line and the Undo form's `contacts[i][contact]`, `contacts[i][categories][]` pairs; constructor takes `?array $categoryChange`, passed by `ContactController::index()`. Tests in `ContactsViewModelTest`
- [X] T021 [US3] In `index.blade.php`, the toolbar's "Change category" is an `x-actions-menu` whose items are submit buttons of one PUT form to `contacts.categories.update`, each carrying its category id, then a divider and "Remove category"; hidden pairs per selected uuid via `<template x-for>`. When the list has no category, the trigger stays with `aria-disabled="true"` and `aria-describedby` on `__('Your list of categories is empty.')` (`.claude/rules/policies.md`). Above the list, the `x-notice` with Undo as in `resources/views/app/vaults/groups/index.blade.php`
- [X] T022 [US3] Create `tests/Feature/Controllers/App/Vaults/ContactCategoryControllerTest.php`: redirect with the flash; the Undo form posted back restores the previous categories; 501 refused; another vault's contact not found
- [X] T023 [P] [US3] Create `app/Http/Controllers/Api/ContactCategoryController.php` and route `api.contacts.categories.update` (contracts/api.md), answering `{changed, unchanged}`; test in `tests/Feature/Controllers/Api/ContactCategoryControllerTest.php` (depends on T017)
- [X] T024 [US3] Create `app/Mcp/Tools/SetContactCategories.php` mirroring `api.contacts.categories.update`; register it in `MonicaServer` (depends on T023)

## Phase 6: Polish

- [X] T025 [P] Document both endpoints under `resources/docs/api/` next to the contact endpoints, and add `resources/docs/api-changelog/2026-11-25.php` (`api-docs-writer` skill)
- [X] T026 [P] Add a "Select several people" part to `docs/portal/en/3-people/4-find-people.md` (`writer` skill)
- [X] T027 [P] Write the What's new entry `changelog/2026-11-25-<ulid>-contact-bulk-actions.md` with a screenshot of the toolbar (`changelog` skill)
- [X] T028 Run `php artisan monica:localize`, then translate every new string in every locale through Sonnet subagents (`translations` skill)
- [X] T029 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff; fix what they confirm
- [X] T030 Run the `definition-of-done` skill

## Dependencies

- T002 blocks every story. US1 (T003 to T006) blocks the toolbar slots of US2 (T013) and US3 (T021).
- The two actions (T007, T017) need only T001; their tests and API controllers follow them.
- Phase 6 after the stories; T028 after every wording exists; T030 last.

## Parallel examples

- After T001: T007 and T017 together; then T008, T015, T018, T023 together.
- In Phase 6: T025, T026, T027 together.

## Strategy

MVP is US1 plus US2: selection and deletion. US3 then adds the category on the same toolbar.
