# Tasks: A lightweight list of contacts

**Tests**: included, since principle III makes them a delivery condition. Work stays on `2026-11-22-lightweight-contact-list`. Decisions referenced as R1 to R5 are in [research.md](research.md), the endpoint and the tool in [contracts/contact-summaries-api.md](contracts/contact-summaries-api.md).

## Phase 1: Setup

- [X] T001 Read `index()` and the private `vault()` of app/Http/Controllers/Api/ContactController.php, app/Mcp/Tools/ListContacts.php and tests/Feature/Controllers/Api/ContactControllerTest.php, the patterns the new files copy (paging `meta`, `per_page` clamp, `Sanctum::actingAs`, `$jsonStructure`)

## Phase 2: Foundational (blocks every story)

- [X] T002 Create app/Http/Controllers/Api/ContactSummaryController.php with an `index(Request $request, string $vault): JsonResponse` resolving the vault through `$request->user()->account->vaults()->addressedBy($vault)->firstOrFail()` (R4, R5)
- [X] T003 Add `Route::match(['GET', 'QUERY'], 'vaults/{vault}/contact-summaries', [ContactSummaryController::class, 'index'])->name('contactSummaries');` beside `contact-categories` in routes/api.php, with its `use` (R2)

## Phase 3: User Story 1, ask an assistant for every contact (P1, MVP)

- [X] T004 [US1] In `index()` of app/Http/Controllers/Api/ContactSummaryController.php: `Contact::query()->where('vault_id', $vault->id)->where('is_archived', false)->sortedBy(ContactSortEnum::Name, SortDirectionEnum::Ascending)->paginate($perPage)` with `$perPage` clamped between 1 and `config('monica.api_maximum_contact_summaries_per_page')` (500), default `config('monica.api_default_contact_summaries_per_page')` (100), both added to config/monica.php; answer `data` as `['id' => uuid, 'name' => $contact->name, 'is_partial' => ..., 'is_archived' => ...]` and `meta` as `page`, `per_page`, `total` (contract, R5)
- [X] T005 [US1] Create app/Mcp/Tools/ListContactSummaries.php mirroring `api.v1.contactSummaries` like ListContacts.php: `#[IsReadOnly]`, `#[IsDestructive(false)]`, `#[IsOpenWorld(false)]`, a description saying it lists every contact of one vault, partial ones included, with only id, name, partial and archived, and that list-contacts is the one for searching and columns; schema `vault` (required), `page`, `per_page` (default 100, maximum 500)
- [X] T006 [US1] Register `ListContactSummaries::class` next to `ListContacts::class` in app/Mcp/Servers/MonicaServer.php, and add `list-contact-summaries` after `list-contacts` in the Contacts family of app/ViewModels/Docs/McpDocsViewModel.php
- [X] T007 [P] [US1] Test in tests/Feature/Controllers/Api/ContactSummaryControllerTest.php: the JSON structure; a vault with Pam and Jim (full), Astrid (partial), Roy (archived) and a soft deleted contact answers Astrid, Jim, Pam in that order with their uuids (US1-1, US1-2); `per_page=2` gives two entries and `meta.total` 3, `per_page=900` is clamped to 500 (US1-3); a vault of another account answers 404 (US1-4)

## Phase 4: User Story 2, only partial, or only full, contacts (P2)

- [X] T008 [US2] Validate in `index()` of app/Http/Controllers/Api/ContactSummaryController.php `'filter' => ['nullable', 'string', Rule::in(['partial', 'full'])]` and `'archived' => ['nullable', 'string', Rule::in(['excluded', 'included'])]`; `partial` keeps `is_partial = true`, `full` keeps `is_partial = false`, `included` drops the `is_archived` condition (R3)
- [X] T009 [US2] Add `filter` (enum `partial`, `full`) and `archived` (enum `excluded`, `included`) to the schema of app/Mcp/Tools/ListContactSummaries.php, each description naming what its values keep
- [X] T010 [P] [US2] Test in tests/Feature/Controllers/Api/ContactSummaryControllerTest.php: `filter=partial` answers Astrid only, `filter=full` Jim and Pam (US2-1, US2-2); `archived=included` adds Roy, the only entry with `is_archived` true (US2-4); `filter=nope` and `archived=nope` answer 422 on that parameter (US2-3)

## Phase 5: Polish and cross-cutting

- [X] T011 [P] Document the endpoint, its parameters and its answer in resources/docs/api/06-contacts.php and add the day's entry under resources/docs/api-changelog/, with the `api-docs-writer` skill
- [X] T012 [P] Add the What's new entry with the `changelog` skill, then translate its strings with subagents on model sonnet per the `translations` skill and run `php artisan monica:localize --check`
- [X] T013 Run the `rules-reviewer` agent on the change and fix what it confirms
- [ ] T014 Run `vendor/bin/pint --dirty --format agent`, PHPStan and `composer test:parallel`, then walk through Validation in specs/147-lightweight-contact-list/plan.md and run the `definition-of-done` skill

## Dependencies

- T002 and T003 block every story. US1 (T004 to T007) is the MVP; US2 adds parameters to the same two files, so it follows US1.
- Within a story, the controller before the tool, a test beside or after its code.

## Parallel examples

- US1: T007 beside T005 and T006 once T004 exists.
- US2: T010 beside T009 once T008 exists.
- Polish: T011 and T012 together.

## Implementation strategy

1. Phases 1 and 2, then US1: an assistant lists every non archived contact of a vault. Stop and try it over MCP.
2. US2: the two filters.
3. Polish, then the definition of done.
