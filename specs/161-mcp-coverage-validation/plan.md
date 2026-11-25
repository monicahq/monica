# Implementation Plan: MCP coverage and stricter section values

**Branch**: `2026-11-25-mcp-coverage-validation` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

## Summary

Four independent slices. Tighten `FieldValueRules` so a malformed repeating row or an unknown field is
refused everywhere, and document the row shape. Add three MCP tools over existing endpoints. Give
value lists eight API endpoints and eight tools over the existing actions. Raise the duration cap to a
year behind one constant and print durations in weeks, days, hours and minutes. Decisions are in
[research.md](research.md), shapes in [contracts/api.md](contracts/api.md). No migration.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major) · **Primary Dependencies**: laravel/mcp, Sanctum
**Storage**: unchanged, no migration · **Testing**: PHPUnit, `composer test:parallel` for the suite
**Project Type**: web application with a JSON API and an MCP server · **Scale/Scope**: 8 endpoints, 11 tools, 1 helper, 7 rule callers, 5 duration printers

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Every value list endpoint calls an existing action; no new action. |
| II. Simplicity | Pass. One helper (`Duration`), two resources; no new layer. |
| III. Tested | Pass. Action tests exist; new controller tests, the rule test, the helper test. |
| V. Localized | Pass. Four counted phrases through `trans_choice()`, translated by Sonnet subagents. |
| VI. Nothing changes under anybody's feet | Pass. Stored data unchanged; the API change is additive except the stricter rule, which only refuses what was silently lost. Recorded in the API changelog. |
| VIII. Nothing disappears silently | Pass; this spec is a fix for a breach of it. |
| X. Everything goes through the API | Pass; closes the value list gap. MCP tools hold no rule (`mcp.md`). |
| XI. Short record | Pass. No `data-model.md`: nothing is stored differently. |

## Project Structure

```text
specs/161-mcp-coverage-validation/  plan.md research.md quickstart.md contracts/api.md

app/Helpers/FieldValueRules.php                 # R1: top level and row keys
app/Actions/SetFieldValues.php                  # R3: a row's unknown_year
app/Helpers/Duration.php                        # new: cap and wording (R11)
app/Helpers/FieldValueFormatter.php             # duration wording
app/ViewModels/Vaults/{LifeEventDetailsViewModel,ContactTimelineBlock,ContactMarkdownViewModel}.php
app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php   # R13
app/Http/Requests/SaveLifeEventRequest.php      # cap
app/Http/Controllers/Api/{LifeEventController,GroupLifeEventController}.php  # cap
app/Http/Controllers/Api/ValueList{,Item,ItemActivation,ItemPosition}Controller.php
app/Http/Resources/ValueList{,Item}Resource.php
routes/api.php
app/Mcp/Tools/{DeleteContact,GetContactListColumns,SetContactListColumns}.php
app/Mcp/Tools/{Create,Update,Delete}ValueList{,Item}.php, SetValueListItem{Activation,Position}.php
app/Mcp/Tools/MirrorsAnApiCapability.php        # valueList(), valueListItem()
app/Mcp/Tools/UpdateContactSection.php          # row shape in the description
app/Mcp/Servers/MonicaServer.php, app/ViewModels/Docs/McpDocsViewModel.php
resources/views/components/field-input.blade.php, resources/views/app/vaults/contacts/_life-event-panel.blade.php  # max (R12)
resources/docs/api/                             # value list sections, section row shape, duration cap
lang/*.json                                     # four phrases, the catalogue sentence, the tool sentences

tests/Unit/Helpers/{FieldValueRulesTest,DurationTest}.php
tests/Feature/Controllers/Api/ValueList{,Item,ItemActivation,ItemPosition}ControllerTest.php
tests/Feature/Controllers/Api/ContactSectionControllerTest.php      # one malformed row reaches a 422
tests/Unit/Mcp/Tools/DeleteContactTest.php                           # only for its own sentence
```

**Structure Decision**: existing Laravel layout; nothing new at the top level.

## Order

1. `FieldValueRules` (R1, R4) and its test; run the whole suite, since seven callers and every
   section form go through it (`components.md`: a shared change is a change to every screen).
2. `SetFieldValues` honours a row's `unknown_year` (R3), with its action test.
3. `UpdateContactSection` description and the API docs of the section endpoint (FR-003).
4. The three contact tools, registered and grouped in `McpDocsViewModel`.
5. Value list resources, controllers, routes, docs, then the eight tools and the two lookups.
6. `Duration`, its five printers, the four caps, the two `max` attributes, the catalogue sentence.
7. Translations, the API changelog entry, the What's new entry, then `definition-of-done`.

## Complexity Tracking

None.
