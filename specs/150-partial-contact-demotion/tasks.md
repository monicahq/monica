---
description: "Tasks for making a full contact partial again"
---

# Tasks: Make a full contact partial again

**Input**: Design documents from `specs/150-partial-contact-demotion/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md), [analysis.md](analysis.md)

**Tests**: included. Constitution principle III makes them a delivery condition, and the plan lists them. Follow `.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case names starting with `it_`, named arguments, The Office for data, no comments in tests.

**Context for whoever runs these tasks**: the branch already holds the opposite move, uncommitted: `app/Actions/PromotePartialContact.php`, `app/Http/Controllers/App/Vaults/ContactPromotionController.php`, the route `contacts.promotion.create`, `ContactViewModel::promotion()`, `resources/views/app/vaults/contacts/_promotion.blade.php`, a `banner` slot on `resources/views/layouts/shell.blade.php`, the journal case `ContactPromoted`, and a changelog entry `changelog/2026-11-23-01M84AD3CSZGR4KXK8PWJ5Y54C-partial-contact-promotion.md`. Read them before starting; several tasks change them.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story of spec.md the task serves

---

## Phase 1: Setup

**Purpose**: nothing to install; confirm the starting point.

- [X] T001 Run `php artisan test --compact tests/Unit/Actions/PromotePartialContactTest.php tests/Feature/Controllers/App/Vaults/ContactPromotionControllerTest.php` and confirm the promotion built earlier on the branch passes before anything changes

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the journal case, the policy ability, the demotion action, and the single route both directions share. Every story depends on these.

- [X] T002 [P] Add `case ContactDemoted = 'contact_demoted';` after `ContactPromoted` in `app/Enums/UserActionEnum.php`, with the description `'Made :name a partial contact'` in `description()`
- [X] T003 [P] Add `public function demote(User $user, Contact $contact): Response` to `app/Policies/ContactPolicy.php`: `Response::deny(__('This is the contact that represents you, and it stays a full contact.'))` when `$contact->is_me`, `Response::allow()` otherwise (research R3)
- [X] T004 [P] In `tests/Unit/Policies/ContactPolicyTest.php`, add three tests naming `demote`: permitted for an ordinary contact, refused for the `is_me` contact, refused with nobody signed in (the name must contain `nobody_is_signed_in`, which `tests/Unit/Policies/PolicyCoverageTest.php` requires)
- [X] T005 Create `app/Actions/DemoteFullContact.php` on the model of `app/Actions/PromotePartialContact.php`: constructor `private readonly User $user, private readonly Contact $contact`; `execute(): Contact` runs `validate()`, `demote()`, `log()`. `validate()` throws `ModelNotFoundException('This contact is in another account.')` for another account, calls `$this->user->authorize('demote', $this->contact)`, throws `InvalidArgumentException(__(':name is already a partial contact.', ['name' => ...]))` when `is_partial`, and `InvalidArgumentException` when `is_archived`. `demote()` sets `is_partial = true` and saves; no lock (research R5). `log()` dispatches `LogUserAction` with `UserActionEnum::ContactDemoted`, `parameters: ['name' => $this->contact->name]`, `loggable: $this->contact`. Class docblock: one sentence
- [X] T006 Create `tests/Unit/Actions/DemoteFullContactTest.php`: it demotes a contact and journals `ContactDemoted` on the `low` queue (assert the parameters as `FavouriteContactTest` does); it leaves the favourite mark, the name and a field value untouched; it lowers `Account::contactCount()` by one; it refuses the `is_me` contact with `AuthorizationException`; it refuses a partial contact and an archived one with `InvalidArgumentException`; it refuses a contact of another account with `ModelNotFoundException`
- [X] T007 Create `app/Http/Controllers/App/Vaults/ContactPartialController.php` with `update(Request $request, Vault $vault, Contact $contact): RedirectResponse`: `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`; `$request->validate(['partial' => ['required', 'boolean']])`; call `DemoteFullContact` when `partial` is true, `PromotePartialContact` otherwise; catch `AuthorizationException|InvalidArgumentException` and return `back()->withErrors(['partial' => $e->getMessage()])`; on success redirect to `contacts.show` with `status` ":name is a partial contact." and `status_description` "They are under Names only in the list of people.", or the existing promotion wording (contracts/routes.md)
- [X] T008 In `routes/web.php`, replace the `contacts.promotion.create` route with `Route::put('vaults/{vault}/contacts/{contact}/partial', [ContactPartialController::class, 'update'])->name('contacts.partial.update');`, swap the `use` line, and delete `app/Http/Controllers/App/Vaults/ContactPromotionController.php` (research R1)
- [X] T009 Point `ContactViewModel::promotion()['url']` in `app/ViewModels/Vaults/ContactViewModel.php` at `contacts.partial.update`, and in `resources/views/app/vaults/contacts/_promotion.blade.php` give the `x-modal.form` `method="put"` and a hidden input `partial` with value `0`; rename its `@error('promotion')` to `@error('partial')`
- [X] T010 Move `tests/Feature/Controllers/App/Vaults/ContactPromotionControllerTest.php` to `tests/Feature/Controllers/App/Vaults/ContactPartialControllerTest.php` and rewrite it for `PUT contacts.partial.update`: making partial redirects with its flash; making full redirects with its flash; a missing `partial` is a validation error; one refusal (the `is_me` contact) reaches the form under `partial`; through another vault of the account answers 404. Update the URL assertion in `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php` (`a_partial_contact_is_offered_the_promotion`)

