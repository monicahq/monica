# Implementation Plan: Document head metadata through Laravel Head

**Branch**: `claude/laravel-head-integration-0c9ecc` | **Date**: 2026-11-04 | **Spec**: [spec.md](spec.md)

## Summary

Install `laravel/head` and let `partials/meta` render its tags through `@head`. Every page defaults
to noindex and error pages are forced to it. One middleware lets the docs, the guide and the
marketplace be indexed and canonical, unless `MONICA_SEARCH_ENGINE_INDEXING` is false. Private links
are nofollow through route metadata. See [research.md](research.md) and [contracts/head.md](contracts/head.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13.35, Blade |
| New dependency | `laravel/head`, required (R1) |
| Storage | None. No migration, no model |
| Testing | PHPUnit: one feature test over the rendered head, view model tests for the guide |
| Project type | Web application; the head only concerns the Blade screens |
| Scale | 1 middleware, 1 config key, 1 partial, 7 views, 2 view models, 73 guide pages, no new string |

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: no user operation is added; the middleware is transport |
| II. Simplicity | Pass: one partial stays the one place; no wrapper around the package (R2) |
| III. Tested | Pass: test scope below |
| IV. Laravel conventions | Pass: documented `Head` facade, `withHead()`, `env()` only in `config/` |
| V. Localized | Pass: descriptions reuse translated lead sentences; guide descriptions per locale file |
| VI. Nothing changes underfoot | Pass: titles and every existing tag unchanged (FR-006) |
| VII. Stable core | Not concerned |
| VIII. Nothing disappears silently | Not concerned |
| IX. Accessible | Pass: no visible change; `lang` attributes untouched |
| X. Through the API | Not concerned: the head is HTML only; the API, `.md` twins and OpenAPI are unchanged |
| XI. Short record | Pass: no `data-model.md`, since nothing is stored |
| Same product everywhere | Pass: the switch is an instance setting, read on both deployments (R6) |

Re-checked after design: unchanged.

## Project Structure

```text
composer.json, composer.lock          laravel/head
config/monica.php, .env.example       search_engine_indexing, MONICA_SEARCH_ENGINE_INDEXING (R6)
app/Providers/AppServiceProvider.php  configureHead(): Head::defaults() and Head::errors() (R3)
app/Http/Middleware/MarkPageSearchable.php   robots all, and marks the request for a canonical (R4)
bootstrap/app.php                     alias searchable
routes/web.php                        searchable on the docs and marketplace routes; withHead on share.* (R5)
resources/views/partials/meta.blade.php      title and description handed to Head, then @head (R2)
resources/views/app/docs/{index,carddav,mcp,portal}.blade.php, app/docs/api/show.blade.php
resources/views/layouts/marketplace.blade.php, app/marketplace/index.blade.php    description (R7)
app/ViewModels/Docs/PortalCatalogue.php      description required in front matter (R7)
docs/portal/en/**/*.md                description in every front matter
lang/*.json                           via monica:localize
tests/Feature/HeadMetadataTest.php    robots per kind of page, the switch, canonical, og (R9)
tests/Unit/ViewModels/Docs/{PortalCatalogueTest,PortalDocsViewModelTest}.php    both exist; extended
```

**Structure Decision**: existing folders only. `app/Http/Middleware` holds thirteen classes, not the
six `CLAUDE.md` claims; adding a fourteenth, the pull request corrects that line.

## Order

1. Install, defaults and errors, `partials/meta` on `@head`: every page turns noindex (US1, P1).
2. `withHead` on the share routes (US1).
3. Config key, middleware, alias, routes: public pages indexable and canonical (US1, US2).
4. Descriptions: the lead sentences each page shows, then the guide front matter (US2).
5. Localize, PHPStan, Pint, the full suite (a shared partial changes every screen).

## Test Scope

- Feature, through a data provider: a vault screen, settings, admin, sign in, a private link, its
  password form, a 404 under `/docs`, the docs index, an API section, a guide page, the marketplace.
- Feature: the same public routes with the setting off answer noindex and no canonical.
- Feature: a query string does not reach the canonical; an http request keeps an http canonical.
- Feature: one screen per layout keeps its `<title>`, csrf token and manifest (SC-003).
- Unit: the catalogue reads `description` and leaves out a page without one; the view model pairs
  pages across locales by `id`.

## Risks

- The package may render a tag `partials/meta` already writes (icons, a second title). The first
  task renders a screen per layout and diffs the head before and after (SC-003).
- Whether `title(..., exact: true)` drops the default when the partial passes `null` is not
  documented. The partial calls `Head::title()` only when a title is given.
- Writing 73 descriptions is most of the work; it can land as its own commit inside the change.
