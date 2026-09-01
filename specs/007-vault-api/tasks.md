---

description: "Task list for the vault endpoints"
---

# Tasks: Vault endpoints

**Input**: Design documents from `specs/007-vault-api/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/vaults.md](contracts/vaults.md)

**Tests**: Included, and not optional here. Constitution Principle III makes a test a condition of
merge, and the checklist in `.claude/CLAUDE.md` repeats it. Test tasks sit in the phase of the story
they prove.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with the other `[P]` tasks in the same phase (different files, no
  dependency between them)
- **[Story]**: Which user story the task belongs to (US1 to US5)
- Every task names the exact file it touches

## What couples the five stories

The spec has five stories at five priorities, and they are increments on three files rather than five
separable pieces of work. Four things in the codebase decide that, all found during Phase 1 design:

1. **The self link.** Every resource in this API carries `links.self` pointing at its own show
   endpoint, so `VaultResource` calls `route('api.v1.vaults.show', ...)`, which throws unless that
   route is registered. No endpoint can render a vault until the show route exists. It therefore lands
   in US1's phase, and US2's phase is what proves it.
2. **The reference sync test.** `ApiDocsViewModelTest` compares documented endpoints against
   `routes/api.php` in **both** directions. A route with no documentation fails the suite, and so does
   documentation for a route that does not exist. Every phase that adds a route adds its reference
   section in the same phase, or it ends on a red suite, which Principle III does not allow.
3. **One controller, one test file, one reference file.** All five endpoints live in
   `app/Http/Controllers/Api/VaultController.php`, all their tests in one class, all their
   documentation in one PHP file. Two people cannot work two stories without editing the same three
   files, so there is almost no parallel work in this feature. It is recorded here rather than papered
   over with `[P]` markers that would be wrong.
4. **US5 modifies what US1 and US3 built.** The two account level decisions are fields on the two
   write endpoints, not endpoints of their own. US5 is therefore the last increment on `create` and
   `update` rather than new code beside them.

**No new domain code.** Every operation already exists under `app/Actions` with its own test, listed in
[research.md](research.md) section 1. Nothing in this task list creates an action, a model, a migration
or a factory state.

---

## Phase 1: Setup

**Purpose**: Know the branch was green before anything moved.

- [X] T001 Run `php artisan test --compact` from the repository root and confirm the suite passes before any edit, so a later failure is known to belong to this branch

**No migration, no dependency, no configuration.** The `vaults` table is already migrated,
`Account::vaults()` already exists (`app/Models/Account.php:122`), `VaultFactory` already has a
`default()` state, and both page size bounds are already in `config/monica.php`. There is nothing to
initialize.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The one shape every endpoint answers with.

**⚠️ CRITICAL**: No user story work begins until this phase is complete.

- [X] T002 Create `app/Http/Resources/VaultResource.php` with the `@mixin Vault` docblock, `type` of `vault`, `id` set to `$this->uuid` and not to the primary key, the attributes named in [data-model.md](data-model.md) (`name`, `description`, `colour`, `position`, `is_default`, `contact_count`, `created_at`, `updated_at`), timestamps as Unix integers via `->timestamp` and `?->timestamp`, and `links.self` pointing at `route('api.v1.vaults.show', $this->uuid)`, following the shape of `app/Http/Resources/ApiKeyResource.php` including the `#[Override]` attribute

**Note**: T002 cannot be exercised until the show route exists, which is T005. That is the self link
coupling above, and it is why the resource is foundational but has no test of its own.

**Checkpoint**: The shape exists. US1 can start.

---

## Phase 3: User Story 1 - Open a compartment from a script (Priority: P1) 🎯 MVP

**Goal**: `POST /api/v1/vaults` opens a vault in the caller's account and answers with it, carrying the
uuid the other endpoints take.

**Independent test**: Call the endpoint with a valid token and a name, and confirm a vault exists in the
caller's account afterwards, that the answer describes it, and that the answer carries the identifier
the other endpoints take.