**Checkpoint**: both directions work through one route; the promotion band still works.

---

## Phase 3: User Story 1, make a full contact partial from the edit page (P1) MVP

**Goal**: the panel of design 2a above the delete section, and the confirmation of design 2b.

**Independent Test**: on a full contact's edit page, confirm the panel's dialog; the contact is partial, under Names only, one lower in the plan's count, with everything it held.

- [X] T011 [US1] Add `demotion(): ?array` to `app/ViewModels/Vaults/ContactEditViewModel.php`, returning null when the contact is partial, else `{title, lead, lines: list<string>, url, confirmTitle, confirmBody, reachabilityLine: string|null, refusal: string|null}` with the copy of research R7; `url` is `contacts.partial.update`; `refusal` comes from `$this->user->inspect('demote', $this->contact)`; `reachabilityLine` is set only when no `Relationship` has the contact as `contact_a_id` or `contact_b_id` (research R6). Docblock: the `@return` array shape only
- [X] T012 [US1] In `tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php`, test `demotion()`: present for a full contact with the URL; null for a partial contact; carries the refusal for the `is_me` contact; carries the reachability sentence for a contact without a relationship and none for a contact holding one
- [X] T013 [US1] Create `resources/views/app/vaults/contacts/_demotion.blade.php` (first line a one line `{{-- --}}` comment, then the `@var` of `$demotion`): an `x-panel` with the profile icon, the title, the lead, the three lines with the icons `people`, `check`, `arrow-right`, and a secondary button opening `x-modal.form` (`method="put"`, hidden `partial` = `1`, `:title`, `:description` = `confirmBody`, the reachability sentence in the slot when present, `:confirm="__('Make a partial contact')"`). When `refusal` is set, draw the button with `aria-disabled="true"` and `aria-describedby` pointing at the refusal text instead of the dialog. Show `@error('partial')` with `role="alert"`. Read `x-panel`, `x-modal.form` and `x-delete-section` before writing (`.claude/rules/components.md`)
- [X] T014 [US1] Include it in `resources/views/app/vaults/contacts/edit.blade.php`, just above `<x-delete-section`, as `@if ($demotion = $viewModel->demotion()) @include('app.vaults.contacts._demotion', ['demotion' => $demotion]) @endif`
- [X] T015 [US1] In `tests/Feature/Controllers/App/Vaults/ContactPartialControllerTest.php`, add one test that the edit page of a full contact shows "Keep :name as a partial contact" and the `contacts.partial.update` URL

