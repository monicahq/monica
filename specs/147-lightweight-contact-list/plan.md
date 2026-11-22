# Implementation Plan: A lightweight list of contacts

**Branch**: `2026-11-22-lightweight-contact-list` | **Date**: 2026-11-22 | **Spec**: [spec.md](spec.md)

## Summary

A new read only endpoint lists the contacts of one vault with four fields each (id, name, partial,
archived), partial and full together by default, archived left out unless asked for, paged up to 500
entries. An MCP tool mirrors it. Decisions are in [research.md](research.md), the endpoint and the
tool in [contracts/contact-summaries-api.md](contracts/contact-summaries-api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `laravel/mcp`, Sanctum. No new dependency

**Storage**: none. No migration, no new model; reads `contacts` (R5)

**Testing**: PHPUnit, `composer test:parallel`

**Project Type**: web application with a JSON API and an MCP server

**Performance Goals**: one query and one count per page, no relation loaded (R5)

**Constraints**: the tool holds no rule of its own (`.claude/rules/mcp.md`)

**Scale/Scope**: one controller, one route, one tool, the API reference, the MCP docs family

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. A read with no business rule, so no action, like `ContactController::index()` |
| II. Simplicity | Pass. One controller method, no resource class: four fields are built inline (R1) |
| III. Tested | Pass. See Tests |
| IV. Laravel conventions | Pass. One named route per line, inline validation, `Model::query()` |
| V. Localized | Pass. "Unnamed contact" and the validation messages already exist in every locale |
| VI. Nothing changes underfoot | Pass. `api.v1.contacts` and `list-contacts` are untouched (R1) |
| VIII. Nothing disappears silently | Pass. Archived contacts become reachable through the API, opt in (R3) |
| IX. Accessible | Not concerned: no screen changes |
| X. Through the API | Pass. The capability is the API; the tool mirrors it |
| XI. Short record | Pass. `data-model.md` and `quickstart.md` omitted: the contract holds the entry, Validation below is the run guide |

Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Http/Controllers/Api/ContactSummaryController.php` | new, `index` per R3 to R5 and the contract |
| `config/monica.php` | the two page sizes of R4 |
| `routes/api.php` | `Route::match(['GET', 'QUERY'], 'vaults/{vault}/contact-summaries', ...)->name('contactSummaries')`, beside `contact-categories` |
| `app/Mcp/Tools/ListContactSummaries.php` | new, mirrors `api.v1.contactSummaries` (contract) |
| `app/Mcp/Servers/MonicaServer.php` | registers the tool next to `ListContacts` |
| `app/ViewModels/Docs/McpDocsViewModel.php` | `list-contact-summaries` in the Contacts family |
| `resources/docs/api/06-contacts.php` | the endpoint (`api-docs-writer` skill) |
| `resources/docs/api-changelog/` | the day's entry (`api-docs-writer` skill) |
| `changelog/` | one What's new entry (`changelog` skill) and its translations |

## Tests

| Test | Proves |
| --- | --- |
| `tests/Feature/Controllers/Api/ContactSummaryControllerTest.php` | the structure; full and partial together, archived and deleted left out (US1-1); `filter=partial` and `filter=full` (US2-1, US2-2); `archived=included` marks only the archived one (US2-4); an unknown `filter` or `archived` answers 422 (US2-3); paging and the 500 cap; another account's vault answers 404 |

The tool adds nothing beyond a lookup and `answerThroughApi()`, so it gets no test file
(`.claude/rules/mcp.md`). `McpCatalogueTest` covers its route, and the walk over every tool covers
another account's vault.

## Validation

1. `composer test:parallel` green, Pint, PHPStan, `php artisan monica:localize --check` clean.
2. With a key, `curl /api/v1/vaults/{vault}/contact-summaries`: full and partial contacts, no archived one.
3. The same with `?filter=partial`, then `?archived=included`, then `?filter=nope` (422).
4. From an assistant connected over MCP, "list everybody in my vault" calls `list-contact-summaries`.