- [X] T003 [US1] Create `app/Http/Controllers/Api/VaultController.php` with `create` and `show`, mirroring `app/Http/Controllers/Api/ApiKeyController.php`: a class docblock saying a vault is a compartment and that another account's vault is not found rather than refused, `create` validating `['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:65535'], 'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())]]` then calling `new CreateVault(user: $request->user(), name: ..., description: ..., colour: ...)->execute()` with named arguments and answering `201` via `->response()->setStatusCode(201)`, and `show` looking the vault up with `$request->user()->account->vaults()->where('uuid', $uuid)->firstOrFail()` and answering `200`
- [X] T004 [US1] Register two routes in `routes/api.php` inside the existing `auth:sanctum` group, below the emails sent pair, with a short comment saying a vault is the compartment everything else lives in: `Route::post('vaults', ...)` named `vaults.create`, and `Route::get('vaults/{vault}', ...)` named `vaults.show` constrained by `->where('vault', '[0-9a-fA-F-]{36}')`, adding the `VaultController` import in alphabetical order
- [X] T005 [US1] Create `resources/docs/api/08-vaults.php` with the group name "Vaults" and two sections, `vaults-show` then `vaults-create`, matching the shape of `resources/docs/api/05-api-keys.php` and the exact bodies in [contracts/vaults.md](contracts/vaults.md): the eight colours listed, `examplePath` carrying a real looking uuid, `responseStatus` of 201 on the create section, The Office for the sample data, and no dashes used as punctuation in the copy
- [X] T006 [US1] Create `tests/Feature/Controllers/Api/VaultControllerTest.php` with a shared `$jsonStructure` property and the creation cases: `it_creates_a_vault`, `it_creates_a_vault_without_a_description_or_a_colour` asserting the default colour, `it_puts_a_new_vault_at_the_end_of_the_list`, `it_makes_the_first_vault_of_an_account_the_default_one`, `it_lowercases_a_colour_written_in_capitals`, `it_refuses_a_vault_without_a_name`, `it_refuses_a_colour_the_product_does_not_offer`, `it_refuses_a_name_longer_than_the_limit`, `it_writes_the_creation_to_the_journal` with `Queue::fake()`, and `it_refuses_a_caller_without_a_token`
- [X] T007 [US1] Run `php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and confirm both the creation cases and the two way reference sync pass

**Checkpoint**: A caller can open a vault and read the answer, documented. US1 is deliverable on its own.

---

## Phase 4: User Story 2 - Read the compartments of the account (Priority: P2)

**Goal**: `GET /api/v1/vaults` lists the account's vaults in the order the account arranged them, a page
at a time, and `GET /api/v1/vaults/{vault}` reads one.

**Independent test**: Call the list on an account holding several vaults and confirm every vault comes
back in the account's own order, then ask for one of them by the identifier the list gave and confirm it
is the same vault.

**Note**: The show route, its controller method and its reference section were built in Phase 3, for the
self link reason at the top of this file. This phase adds the list and proves both.

