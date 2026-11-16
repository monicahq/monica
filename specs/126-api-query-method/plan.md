# Implementation Plan: QUERY method on the API

**Branch**: `126-api-query-method` | **Date**: 2026-11-16 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/126-api-query-method/spec.md`

## Summary

Every read route in `routes/api.php` is declared for GET and QUERY. One middleware, on the
authenticated group, refuses a parameter sent twice and copies a QUERY body into the address
parameters, so every controller reads it unchanged. The reference gains a guide and one line per
read endpoint, the OpenAPI document gains a `query` operation per `get`, and the changelog gains an
entry. [research.md](research.md) holds the seven decisions.

## Technical Context

**Language/Version**: PHP 8.4 on Laravel 13.40, which already routes QUERY (`Router::$verbs`).

**Primary Dependencies**: none added.

**Storage**: none. No migration.

**Testing**: PHPUnit. The suite must pass on SQLite, MySQL and PostgreSQL, which this change does not touch.

**Target Platform**: the existing web server. Whether a proxy passes QUERY is outside the suite (research D7).

**Project Type**: web service, the JSON API under `/api/v1`.

**Constraints**: no GET answer changes (SC-003). No new dependency.

**Scale/Scope**: 112 read routes, 50 address-only parameter reads, left unchanged.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No business rule is added, and the middleware is transport. |
| II. Simplicity | Pass. One middleware, one route verb list, no new abstraction. |
| III. Tested before merged | Pass. Tests below. |
| IV. Laravel conventions | Pass. `Route::match` and middleware are framework idiom. |
| V. Localized | Pass. The one new message goes through `monica:localize`. |
| VI. Nothing changes under anybody's feet | Pass. Additive, and GET is untouched. |
| X. Everything goes through the API | Pass. This widens the API, and the reference and OpenAPI follow. |
| XI. Short written record | Pass. No data model and no contract file, since the spec carries the contract. |

Post-design re-check: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/126-api-query-method/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `routes/api.php` | 112 `Route::get(` become `Route::match(['GET', 'QUERY'], `; the authenticated group adds the middleware |
| `app/Http/Middleware/MergeQueryRequestBody.php` | New: 422 on a key in both places, then merge the body (D2) |
| `app/ViewModels/Docs/ApiDocsViewModel.php` | `registeredRoutes()` skips QUERY (D4) |
| `app/ViewModels/Docs/ApiDocsMarkdown.php` | One line on a GET section (D4) |
| `resources/views/components/api-docs/endpoint.blade.php` | One line on a GET section (D4) |
| `resources/docs/api/01-getting-started.php` | New guide section (D4) |
| `app/Actions/BuildOpenApiDocument.php` | `query` operation per `get` (D5) |
| `resources/docs/api-changelog/<release date>.php` | New entry (D6) |
| `lang/*.json` | The 422 message, through `monica:localize` |

**Structure Decision**: existing folders only. The middleware is aliased `query.body` in
`bootstrap/app.php`, as the middlewares skill requires, and `.claude/CLAUDE.md` lists the alias.

## Tests

| Layer | File | Proves |
| --- | --- | --- |
| Routes | `tests/Feature/ApiQueryMethodTest.php` | Every `api/v1/` route holding GET holds QUERY (FR-001, FR-011); a write-only path answers QUERY with 405 (FR-007) |
| Middleware | same file | Body parameters reach an address-only reader (contacts list, `per_page`); a key in both places is 422; an unauthenticated QUERY is 401; a form body works |
| Docs | `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` | Parity tests still pass with QUERY routes |
| Docs | `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` | A GET page shows the QUERY line; a POST page does not |
| OpenAPI | `tests/Unit/Actions/BuildOpenApiDocumentTest.php` | A GET path carries `query` with a request body; a POST path does not |

The existing API controller tests, unchanged and green, prove SC-003.

## Complexity Tracking

None.
