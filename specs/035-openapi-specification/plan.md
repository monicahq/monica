# Implementation Plan: OpenAPI description of the public API

**Branch**: `035-openapi-specification` | **Date**: 2026-09-15 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/035-openapi-specification/spec.md`

## Summary

The 19 reference files under `resources/docs/api` already record, per endpoint, everything OpenAPI needs: method, path, typed parameters with their requiredness, defaults and examples, the status answered and a worked response. They are already held to the routes in both directions by a test, and their response fields are already checked against real responses by another. So the document is produced from them rather than written beside them.

An action reads those files and builds an OpenAPI 3.2.0 document. A controller serves it at `/docs/api/v1/openapi.json`, built on the request. Nothing is written and nothing is committed, so no stored copy exists to fall out of step with the reference. The one drift still possible, between the reference and the routes, is the drift a test already catches in both directions.

## Technical Context

**Language/Version**: PHP 8.4 on the current major version of Laravel.

**Primary Dependencies**: none added. The document is an array the application builds and encodes, so no OpenAPI package enters `composer.json`.

**Storage**: none. Nothing is written and nothing is committed, and there is no database, no migration and no model. The document exists for the length of one request.

**Testing**: PHPUnit, as the suite everywhere. Nothing here touches the database, so `RefreshDatabase` is not needed outside the existing feature tests this change extends.

**Target Platform**: the same web application, self hosted and hosted alike. The document is a static file once written, so both deployments serve the identical bytes for a given release.

**Project Type**: a generated artifact of an existing web service, plus the command that writes it and the route that serves it.

**Performance Goals**: building the document reads and transforms the same 19 files the portal already reads to render a single documentation page, so one request costs about what a documentation page costs.

**Constraints**: OpenAPI 3.2.0, kept to plain constructs so that a tool supporting only part of the version still reads it. Code generation built on swagger-parser cannot read 3.2 at all, which [research D1](research.md) measures. No new dependency. `resources/docs` is the fixed home the constitution names for the API reference, so nothing new is created beside it. The suite must pass on SQLite, MySQL and PostgreSQL, which this change does not touch.

**Scale/Scope**: 109 routed endpoints, 153 authored sections across 19 files and 4,733 lines, 22 response shapes. The built document is the largest thing the application assembles for one request, which is worth measuring once it exists rather than guarding against before it does.

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1 design. No violation, so Complexity Tracking is empty and omitted.*

| Principle | How this change satisfies it |
| --- | --- |
| I, Actions hold the business logic | Building the document is an action. The command and the controller add transport and nothing else, so a future caller building it for another reason reaches the same class. |
| II, Simplicity over abstraction | One action and one controller, with no layer interposed between the page format and OpenAPI and no artifact to keep in step. The mapping is a transformation written once, not a framework. |
| III, Tested before merged | The action and the controller each get a test, and the document's agreement with the routes is asserted by the suite rather than left to a reviewer. |
| IV, Laravel conventions | camelCase route name, kebab-case public URL, typed returns throughout, early returns, one class per file. |
| V, Localized and safe UX | The document carries the reference's own English, which is what the reference already is. The one reader-facing addition is a link on an existing page, and its wording goes through `__()` like every other string on it. |
| IX, Accessible to everybody | No screen is added. The link required by FR-016 sits on a page that already meets the bar and is an ordinary link, keyboard reachable and announced as its own text. |
| X, Everything goes through the API | This feature documents the API rather than adding to it. The document is fetched over HTTP without a credential, so it is reachable by the tooling it exists for. |
| XI, The written record is short | `spec.md` is at its cap, `plan.md` and `research.md` are under theirs, `data-model.md` carries the mapping that no other artifact holds, and `contracts/` is omitted because the document this feature produces is the contract and a 60 line excerpt of it would be the stale artifact `specs/003-api-docs-portal/contracts/openapi.yaml` already is. |

Principles VI, VII and VIII do not apply: nothing here is versioned under a record, no personalisation mechanism is introduced, and nothing is deactivated or removed.

## Project Structure

### Documentation (this feature)

```text
specs/035-openapi-specification/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output, the page format to OpenAPI mapping
├── quickstart.md        # Phase 1 output
├── checklists/
│   └── requirements.md  # Written by /speckit-specify, all items pass
└── tasks.md             # Phase 2 output, written by /speckit-tasks
```

`contracts/` is deliberately absent. See the Constitution Check above.

### Source Code (repository root)

```text
app/
├── Actions/
│   └── BuildOpenApiDocument.php          # new: reads the reference files, returns the document as an array
└── Http/Controllers/App/Docs/
    ├── ApiDocsController.php             # unchanged
    ├── ApiDocsMarkdownController.php     # unchanged
    └── OpenApiDocumentController.php     # new: builds the document and answers with it

resources/docs/api/                       # the 19 authored reference files, the source of truth
└── *.php                                 # unchanged: read as raw arrays, gaining no key

routes/web.php                            # new route, declared before the docs/api/{section} routes

tests/
├── Unit/Actions/BuildOpenApiDocumentTest.php
└── Feature/Controllers/App/Docs/OpenApiDocumentControllerTest.php
```

**Structure Decision**: every file lands in a folder the constitution already names, so no amendment is owed. The action holds the logic, per principle I, which is why the builder is not a view model: `app/ViewModels` is read-only and Blade-only by the same principle, and this output never reaches Blade.

The route is registered in `routes/web.php` before `docs/api/{section}`, alongside the existing `docs/api.md` route, which is the same ordering problem solved the same way.