- [X] T008 [US2] Add `index` to `app/Http/Controllers/Api/VaultController.php`, returning a `JsonResponse` with the hand built `data` and `meta` envelope used by `ApiKeyController::index`, the same `max(1, min(...))` clamp against `monica.api_default_items_per_page` and `monica.api_maximum_items_per_page`, scoping through `$request->user()->account->vaults()`, and ordering by `position` then `id` ascending, with a short comment saying why the order is the account's own rather than newest first
- [X] T009 [US2] Register `Route::get('vaults', ...)` in `routes/api.php` named `vaults`, placed above the show route so the file reads index, show, create
- [X] T010 [US2] Add a `vaults-index` section to `resources/docs/api/08-vaults.php`, first in the group so the sidebar reads List, Get, Create, with the `page` and `per_page` query parameters worded as they are in `resources/docs/api/05-api-keys.php`, a `total` of 2 in the sample, and a sentence saying the order is the one the account arranged
- [X] T011 [US2] Add the list cases to `tests/Feature/Controllers/Api/VaultControllerTest.php`: `it_lists_the_vaults_of_the_account_in_the_order_the_account_arranged`, `it_does_not_list_the_vaults_of_another_account`, `it_lists_nothing_when_the_account_holds_no_vault`, `it_brings_an_absurd_page_size_back_into_range`, `it_answers_the_page_that_was_asked_for`, and `it_does_not_list_a_deleted_vault`
- [X] T012 [US2] Add the single vault cases to the same file: `it_shows_one_vault` asserting the full structure and that `data.id` is the uuid rather than the primary key, `it_returns_not_found_for_a_vault_from_another_account`, `it_returns_not_found_for_a_vault_that_does_not_exist`, `it_returns_not_found_for_a_deleted_vault`, and `it_returns_not_found_for_a_numeric_address` asserting `/api/v1/vaults/1` answers 404 even when a vault of the caller's account carries that primary key
- [X] T013 [US2] Run `php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and confirm every case passes

**Checkpoint**: All three read paths work and the account boundary is proven on each.

---

## Phase 5: User Story 3 - Change what a vault says about itself (Priority: P3)

**Goal**: `PUT /api/v1/vaults/{vault}` replaces a vault's name, what it is for, and its colour, without
touching what it holds.

**Independent test**: Change all three on one of the caller's vaults and confirm all three changed, that
nothing the vault holds moved, and that the change is written to the account journal.

- [X] T014 [US3] Add `update` to `app/Http/Controllers/Api/VaultController.php`, looking the vault up the way `show` does, validating `['name' => ['required', 'string', 'max:255'], 'description' => ['present', 'nullable', 'string', 'max:65535'], 'colour' => ['required', 'string', Rule::in(VaultColourEnum::values())]]`, calling `new UpdateVault(user: ..., vault: ..., name: ..., description: ..., colour: ...)->execute()` and answering `200`, with a docblock saying the endpoint replaces rather than patches and why `colour` and `description` are demanded, per [research.md](research.md) section 4
- [X] T015 [US3] Register `Route::put('vaults/{vault}', ...)` in `routes/api.php` named `vaults.update`, constrained by `->where('vault', '[0-9a-fA-F-]{36}')`
- [X] T016 [US3] Add a `vaults-update` section to `resources/docs/api/08-vaults.php`, after `vaults-create`, saying in its `body` that the call replaces the wording and that a field left out is refused rather than quietly reset, and listing all three body parameters as required with `description` noted as required but nullable
- [X] T017 [US3] Add the change cases to `tests/Feature/Controllers/Api/VaultControllerTest.php`: `it_changes_the_wording_of_a_vault`, `it_clears_the_description_when_it_is_sent_as_null`, `it_leaves_what_the_vault_holds_alone` asserting `position`, `is_default` and `contact_count` are untouched, `it_refuses_a_change_that_omits_the_colour`, `it_refuses_a_change_that_omits_the_description`, `it_refuses_a_change_that_omits_the_name`, `it_returns_not_found_when_changing_a_vault_from_another_account` asserting the vault is unchanged afterwards, and `it_writes_the_change_to_the_journal`
- [X] T018 [US3] Run `php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and confirm every case passes, in particular the three that prove a partial change is refused

**Checkpoint**: A caller can correct a vault, and cannot lose a field by forgetting to mention it.

---

## Phase 6: User Story 4 - Delete a vault and everything it separates (Priority: P4)

**Goal**: `DELETE /api/v1/vaults/{vault}` deletes the vault exactly as the settings screen does, and
answers with nothing.

**Independent test**: Delete one of the caller's vaults and confirm the vault is gone, that what it held
went with it, that private links into it no longer open, and that the account is left coherent.

