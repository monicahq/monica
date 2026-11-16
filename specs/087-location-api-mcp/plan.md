# Implementation Plan: Location API and MCP Tools

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-25 | **Spec**: [spec.md](spec.md)

## Summary

Five JSON API endpoints and five MCP tools over the three location actions of 086, unchanged. The tree
logic (order, path, inherited address, count) moves from `LocationsViewModel` into one helper,
`LocationTree`, so the screen, the API resource and the tools read the same answers. Documented in the
API reference and on the MCP page. Decisions in [research.md](research.md), shapes in
[data-model.md](data-model.md), endpoints and tools in [contracts/api-and-mcp.md](contracts/api-and-mcp.md),
checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Laravel Sanctum, Laravel MCP |
| Storage | No change; `locations` from 086 |
| Testing | PHPUnit: one API controller test, one test per MCP tool, the helper, the changelog over MCP |
| Scale | Tens of locations per account; the list is ordered and paged in memory (R3) |
| Constraints | Same rules and wording as the screen, nothing across accounts, refusals in the key owner's language |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. The controller and the tools call the 086 actions; no rule is written again |
| II Simplicity | Pass. One helper replaces logic that would otherwise exist three times (R2) |
| III Tested | Pass. Every endpoint and tool has a test; catalogue and docs tests guard the lists |
| IV Laravel | Pass. Inline validation, API resource, the sibling controller's shape |
| V Localized | Pass. Refusals reuse the 086 strings; new MCP strings go through `monica:localize` |
| VIII Nothing disappears | Pass. Deletion keeps 086's refusal and changelog entry |
| IX Accessible | Not touched; the screen does not change |
| X API | **Closes 086's deferral.** MCP mirrors the API and holds no rule. Webhooks: none exist yet (R10) |
| XI Short record | Pass |
| Invariant: vault is a compartment | Pass. Locations are account level; every lookup is scoped to the key's account |

Re-checked after design: unchanged. One finding outside the scope (R7): no JSON API change in the
application is recorded with the `api` source.

## Project Structure

```text
app/Helpers/LocationTree.php                         new, the tree of one account (R2)
app/ViewModels/Settings/Account/Locations/LocationsViewModel.php   reads LocationTree, same public API
app/Http/Resources/LocationResource.php              new (R4)
app/Http/Controllers/Api/LocationController.php      new (R1, R5, R6)
routes/api.php                                       five routes
app/Mcp/Tools/{List,Get,Create,Update,Delete}Location(s).php   new (R8)
app/Mcp/Tools/MirrorsAnApiCapability.php             location() lookup
app/Mcp/Servers/MonicaServer.php                      registration and one paragraph of instructions
app/ViewModels/Docs/McpDocsViewModel.php             a Locations group
resources/docs/api/25-locations.php                  API reference (R9)
lang/*.json                                          via `php artisan monica:localize`
docs/FEATURES.md                                     via the feature-documentator skill
tests/Unit/Helpers/LocationTreeTest.php
tests/Feature/Controllers/Api/LocationControllerTest.php
tests/Unit/Mcp/Tools/{List,Get,Create,Update,Delete}Location(s)Test.php
tests/Feature/Mcp/McpChangelogTest.php               one case for a location
spec/models/058-location-in-progress.md              drop "No API endpoint and no MCP tool"
```

## Order of work

1. `LocationTree` and its test; `LocationsViewModel` on top of it, its test unchanged and green.
2. Resource, controller, routes, controller test.
3. MCP lookup, five tools, registration, instructions, tool tests, changelog case.
4. API reference, MCP page, translations, PHPStan, Pint, the whole suite, quickstart by hand.

## Spec amendments made while planning

- US2.5: the API `PUT` clears what it leaves out, and so does the MCP update, which runs the same controller method (R9, replacing R6).
- FR-008: MCP changes are recorded as from the assistant; API changes as every other API endpoint's (R7).

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Extract `LocationTree` from a shipped view model | Three callers need the path and the inherited address | Copying it into the resource makes two paths that can disagree |
| List paged in memory | Tree order needs the whole account | SQL ordering puts children before parents |
