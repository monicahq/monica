# Implementation Plan: Contact relationships over the API and MCP

**Branch**: `2026-10-23-mcp-contact-relationships` | **Date**: 2026-10-23 | **Spec**: [spec.md](spec.md)

## Summary

Expose the existing relationship actions through five API endpoints under a contact, then mirror each
endpoint with an MCP tool, and document both. No schema change, no new action, no rule change.
Decisions are in [research.md](research.md); the wire shape is in [contracts/api.md](contracts/api.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), `laravel/mcp` |
| Storage | Existing `relationships` table, no migration |
| Testing | PHPUnit: action tests exist; new controller, tool and docs tests |
| Project type | Web application with a JSON API and an MCP server |
| Scale | 5 routes, 1 controller, 1 resource, 5 tools, 2 doc pages |

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: the three existing actions are called unchanged |
| II. Simplicity | Pass: copies the reminder controller and tools; no new abstraction |
| III. Tested before merged | Pass: controller, tool and docs tests listed below |
| IV. Laravel conventions | Pass: inline validation, one named route per line, camelCase names |
| V. Localized | Pass: action refusals reuse translated strings; new ones (R3, R7, tool and docs copy) go through `monica:localize` |
| VI, VII, VIII | Not touched: no structure, no new concept, nothing deactivated |
| IX. Accessible | Not touched: no screen changes |
| X. Through the API | Pass: this feature closes the gap principle X names |
| XI. Short record | Pass: no data model file (no schema change); caps respected |

Re-checked after design: unchanged.

## Project Structure

```text
routes/api.php                                        5 routes after the reminder block
app/Http/Controllers/Api/ContactRelationshipController.php   index, create, show, update, destroy
app/Http/Resources/RelationshipResource.php
app/Mcp/Tools/MirrorsAnApiCapability.php              + relationship(Contact, string)
app/Mcp/Tools/{List,Get,Create,Update,Delete}ContactRelationship(s).php
app/Mcp/Servers/EricaServer.php                       register tools, one instructions paragraph
app/ViewModels/Docs/McpDocsViewModel.php              two groups: Relationships, reading / changing
resources/docs/api/23-relationships.php               API reference page
lang/*.json                                           via php artisan monica:localize

tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php
tests/Unit/Mcp/Tools/{List,Get,Create,Update,Delete}ContactRelationship(s)Test.php
```

**Structure Decision**: follows the reminder capability file for file (research R2).

## Test scope

Following `.claude/rules/testing.md`, rules already proven in `CreateRelationshipTest`,
`UpdateRelationshipTest` and `DestroyRelationshipTest` are not tested again.

| Test | Proves |
| --- | --- |
| Controller | each route answers with its shape; inline validation (`prohibits`, `status`, dates); one action refusal reaches the client as 422; wrong vault and a non-involved contact give 404; list leaves out other contacts' relationships; update keeps absent fields; naming a non-placeholder gives 422 |
| Each tool | it calls through and answers the resource shape; one refusal comes back as an error; not found wording |
| `McpCatalogueTest` | already checks mirrors, registration and read only / destructive annotations |
| Docs tests | existing API docs and MCP docs tests cover the new page and groups |

## Order

1. Resource, controller, routes, controller test (US1 to US5 over the API).
2. Trait helper and five tools, tool tests, server registration (US1 to US5 over MCP).
3. API reference page, MCP docs groups, server instructions paragraph (US5).
4. `monica:localize`, PHPStan, Pint, full suite.

## Risks

- Contact slugs are unique per vault only; `other_contact` is always looked up inside the URL's
  vault, so no ambiguity (R4).
- The unmerged web links branch touches `EricaServer` and the docs; expect a small merge (R10).

## Complexity Tracking

None.