- [X] T019 [US4] Add `destroy` to `app/Http/Controllers/Api/VaultController.php`, looking the vault up the way `show` does, calling `new DestroyVault(user: $request->user(), vault: $vault)->execute()`, returning `Response` from `response()->noContent(204)`, with a docblock saying this is the most destructive call in the API, that there is no second step because the call is itself the confirmation, and that nothing brings a vault back today
- [X] T020 [US4] Register `Route::delete('vaults/{vault}', ...)` in `routes/api.php` named `vaults.destroy`, constrained by `->where('vault', '[0-9a-fA-F-]{36}')`
- [X] T021 [US4] Add a `vaults-destroy` section to `resources/docs/api/08-vaults.php`, last in the group, whose `body` states what leaves with the vault (the contacts, the records of the invented kinds, every private link into the compartment, permanently), that the default title passes to the first vault left, that the last vault of an account may go, and that a caller should treat this as final because no endpoint brings a vault back, with `responseStatus` of 204 and no `response` body
- [X] T022 [US4] Add the deletion cases to `tests/Feature/Controllers/Api/VaultControllerTest.php`: `it_deletes_a_vault` asserting 204, an empty body and `assertSoftDeleted`, `it_takes_the_contacts_with_it` asserting `deleted_with_vault`, `it_revokes_the_private_links_into_the_vault`, `it_hands_the_default_title_to_the_first_vault_left` asserting exactly one default vault remains in the account, `it_deletes_the_last_vault_of_an_account` asserting the account is left with no vault and no default, `it_returns_not_found_when_deleting_a_vault_from_another_account` asserting the vault still exists afterwards, `it_returns_not_found_when_deleting_the_same_vault_twice`, and `it_writes_the_deletion_to_the_journal`
- [X] T023 [US4] Run `php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and confirm every case passes

**Checkpoint**: All five endpoints exist and are documented. The API matches the vault screens except
for the two decisions in Phase 7.

---

## Phase 7: User Story 5 - Say where records land and what they start from (Priority: P5)

**Goal**: both write endpoints accept `is_default` and `template_version_id`, settling the same two
account level decisions the browser form settles.

**Independent test**: Create a vault asking for it to be the default and naming a published contact
structure, then confirm the account's default moved to it and that a new contact in it starts from the
structure named.

- [X] T024 [US5] Extend the validation in `VaultController::create` with `'is_default' => ['nullable', 'boolean']` and `'template_version_id' => ['nullable', 'integer', Rule::exists('templates', 'published_version_id')->where('account_id', $request->user()->account_id)->where('applies_to', TemplateAppliesToEnum::Contact->value)->where('is_active', true)]`, lifting the rule from `App\Http\Controllers\App\Vaults\VaultController::rules()`, then after `CreateVault` call `SetVaultDefaultTemplate` when `template_version_id` is filled and `SetDefaultVault` when `$request->boolean('is_default')` is true, in that order, matching the web controller
- [X] T025 [US5] Apply the same two rules and the same two calls to `VaultController::update`, in the same order, and refresh the vault before it is returned so the answer carries `is_default` as it now stands
- [X] T026 [US5] Add the two body parameters to both write sections of `resources/docs/api/08-vaults.php`, saying that a false or absent `is_default` changes nothing because the title is never left unheld, that `template_version_id` must be a published version of one of your active contact templates, that contacts already created keep the version they were created on, and that the vault object does not carry the structure back because that default is held per vault and per kind of record
- [X] T027 [US5] Add the two decision cases to `tests/Feature/Controllers/Api/VaultControllerTest.php`: `it_makes_a_new_vault_the_default_one_when_asked`, `it_takes_the_default_title_off_the_vault_that_held_it` asserting exactly one default vault in the account, `it_leaves_the_default_alone_when_the_request_says_false`, `it_leaves_the_default_alone_when_the_request_says_nothing`, `it_says_what_a_new_contact_in_the_vault_starts_from` asserting the `vault_default_templates` row, `it_refuses_a_template_version_that_is_still_a_draft`, `it_refuses_a_template_version_from_another_account`, `it_makes_a_vault_the_default_one_on_a_change`, and `it_writes_each_decision_to_the_journal_as_its_own_entry`
- [X] T028 [US5] Run `php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php` and confirm every case passes, in particular that the count of default vaults in the account is one after every write

**Checkpoint**: The API matches the vault creation form field for field. The feature is complete.

---

## Phase 8: Polish and Cross-Cutting Concerns

- [X] T029 [P] Add `$response->assertSee('Vaults');` to the sidebar test in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`, beside the existing assertions for Health, Your profile, API keys, Logs and Emails sent
- [X] T030 [P] Run `vendor/bin/pint --dirty --format agent` from the repository root and commit whatever it reformats
- [X] T031 Run `php artisan monica:localize` and then `php artisan monica:localize --check`, confirming nothing went missing across the nine locales, per the checklist in `.claude/CLAUDE.md`. No new key is expected: the reference is English copy on a surface that does not go through `__()`
- [X] T032 Run `vendor/bin/phpstan analyse` and confirm it reports nothing new over `app/`, `routes/` and `database/`
- [X] T033 Run the whole suite with `php artisan test --compact` and confirm it is green
- [X] T034 Walk [quickstart.md](quickstart.md) steps 2 through 7 by hand against a running application, in particular step 4, where sending only a name must answer 422 rather than 200, and step 6, where the count of default vaults must stay at one
- [X] T035 Confirm the reference at `/docs/api` shows a "Vaults" group under RESOURCES with five entries carrying GET, GET, POST, PUT and DELETE, that `/docs/api.md` carries the group too, and that `/docs/api/vaults-index.md` renders on its own

