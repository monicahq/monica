# Implementation Plan: MCP tools for templates, sections and fields

**Branch**: `2026-09-25-mcp-stuff` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

Nineteen new tools on `EricaServer`, one class each under `app/Mcp/Tools`, each mirroring an existing
`api.v1.templates.*` route by calling the same action and returning the same resource
([contracts/mcp-tools.md](contracts/mcp-tools.md)). The one gap in the API, creating a new section
inside a version, is closed by extending the existing add section endpoint
([contracts/api-section-create.md](contracts/api-section-create.md)). A likely API defect (a published
version answering 500 instead of 422 on section and field changes) is confirmed by a test and fixed
first ([research R4](research.md#r4-a-published-version-is-not-refused-by-the-section-and-field-endpoints-unconfirmed)).
`/docs/mcp` and its Markdown gain the tools and a short section on editing a template through a draft.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: `laravel/mcp` v1.0.0 (`Tool`, `Response`, `IsReadOnly`, `IsDestructive`)

**Storage**: MySQL/SQLite through existing models; no migration

**Testing**: PHPUnit. Unit tests per tool under `tests/Unit/Mcp/Tools`, feature tests for the API
change under `tests/Feature/Controllers/Api`, the existing MCP feature tests extended

**Target Platform**: the JSON API and the MCP endpoint at `/api/mcp`

**Project Type**: server rendered web application with a JSON API and an MCP server

**Constraints**: no rule of its own in a tool (principle X); refusals relayed word for word; no new
dependency or folder

**Scale/Scope**: 19 tools, 1 endpoint extended, up to 8 endpoints catching one more exception, 1 view
model, 1 Blade view, 1 API reference file, server instructions

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Tools and the extended controller call existing actions only |
| II. Simplicity | Pass. Three lookups added to the existing trait; no base class, no shared rule arrays ([R1](research.md#r1-how-a-tool-mirrors-an-endpoint)) |
| III. Tested | Pass. Every tool, the extended endpoint and the published version refusal are tested |
| IV. Laravel conventions | Pass. `$request->validate()`, named routes, no new route |
| V. Localized | Pass for the new "does not exist" sentences (`monica:localize`). Action refusal messages stay English, as in the API today ([R3](research.md#r3-refusals-from-actions)) |
| VI. Nothing changes under anybody's feet | Pass. Changes land on drafts; records move only on publish, through the existing action |
| VIII. Nothing disappears silently | Pass. Deletes use the existing actions and their refusals, and are marked destructive to clients |
| IX. Accessible | Pass. The docs page reuses its existing table and section components |
| X. Through the API | Pass. Every tool names a route that exists (`McpCatalogueTest`); the missing capability is added to the API first |
| XI. Short record | Pass. `data-model.md` omitted: nothing is stored or reshaped |

Re-checked after design: no change.

## Project Structure

```text
app/Mcp/Tools/MirrorsAnApiCapability.php      # + version(), section(), field(), page lookups
app/Mcp/Tools/{GetTemplate,CreateTemplate,UpdateTemplate,DeleteTemplate,SetTemplateActivation,
  SetTemplatePosition,DuplicateTemplate}.php
app/Mcp/Tools/{GetTemplateVersion,CreateTemplateDraft,PublishTemplateVersion,DeleteTemplateDraft}.php
app/Mcp/Tools/{AddTemplateSection,UpdateTemplateSection,RemoveTemplateSection,
  SetTemplateSectionPosition}.php
app/Mcp/Tools/{CreateTemplateField,UpdateTemplateField,DeleteTemplateField,
  SetTemplateFieldPosition}.php
app/Mcp/Tools/{List*,Get*}.php                # + #[IsReadOnly] on the ten existing read tools
app/Mcp/Servers/EricaServer.php               # register 19 tools; one paragraph of instructions
app/Http/Controllers/Api/StructureVersionSectionController.php  # create takes name/icon/colour
app/Http/Controllers/Api/{StructureVersionSection,StructureVersionSectionPosition,
  Field,FieldPosition}Controller.php          # catch PublishedVersionIsFrozen if R4 confirms
resources/docs/api/11-templates.php           # document the new inputs
app/ViewModels/Docs/McpDocsViewModel.php      # tool groups, new "Edit a template" section, markdown
resources/views/app/docs/mcp.blade.php        # draw the new section
lang/*.json                                   # new sentences via monica:localize
docs/FEATURES.md                              # feature entry

tests/Unit/Mcp/Tools/<Tool>Test.php           # one per new tool
tests/Feature/Controllers/Api/StructureVersionSectionControllerTest.php  # new section, refusals
tests/Feature/Controllers/Api/{...}Test.php   # published version refusal
tests/Feature/Mcp/{McpCompartmentTest,McpChangelogTest}.php  # one case each for templates
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php          # groups, count, new section
```

**Structure Decision**: follow the existing MCP tool layout exactly. Tool class names collide with
action names (`CreateTemplate`), which the existing tools already solve by importing the action
`as CreateTemplateAction`.

## Test plan by layer

Following `.claude/rules/testing.md`: the rule is tested where it lives.

- **Tool tests**: the happy path writes what it should and returns the resource; one refusal the tool
  relays (where the action has one); the "keeps" behaviour on the three update tools; the `mirrors()`
  name. Business rules of the actions are not tested again.
- **Compartment**: one test that a template tool with another account's template reports not found,
  and one that a section id from another template's version reports not found.
- **Changelog**: one test that a template change is recorded with source MCP.
- **API**: the new section form (created, refused on a published version with nothing written,
  `section_id` and `name` together refused); the published version refusal on each patched endpoint.
- **Docs**: the existing parity test covers the table; the "changes data" test is rewritten for the
  new groups; the Markdown test gains the new section.

## Complexity Tracking

None.