**Checkpoint**: User Story 1 is usable on its own.

---

## Phase 4: User Story 2, undo it at any time, within the ceiling (P1)

**Goal**: every way back to full respects the plan (FR-006, FR-013).

**Independent Test**: on a free account at its ceiling, merging two partial contacts is refused; below it, the merge makes the result full.

- [X] T016 [US2] In `app/Actions/MergeContacts.php`, inside the transaction and before `stopBeingPartial()` runs, when both `$this->kept->is_partial` and `$this->absorbed->is_partial` are true: `Account::query()->whereKey($this->user->account_id)->lockForUpdate()->first();` then `$this->user->authorize('create', Contact::class);` (research R4). Put the check in its own private step called before the work begins in the closure, so that a refusal moves nothing
- [X] T017 [US2] In `tests/Unit/Actions/MergeContactsTest.php`, add: merging two partial contacts with `config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 1])` and one full contact already counted throws `AuthorizationException`, and both contacts are untouched; below the ceiling, the kept contact ends full
- [X] T018 [P] [US2] Catch `AuthorizationException` in `create()` of `app/Http/Controllers/App/Vaults/ContactMergeController.php`, answering as its other refusals do, and in `create()` of `app/Http/Controllers/Api/ContactMergeController.php` with a 422 carrying the message
- [X] T019 [P] [US2] Add one test each to `tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php` and `tests/Feature/Controllers/Api/ContactMergeControllerTest.php` proving the ceiling refusal reaches the form and the API

**Checkpoint**: the ceiling holds on every way back.

---

## Phase 5: User Story 3, the same through the API and an assistant (P2)

**Goal**: `PUT /api/v1/vaults/{vault}/contacts/{contact}/partial` and the MCP tool `SetContactPartial` (FR-008).

**Independent Test**: call the endpoint both ways and check the resource's `is_partial`, one refusal as 422.

- [X] T020 [US3] Create `app/Http/Controllers/Api/ContactPartialController.php` on the model of `app/Http/Controllers/Api/ContactFavouriteController.php`: resolve vault and contact by address, validate `['partial' => ['required', 'boolean']]`, call `DemoteFullContact` or `PromotePartialContact`, catch `AuthorizationException|InvalidArgumentException` into a 422 with `message` (read how the other API controllers shape a 422), return the `ContactResource` with status 200
- [X] T021 [US3] Register `Route::put('vaults/{vault}/contacts/{contact}/partial', [ContactPartialController::class, 'update'])->name('contacts.partial.update');` beside the favourite route in `routes/api.php` (the group prefixes the name with `api.v1.`)
- [X] T022 [US3] Create `tests/Feature/Controllers/Api/ContactPartialControllerTest.php` on the model of `tests/Feature/Controllers/Api/ContactFavouriteControllerTest.php`: both directions with `assertJsonPath('data.attributes.is_partial', ...)` (check the real path in `ContactResource`), the shared `$jsonStructure`, one refusal (`is_me`) as 422, a missing `partial` as 422 with `assertJsonValidationErrors('partial')`
- [X] T023 [P] [US3] Create `app/Mcp/Tools/SetContactPartial.php` on the model of `app/Mcp/Tools/SetContactFavourite.php`, `$mirrors = 'api.v1.contacts.partial.update'`, schema `vault`, `contact`, `partial` ("True to make them a partial contact, false to make them a full one."), and register it beside `SetContactFavourite` in `app/Mcp/Servers/MonicaServer.php`. No test file of its own (`.claude/rules/mcp.md`)
- [X] T024 [P] [US3] Document the endpoint in `resources/docs/api/06-contacts.php` and the merge's new refusal, and add an entry in `resources/docs/api-changelog/2026-11-23.php` (it does not exist yet: create it on the model of `2026-11-22.php`), following the `api-docs-writer` skill

