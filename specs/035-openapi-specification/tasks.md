# Tasks: OpenAPI description of the public API

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: included, because constitution principle III is non-negotiable and the plan names the two test files.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with no incomplete dependency.

**Length**: constitution principle XI caps this file at one line per task plus phase headings and the dependency list, so there is no strategy prose and no worked parallel example below.

## Phase 1: Setup

- [X] T001 Install dependencies with `composer install && bun install && bun run build`, then confirm `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` passes, since a fresh worktree has no `vendor/`

---

## Phase 2: Foundational (blocks every story)

- [X] T002 Create the action `app/Actions/BuildOpenApiDocument.php` with a promoted readonly constructor and one public `execute(): array`, reading the 19 files under `resources/docs/api` with `require` and skipping every group whose `guide` is true
- [X] T003 Emit the document skeleton in `app/Actions/BuildOpenApiDocument.php`: `openapi` 3.2.0, `info` carrying title, description, version, license and contact, `servers` from `ApiDocsViewModel::baseUrl()`, `components.securitySchemes.bearerAuth`, and `bearerAuth` as the global `security`
- [X] T004 Emit `tags` in `app/Actions/BuildOpenApiDocument.php`, one per non guide group `name`, in file order

**Checkpoint**: the action returns a valid but pathless document.

---

## Phase 3: User Story 1 - Generate a client without reading prose (Priority: P1) 🎯 MVP

**Goal**: one fetch of `/docs/api/v1/openapi.json` yields a document a generator turns into a client covering every endpoint.

**Independent test**: fetch it, run a generator over it, confirm the client covers all 109 routed endpoints and compiles.

- [X] T005 [P] [US1] Create `tests/Unit/Actions/BuildOpenApiDocumentTest.php` asserting the skeleton, that no guide section contributes a path, and that the three endpoints whose `auth` is false carry `security: []`
- [X] T006 [P] [US1] Create `tests/Feature/Controllers/App/Docs/OpenApiDocumentControllerTest.php` asserting `/docs/api/v1/openapi.json` answers 200 as JSON with no credential and declares `openapi` 3.2.0
- [X] T007 [US1] Map each section to an operation in `app/Actions/BuildOpenApiDocument.php`: path key, lowercased method, `operationId` from `id`, `tags` from the group name, `summary` from `title`, and `description` from `description`, `body` and `rateLimit`
- [X] T008 [US1] Map parameters in `app/Actions/BuildOpenApiDocument.php` from the raw `pathParams` as `in: path` always required and `queryParams` as `in: query`, reading the arrays directly so the kind survives, per [data-model.md](data-model.md)
- [X] T009 [US1] Map `requestBody` in `app/Actions/BuildOpenApiDocument.php` from `bodyParams` as `application/json`, and as `multipart/form-data` for the one `file` parameter on the contact avatar endpoint
- [X] T010 [US1] Map the type vocabulary in `app/Actions/BuildOpenApiDocument.php` per [data-model.md](data-model.md), casting `default` to the parameter's own type because it is held as a string
- [X] T011 [US1] Fold `returnFields` dotted paths into a nested response schema in `app/Actions/BuildOpenApiDocument.php`, `[]` marking a list and `opaque` a free form object
- [X] T012 [US1] Emit the success response in `app/Actions/BuildOpenApiDocument.php` from `responseStatus` and `response`, carrying no content when the status is 204
- [X] T013 [US1] Derive the refusals in `app/Actions/BuildOpenApiDocument.php`: 401 when `auth` is true, 404 with any path parameter, 422 with any body parameter, and 429 always
- [X] T014 [US1] Create `app/Http/Controllers/App/Docs/OpenApiDocumentController.php` calling the action and answering with the document as JSON
- [X] T015 [US1] Register `GET docs/api/v1/openapi.json` in `routes/web.php` named `docs.api.openapi`, declared before `docs/api/{section}` so the catch all does not swallow it

**Checkpoint**: US1 is complete and a client can be generated.

---

## Phase 4: User Story 2 - Load the API into the tool you already use (Priority: P2)

**Goal**: the document imports cleanly into an ordinary API client and a request sent from it works.

**Independent test**: import it into Postman or Bruno, send one request with a token, confirm the answer matches the promised shape.

- [X] T016 [US2] Extend `tests/Unit/Actions/BuildOpenApiDocumentTest.php` asserting the pagination `meta` and each refusal are referred to rather than repeated inline
- [X] T017 [US2] Extract the pagination `meta` and the refusal shapes to `components.schemas` in `app/Actions/BuildOpenApiDocument.php` and refer to them, leaving record shapes inline because the same type carries different fields per endpoint
- [X] T018 [US2] Build the request body example in `app/Actions/BuildOpenApiDocument.php` from each parameter's `example`, omitting a parameter whose example is null, which is what the portal already shows
- [X] T019 [US2] Validate the served document with `bunx @redocly/cli@latest lint` per [quickstart.md](quickstart.md) and fix every error and every warning, since FR-002 admits neither

**Checkpoint**: US1 and US2 both hold independently.

---

## Phase 5: User Story 3 - The description cannot fall behind the API (Priority: P3)

**Goal**: a route added, removed or renamed without its reference entry fails the suite.

**Independent test**: add a route without documenting it, then remove a documented one, and confirm each fails.

- [X] T020 [US3] Extend `tests/Unit/Actions/BuildOpenApiDocumentTest.php` with a two direction parity assertion against `ApiDocsViewModel::registeredRoutes()`, each failure naming the endpoint it could not match

**Checkpoint**: all three stories hold.

---

## Phase 6: Polish

- [X] T021 [P] Point the reference at the document, satisfying FR-016: an `OpenApi` button beside `Get an API key` in `resources/views/components/api-docs/subheader.blade.php`, and a summary in the introduction of `resources/docs/api/01-getting-started.php`
- [X] T022 [P] Run `php artisan monica:localize` and translate whatever it reports in every configured locale, writing nothing in `lang/*.json` by hand
- [X] T023 Run `vendor/bin/pint --dirty --format agent` and PHPStan over `app/` and `routes/`, fixing rather than ignoring what they report
- [ ] T024 Run the full suite with `php artisan test` on SQLite, MySQL and PostgreSQL, since `resources/docs/api` feeds tests beyond this feature. SQLite is green here, 6024 tests. MySQL and PostgreSQL are not reachable in this environment, so `tests.yml` is what runs them

---

## Dependencies

- T001 blocks everything.
- T002 blocks T003 and T004. T003 and T004 block Phase 3.
- Within Phase 3: T005 and T006 are parallel to each other and to nothing else. T007 blocks T008 to T013, all of which edit `app/Actions/BuildOpenApiDocument.php` and are therefore sequential. T014 depends on T002. T015 depends on T014.
- Phase 4 depends on Phase 3. T016 blocks nothing; T017 and T018 edit the action and are sequential; T019 depends on T017 and T018.
- Phase 5 depends only on Phase 2, so T020 may be brought forward if drift protection is wanted before the document is complete.
- Phase 6 depends on Phase 3 at least. T023 and T024 come last.

## Parallel opportunities

Few, and saying so is more useful than marking tasks that are not. One class, `app/Actions/BuildOpenApiDocument.php`, carries the whole mapping, so T007 to T013 and T017 to T018 are sequential by construction. What genuinely parallelises is T005 with T006, and T021 with T022.

## MVP

Phases 1, 2 and 3. That is T001 to T015, after which the document exists, is served, and a client can be generated from it, which is SC-001.
