# Tasks: QUERY requests in the API reference

**Input**: Design documents from `specs/148-api-query-documentation/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/reference-entry.md](contracts/reference-entry.md)

**Tests**: included. `CLAUDE.md` and `.claude/rules/testing.md` require a test for every behaviour
change, and FR-012 asks the suite to enforce coverage. Each rule is tested once, in the layer that
owns it: the derivation in the view model test, the page in one controller test.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story of spec.md the task serves

## Path Conventions

Laravel monolith at the repository root: `app/`, `resources/`, `tests/`.

---

## Phase 1: Setup

- [X] T001 Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Unit/Actions/BuildOpenApiDocumentTest.php tests/Feature/Controllers/App/Docs` and record which tests pass before any change, so a later failure can be told apart from one that was already there (see the memory note on `McpOAuthTest` and on `view:clear`)

---

## Phase 2: Foundational

- [X] T002 Add two nullable readonly properties to `app/ViewModels/Docs/ApiDocsSection.php`, read in the constructor as `$section['querySectionId'] ?? null` and `$section['derivedFromSectionId'] ?? null`: `querySectionId` ("set only on a GET section in a group that is not a guide") and `derivedFromSectionId` ("set on a derived QUERY entry"), per [data-model.md](data-model.md#apidocssection)

**Checkpoint**: nothing visible changes yet.

---

## Phase 3: User Story 1 - Open the reference entry of a QUERY request (Priority: P1) MVP

**Goal**: every route that answers QUERY has its own entry page, derived from its GET entry.

**Independent Test**: open `/docs/api/{GET id}-query` for the vault contact list; it shows "with
QUERY" in its heading, body parameters and a cURL sample sending `-X QUERY` with a JSON body.

### Tests for User Story 1

- [X] T003 [US1] In `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`, add tests of `ApiDocsViewModel::withQuerySections()` built on hand made section arrays (a GET listing of Dunder Mifflin employees with a `branch` query parameter, a POST beside it):
  - `it_follows_every_get_section_with_its_query_entry`: the result is GET, QUERY, POST; the QUERY id is `"{id}-query"`, its title `"{title} with QUERY"`, its label the GET label, its `derivedFromSectionId` the GET id, and the GET array carries `querySectionId`
  - `it_sends_the_query_parameters_in_the_body_of_the_query_entry`: `bodyParams` holds the GET `queryParams`, `queryParams` is empty, `pathParams` is kept; the built `ApiDocsSection` has `exampleBody()` `['branch' => 'Scranton']`
  - `it_adds_the_refusal_of_a_parameter_sent_twice_to_the_status_codes`: with no 422 row a 422 row is added; with an existing 422 row its `when` is extended and no second 422 row appears
  - `it_adds_no_refusal_when_the_endpoint_takes_no_query_parameters`
  - `it_derives_no_query_entry_in_a_guide`, through `groups()` on the shipped files: the `query-method` guide section has no twin and no guide group gains a QUERY entry
  - `it_gives_no_query_entry_to_an_endpoint_switched_off_for_every_account`, following the existing `it_leaves_out_an_endpoint_switched_off_for_every_account`

### Implementation for User Story 1

- [X] T004 [US1] Add `public static function withQuerySections(array $sections): array` to `app/ViewModels/Docs/ApiDocsViewModel.php`, next to `offeredToEveryAccount()`. For each section with `'method' => 'GET'` it writes `querySectionId` on the GET array and appends directly after it a copy with: `id` `"{id}-query"`, `method` `QUERY`, `title` `"{title} with QUERY"`, `label` the GET label or title, `queryParams` `[]`, `bodyParams` the GET `queryParams`, `derivedFromSectionId` the GET id, and the status codes of [research R5](research.md#r5-status-codes) (wording: "Or a parameter was sent both in the address and in the body." appended to an existing 422 `when`, else a 422 row named "Unprocessable Content" whose `when` is "A parameter was sent both in the address and in the body."). In `groups()` call it on `offeredToEveryAccount($group['sections'])` only when the group is not a guide
- [X] T005 [US1] In `ApiDocsViewModel::registeredRoutes()` of `app/ViewModels/Docs/ApiDocsViewModel.php`, stop skipping `QUERY` (keep `HEAD` and `OPTIONS`), so `it_documents_every_registered_route` and `it_registers_every_documented_route` enforce FR-012 in both directions
- [X] T006 [P] [US1] In `app/Actions/BuildOpenApiDocument.php`, make `groups()` pass each group's offered sections through `ApiDocsViewModel::withQuerySections()` (guides excluded as today), and delete the `if ($section['method'] === 'GET')` QUERY branch from `paths()`. The QUERY operation keeps its `operationId` `{id}-query`
- [X] T007 [P] [US1] In `tests/Unit/Actions/BuildOpenApiDocumentTest.php`, remove from `registered()` the lines adding `"QUERY {$route['path']}"`, since `registeredRoutes()` now returns QUERY itself
- [X] T008 [P] [US1] In `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`, make `endpoints()` leave out sections whose `derivedFromSectionId` is not null ([research R8](research.md#r8-the-return-field-test))
- [X] T009 [US1] In `resources/views/components/api-docs/endpoint.blade.php`, add `@if ($section->derivedFromSectionId !== null)` a paragraph in the style of the existing QUERY sentence: "The QUERY form of" a link to `route('docs.api.show', ['section' => $section->derivedFromSectionId])` reading "GET {path}", then ", with the same parameters sent in the request body." and the existing "How QUERY works" link to the `query-method` guide
- [X] T010 [US1] In `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`, add one test that the QUERY entry of a shipped read endpoint renders: status 200, its title ending "with QUERY", and the address of its GET entry in the page

**Checkpoint**: every QUERY route has an entry; the coverage and OpenAPI tests pass.

---

## Phase 4: User Story 2 - Find a QUERY request in the navigation (Priority: P2)

**Goal**: the GET entry leads to its QUERY entry; sidebar and paging list it right after the GET entry.

**Independent Test**: on the GET entry of the contact list, the QUERY sentence links to the QUERY
entry, and "Next" opens it.

- [X] T011 [US2] In `resources/views/components/api-docs/endpoint.blade.php`, change the condition of the existing sentence from `$section->method === 'GET'` to `$section->querySectionId !== null`, and point its link at `route('docs.api.show', ['section' => $section->querySectionId])` with the text "Its QUERY entry" in place of "How QUERY works" (depends on T009, same file)
- [X] T012 [US2] In `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`, add `it_pages_from_a_get_entry_to_its_query_entry` (`next()` of a shipped GET entry is its QUERY entry) and `it_lists_the_query_entry_after_its_get_entry_in_the_navigation` (`navigation()` resources hold the QUERY item directly after the GET item, with the same label and method `QUERY`)

**Checkpoint**: the QUERY entries are reachable from the GET entry, the sidebar and paging.

---

## Phase 5: User Story 3 - Read the QUERY entry as Markdown (Priority: P3)

**Goal**: the Markdown copies say what the HTML pages say.

**Independent Test**: `/docs/api/{id}-query.md` holds the QUERY entry, and `/docs/api.md` prints it
after its GET entry.

- [X] T013 [P] [US3] In `app/ViewModels/Docs/ApiDocsMarkdown.php`, replace the `if ($section->method === 'GET')` sentence with two: when `querySectionId` is set, "This endpoint also answers QUERY, with the same parameters sent in the request body. [Its QUERY entry](…{querySectionId}.md)."; when `derivedFromSectionId` is set, "The QUERY form of [GET {path}](…{derivedFromSectionId}.md), with the same parameters sent in the request body. [How QUERY works](…query-method)." Link with `route('docs.api.markdown.show', …)` for the entries, as the guide link does with `docs.api.show`
- [X] T014 [US3] In `tests/Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php`, add one test that `/docs/api/{id}-query.md` of a shipped read endpoint answers `text/markdown` holding its "with QUERY" title and the link to its GET entry

**Checkpoint**: all three stories done.

---

## Phase 6: Polish

- [X] T015 [P] Add `resources/docs/api-changelog/2026-11-22.php` in the shape of `2026-11-16.php`: one entry, `id` `query-reference-entries`, `kind` `Added`, `group` `Getting started`, `breaking` false, title "Every QUERY request has its own reference entry", a summary saying each read endpoint's QUERY request now has its own page, sample and Markdown copy, and no `changes` list of endpoints unless the changelog tests require one
- [X] T016 Run `vendor/bin/pint --dirty --format agent`, PHPStan on the changed files, and `php artisan test --compact` over `tests/Unit/ViewModels/Docs`, `tests/Unit/Actions/BuildOpenApiDocumentTest.php`, `tests/Feature/Controllers/App/Docs`, `tests/Feature/Views/Components/ApiDocs` and `tests/Feature/ChangelogSwitchTest.php`; compare with T001
- [X] T017 Run the `definition-of-done` skill, then the `rules-reviewer` and `accessibility-reviewer` agents on the diff (the Blade change in `endpoint.blade.php`)
- [X] T018 Walk [quickstart.md](quickstart.md) by hand in the browser: sidebar, a QUERY entry page, Next from a GET entry, `/docs/api.md`, `/llms.txt`

---

## Dependencies & Execution Order

- T001 → T002 → US1 (T003 → T004 → T005; T006, T007, T008 in parallel after T004; T009 → T010)
- US2 depends on US1 (it links to entries US1 creates); T011 follows T009 in the same file
- US3 depends on T002 and T004 only, so it can run beside US2
- Polish after all stories

## Parallel Example: User Story 1

After T004 lands:

```text
T006 app/Actions/BuildOpenApiDocument.php
T007 tests/Unit/Actions/BuildOpenApiDocumentTest.php
T008 tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php
```

## Implementation Strategy

MVP is Phase 1 to 3: with US1 alone every QUERY request has a page, listed in the sidebar and paging
because they read the same sections. US2 adds the pointer from the GET entry, US3 the Markdown
sentences. Ship as one pull request; the stories are small enough that splitting them buys nothing.

---

## Phase 7: Example values (added after implementation, at the requester's request)

- [X] T019 In `app/ViewModels/Docs/ApiDocsSection.php`, build `exampleBody()` from `bodyParams` only and add `exampleQuery()` from `queryParams`; in `app/ViewModels/Docs/ApiDocsCodeSamples.php`, append `exampleQuery()` to the sample address ([research R11](research.md#r11-example-values-and-where-a-sample-sends-them))
- [X] T020 Add an `example` to every GET query parameter in `resources/docs/api/*.php` that filters by a value, and move the query strings out of the six `examplePath` values
- [X] T021 Test both in `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`, check every example against its controller's validation rule, and mention the fix in `resources/docs/api-changelog/2026-11-22.php`

## Phase 8: One page for GET and QUERY (added after implementation, at the requester's request)

- [X] T022 Nest the QUERY section in its GET section in `app/ViewModels/Docs/ApiDocsViewModel.php` and `app/ViewModels/Docs/ApiDocsSection.php`, and read it in `navigation()`, `documentedRoutes()`, `sectionFor()` and `app/Actions/BuildOpenApiDocument.php` ([research R12](research.md#r12-one-page-for-get-and-query-2026-11-23))
- [X] T023 Stack the two methods in `resources/views/components/api-docs/sidebar.blade.php`, show both badges, the sentence and the QUERY sample in `resources/views/components/api-docs/endpoint.blade.php`, and do the same in `app/ViewModels/Docs/ApiDocsMarkdown.php`
- [X] T024 Remove `querySectionId`, `derivedFromSectionId` and the two linking sentences, and rewrite the tests that read them
- [X] T025 Rewrite the entry in `resources/docs/api-changelog/2026-11-22.php` and the spec