**Checkpoint**: the API and the assistant do what the screen does.

---

## Phase 6: User Story 4, the journal says what happened (P3)

**Goal**: both directions appear in the account's journal (FR-007).

**Independent Test**: covered by T006 and the existing `PromotePartialContactTest`, which assert the dispatched `LogUserAction`.

- [X] T025 [US4] Open the account's journal screen after making a contact partial and full (quickstart step 3 and 4) and confirm both lines read as written in `UserActionEnum`; no code change expected

---

## Phase 7: Polish and cross-cutting concerns

- [X] T026 [P] Correct the wrong sentences (research R8): `app/Http/Controllers/App/Vaults/ContactController.php:188` and `app/ViewModels/Vaults/ContactsViewModel.php:268` stop saying "until they are more than a first name"; the `is_partial` description in `app/Mcp/Tools/CreateContact.php:52` stops saying "until they are more than a first name"
- [X] T027 [P] Add to `tests/Unit/Http/Dav/CollectionChangesTest.php` a test that a contact made partial after the sync token lands in the removed set (research R9)
- [X] T028 [P] Update the product guide, following the `writer` skill: `docs/portal/en/3-people/2-add-a-person.md` (how to turn a partial contact full now names the band, and a new short section on making a full contact partial from the edit page, saying it leaves phones and the birthday calendar), `docs/portal/en/7-keeping-records-tidy/3-merge-two-contacts.md` (the ceiling when both are partial), `docs/portal/en/13-reference/2-glossary.md` (the definition no longer says "with a name only"). Check `docs/portal/en/3-people/4-find-people.md:30` too
- [X] T029 Rewrite `changelog/2026-11-23-01M84AD3CSZGR4KXK8PWJ5Y54C-partial-contact-promotion.md` to cover both directions (keep its `id`; it is not merged), following the `changelog` skill
- [X] T030 Run `php artisan monica:localize`, then translate every missing string in every locale with Sonnet subagents as the `translations` skill requires (brief: `.claude/rules/translations.md`, the glossary, reuse each locale's wording for "partial contact" and "full contact" from this branch); then `php artisan monica:localize --check` must pass
- [X] T031 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, and `composer test:parallel`. `BillingControllerTest` fails locally on `.env` `MONICA_FREE_VAULT_LIMIT=2`, unrelated; every other test must pass
- [X] T032 Capture the edit page with its panel and the account holder's refused panel with Iris (the `take-screenshot` skill); the example account holds no partial contact, so render inside a rolled back transaction as done earlier on the branch
- [X] T033 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change, check their evidence, fix what holds
- [X] T034 Run the `definition-of-done` skill and report each of its items

---

## Dependencies and execution order

- Phase 1, then Phase 2 (T002, T003, T004 in parallel; T005 after T002 and T003; T006 after T005; T007 after T005; T008 after T007; T009 after T008; T010 after T009).
- US1 (T011 to T015) after Phase 2.
- US2 (T016 to T019) after Phase 2, independent of US1.
- US3 (T020 to T024) after Phase 2, independent of US1 and US2. T024 documents the merge refusal, so it reads T018.
- US4 (T025) after US1.
- Polish after the stories it touches: T026, T027, T028 any time after Phase 2; T029 and T030 after every copy change; T031 to T034 last.

## Parallel examples

- Phase 2: T002, T003 and T004 touch three different files.
- US2: T018 and T019 once T016 and T017 are done.
- US3: T023 and T024 while T020 to T022 are written.
- Across stories: US1, US2 and US3 can be given to three people once Phase 2 is done.

## Implementation strategy

1. MVP: Phase 1, Phase 2, US1. A person can make a contact partial and back, on the screen.
2. US2 closes the ceiling hole that the MVP makes repeatable (analysis D1). Ship it with the MVP, not after.
3. US3 brings the API and MCP, required by the constitution before merge.
4. Polish, translations and the definition of done before the pull request.
