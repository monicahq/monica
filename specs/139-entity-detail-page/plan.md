# Implementation Plan: Record of an invented type, read and edited

**Branch**: `2026-11-19-changelog-panda-redesign` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/139-entity-detail-page/spec.md`

## Summary

A page per record drawn from its own template version (design 1a), a full-page edit form built on the
creation form, an `UpdateEntity` action on top of `SetFieldValues`, the API read and update of one record
with its values, four MCP tools, and the API and product documentation. Decisions in
[research.md](research.md); the external interface in [contracts/api.md](contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS v4, Alpine.js

**Primary Dependencies**: Laravel MCP, Sanctum; components `x-shell`, `x-panel`, `x-detail-list`, `x-field-value`, `x-entity-icon`, `x-caption`

**Storage**: no migration; `entities`, `field_values`, `field_value_rows` as they are

**Testing**: PHPUnit; action, view model, controller (web and API), MCP wiring per `.claude/rules/mcp.md`

**Target Platform**: web light and dark, 320px up; JSON API v1; MCP server

**Project Type**: Laravel monolith

**Performance Goals**: the page loads values, rows and fields with a fixed number of queries whatever the field count

**Constraints**: WCAG 2.2 AA; record stays on its version; no query string for pages

**Scale/Scope**: 1 action, 2 view models (1 new), 2 controllers extended, 4 MCP tools, 2 views, docs

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions | Pass. `UpdateEntity` holds the rule; web, API and MCP call it |
| II. Simplicity | Pass. Reuses `SetFieldValues`, `x-field-value`, the creation form |
| III. Tested | Pass. Every new class has its test at its layer |
| IV. Laravel conventions | Pass. One route per line, inline validation, camelCase names |
| V. Localized | Pass. All copy through `__()`, translated by Sonnet subagents |
| VI. Nothing changes underfoot | Pass. Edit keeps the record's version |
| VII. One mechanism | Pass. Template, Page, Section, Field only |
| VIII. Nothing disappears | Pass. Fields left out of an API update are kept |
| IX. Accessible | Pass. `nav` with `aria-current`, `dl` rows, field types render their own accessibility |
| X. API | Pass. Read and update through API and MCP in the same change |
| XI. Short record | `data-model.md` omitted: no new data |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/139-entity-detail-page/
├── spec.md  plan.md  research.md  quickstart.md  tasks.md
└── contracts/api.md
```

### Source Code (repository root)

```text
app/Actions/UpdateEntity.php                          # new
app/Enums/UserActionEnum.php                          # EntityUpdate case
app/ViewModels/Vaults/EntityViewModel.php             # new: header, pages, sections, fields
app/ViewModels/Vaults/EntitiesViewModel.php           # row url
app/ViewModels/Vaults/EntityFormViewModel.php         # optional Entity: prefill, PUT, title
app/Http/Controllers/App/Vaults/EntityController.php  # show, edit, update
app/Http/Controllers/Api/EntityController.php         # show, update
app/Http/Resources/EntityResource.php                 # fields, links
app/Mcp/Tools/{ListEntities,CreateEntity,GetEntity,UpdateEntity}.php
app/Mcp/Tools/MirrorsAnApiCapability.php              # entityType(), entity()
routes/web.php, routes/api.php
resources/views/app/vaults/entities/{show,index,form}.blade.php
resources/docs/api/42-entities.php, resources/docs/api-changelog/2026-11-19.php
docs/portal/en/3-people/6-profiles.md, docs/portal/en/9-templates-and-customisation/9-profile-types.md
tests/Unit/Actions/UpdateEntityTest.php, tests/Unit/ViewModels/Vaults/EntityViewModelTest.php
tests/Feature/Controllers/App/Vaults/EntityControllerTest.php, tests/Feature/Controllers/Api/EntityControllerTest.php
lang/*.json, changelog/2026-11-19-*-profile-page.md
```

**Structure Decision**: existing Laravel layout; the record page mirrors the contact page's split of
controller, view model and partial.

## Complexity Tracking

None.