**Two pinned assertions this list did not anticipate**, both found by the suite and both fixed:
`Tests\Unit\ViewModels\Docs\ApiDocsViewModelTest::it_splits_the_sidebar_between_guides_and_resources`
asserts the exact list of sidebar group names, so it gained `'Vaults'`, and
`ApiDocsControllerTest::every_section_of_the_reference_is_reachable` pins the total number of sections,
which went from 19 to 24. A group added to the reference always touches both, and T029 named only one
of them. Worth remembering for the next API feature.

---

## Dependencies

```text
T001 (baseline)
  └─> Phase 2 Foundational
        T002 (resource)   [cannot run until T005 registers the show route]
              └─> Phase 3 US1
                    T003 (controller: create, show) ──> T004 (two routes) ──> T005 (reference) ──> T006 (tests) ──> T007
                                                        T004 and T005 must land together
                          └─> Phase 4 US2
                                T008 (index) ──> T009 (route) ──> T010 (reference) ──> T011, T012 (tests) ──> T013
                                      └─> Phase 5 US3
                                            T014 (update) ──> T015 (route) ──> T016 (reference) ──> T017 (tests) ──> T018
                                                  └─> Phase 6 US4
                                                        T019 (destroy) ──> T020 (route) ──> T021 (reference) ──> T022 (tests) ──> T023
                                                              └─> Phase 7 US5
                                                                    T024 (create fields) ──> T025 (update fields) ──> T026 (reference) ──> T027 (tests) ──> T028
                                                                          └─> Phase 8 Polish
                                                                                T029, T030 parallel; then T031 to T035 in order
```

**The one ordering that must not be relaxed**: within every story phase, the route task and the
reference task are a pair. Committing one without the other leaves `ApiDocsViewModelTest` failing in
one of its two directions, so a phase that stops between them ends on a red suite.

**Story order**: US1 before US2 before US3 before US4 before US5. US2's show plumbing is dragged into
US1 by the self link, and US5 edits the two methods US1 and US3 wrote, so no story can be built before
the one above it.

## Parallel execution examples

**Phase 8, at the start**: T029 (the sidebar assertion) and T030 (Pint) touch different files and
depend on nothing, so they can run together. Everything after them is a verification run and is
sequential by nature.

**Phases 3 to 7 hold no parallel work at all.** Each phase edits the same controller, the same route
file, the same reference file and the same test class, in that order, and each task depends on the one
before it. Two people on this feature would be two people queueing on three files.

## Implementation strategy

**MVP**: Phase 1 plus Phase 2 plus Phase 3, which is T001 to T007. That delivers a documented way to
open a vault and read it back, which is the gate in front of every other capability the API has: an
account with no vault can hold nothing, so this is the endpoint that makes the rest of the API usable
from a script.

**Increment two**: Phase 4, six tasks, which makes the API usable against an account somebody else set
up, and proves the compartment on all three read paths.

**Increments three and four**: Phases 5 and 6, five tasks each, which close the settings screen's gap.
Phase 6 is where the care goes: it is the most destructive call in the API and its test list is the
longest for that reason.

**Increment five**: Phase 7, five tasks, which closes the creation form's gap.

**Then**: Phase 8. None of it may be skipped before the pull request. T031, T032 and T033 are checklist
items in `.claude/CLAUDE.md` and gates in CI rather than suggestions.

**Total**: 35 tasks. 1 setup, 1 foundational, 5 for US1, 6 for US2, 5 for US3, 5 for US4, 5 for US5,
7 polish.
