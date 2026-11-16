# Tasks: QUERY method on the API

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

Tests are included: `CLAUDE.md` makes a covering test part of "done". No setup or foundational
phase: nothing blocks both stories, and nothing is installed or migrated.

## Phase 1: User Story 1, read with the parameters in the body (P1) 🎯 MVP

**Independent test**: a QUERY with a JSON body on the contacts list answers exactly as the GET with the same parameters in the address.

- [X] T001 [US1] In `routes/api.php`, replace every `Route::get(` with `Route::match(['GET', 'QUERY'], `, one route per line as now (112 lines, research D1)
- [X] T002 [US1] Create `app/Http/Middleware/MergeQueryRequestBody.php` with `php artisan make:middleware --no-interaction`: pass through unless `$request->isMethod('QUERY')`; read the body as `$request->isJson() ? $request->json()->all() : $request->request->all()`; throw `ValidationException::withMessages()` with `__('Send this parameter either in the address or in the body, not both.')` for every key in both the body and `$request->query->all()`; otherwise `$request->query->add($body)` (research D2)
- [X] T003 [US1] Alias the middleware `query.body` in `bootstrap/app.php` (middlewares skill) and change the authenticated group in `routes/api.php` to `Route::middleware(['auth:sanctum', 'query.body'])` (research D3)
- [X] T004 [US1] In `app/ViewModels/Docs/ApiDocsViewModel.php` `registeredRoutes()`, skip `QUERY` beside `HEAD` and `OPTIONS`, so `it_documents_every_registered_route` keeps comparing GET with the reference (research D4)
- [X] T005 [P] [US1] Create `tests/Feature/ApiQueryMethodTest.php` (The Office data): every route under `api/v1/` holding GET holds QUERY; QUERY on `logout` answers 405; QUERY on `vaults/{vault}/contacts` with `{"page": 2, "per_page": 2}` returns the same JSON as the GET with `?page=2&per_page=2`; QUERY on `vaults/{vault}/contacts` with a form body `query=Dwight` finds Dwight; `{"page": 2}` with `?page=1` answers 422 with an error on `page`; an unauthenticated QUERY answers 401
- [X] T006 [US1] Run `php artisan monica:localize` and translate the new message in every configured locale, following the translations skill (sonnet subagents), until `php artisan monica:localize --check` passes

## Phase 2: User Story 2, find QUERY in the reference (P2)

**Independent test**: the reference page of a GET endpoint names QUERY and links to the guide, and the OpenAPI document holds a `query` operation on that path.

- [X] T007 [P] [US2] Add a guide section to `resources/docs/api/01-getting-started.php` with `id` `query-method`, title "The QUERY method", borrowing `GET /vaults/{vault}/search`: one worked request with a JSON body, the 422 for a parameter sent twice, and paging by sending `page` again
- [X] T008 [US2] In `resources/views/components/api-docs/endpoint.blade.php`, print one line on a section whose `method` is `GET` saying it also answers to QUERY, linking to `route('docs.api.show', ['section' => 'query-method'])`
- [X] T009 [US2] In `app/ViewModels/Docs/ApiDocsMarkdown.php`, print the same line, with the same link, in the Markdown of a GET section
- [X] T010 [P] [US2] In `app/Actions/BuildOpenApiDocument.php`, beside each `get` operation write a `query` operation: a copy with `Query` appended to its `operationId` and an `application/json` `requestBody` whose object properties are the GET's `in: query` parameters (research D5)
- [X] T011 [P] [US2] Create `resources/docs/api-changelog/<release date>.php`: one entry, `id` `query-method`, kind `Added`, group `Getting started`, not breaking, `changes` naming every `QUERY /path` written out by hand from `php artisan route:list --path=api/v1 --method=QUERY` (research D6)
- [X] T012 [P] [US2] In `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`: a GET endpoint page shows the QUERY line and its link; a POST endpoint page does not; the `query-method` guide renders
- [X] T013 [P] [US2] In `tests/Unit/Actions/BuildOpenApiDocumentTest.php`: a GET path carries a `query` operation with a request body naming its parameters; a POST path carries none
- [X] T014 [US2] Run `tests/Unit/ViewModels/Docs/ApiChangelogViewModelTest.php` and `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`: the shipped changelog reports no problem, and the guide passes `it_registers_every_documented_route`

## Phase 3: Polish

- [X] T015 Run the whole suite with `PAO_DISABLE=1 php artisan test --parallel --compact`, since T001 touches every API route
- [X] T016 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T017 Run the five scenarios of `specs/126-api-query-method/quickstart.md` against a running instance

## Dependencies

- T002 before T003. T001, T003 and T004 before T005. T002 before T006.
- US2 needs T004 for its parity tests, and otherwise nothing from US1. T007 before T008, T009 and T012. T010 before T013. T011 before T014.
- T015 to T017 after both stories.

## Parallel opportunities

- US1: T005 is written while T001 to T004 are made.
- US2: T007, T010 and T011 touch separate files. T012 and T013 follow their own implementation tasks.

## Implementation strategy

US1 alone is shippable: QUERY works, and the reference still matches the routes. US2 makes it discoverable. Ship both in one pull request (clarified 2026-11-16, no flag).
