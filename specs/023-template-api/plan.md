# Implementation Plan: Template endpoints

**Branch**: `2026-09-05-api-templates` | **Date**: 2026-09-06 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/023-template-api/spec.md`

## Summary

Expose the template builder through the API in five phases, one shippable at a time, each with its
endpoints, its tests and its group in the docs portal. No new table and no new model: every action the
builder needs already exists, so the work is controllers, resources, routes, tests and documentation,
plus two corrections to existing actions that the research turned up.

## Technical Context

- **Language/Version**: PHP 8.4, Laravel 12
- **Primary Dependencies**: Sanctum for the bearer token, the existing `App\Actions\*` template actions, `App\ViewModels\Docs\ApiDocsViewModel` for the reference portal
- **Storage**: the existing `templates`, `template_versions`, `template_version_pages`, `template_version_sections`, `sections`, `section_versions` and `fields` tables. No migration.
- **Testing**: PHPUnit, `RefreshDatabase`, feature tests under `tests/Feature/Controllers/Api`
- **Target Platform**: the hosted and self hosted web application, served by Herd in development
- **Project Type**: web service endpoints added to an existing Laravel monolith
- **Performance Goals**: `templates.versions.show` answers a version, its pages, its placements and their fields in a bounded number of queries, eager loaded, never one per field
- **Constraints**: `throttle:60,1` as the sibling endpoints; page size from `monica.api_default_items_per_page` clamped to `monica.api_maximum_items_per_page`; the suite passes on SQLite, MySQL and PostgreSQL
- **Scale/Scope**: 26 endpoints across 5 phases, roughly 10 controllers and 6 resources

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I, actions hold the logic | No controller here writes. Each calls an existing action with named arguments. Two pieces of logic currently outside an action are moved into one (see Complexity Tracking). |
| II, simplicity | One controller per address, the five method names only, no base class or trait invented for the nesting. |
| III, tested before merged | Every controller gets a feature test covering its statuses, its validation failures and its cross account 404. The two action corrections get unit tests. |
| IV, Laravel conventions | Resources, `$request->validate()`, route model scoping through the account, no migration to write. |
| V, localized, and IX, accessible | No new user facing string and no new screen: the API answers statuses and validation messages, and the docs portal components it grows are already in the suite. |
| VI, nothing changes underfoot | The heart of the feature. Every write against a published or retired version is refused, and phase three's tests exist to prove it. |
| VII, stable core | Adds no second mechanism. It exposes Template, Page, Section, Field exactly as they are. |
| VIII, nothing disappears silently | Deleting a template is recoverable, removing a field detaches values rather than destroying them, and both are stated in the reference. |
| X, everything goes through the API | This is the principle being paid down, for the largest surface still browser only. |
| XI, the written record is short | `data-model.md` omitted, nothing restated between artifacts. The `spec.md` overrun is declared in the checklist and must be named in the pull request. |

## Project Structure

### Documentation (this feature)

```text
specs/023-template-api/
├── plan.md              # This file
├── spec.md              # The five phases
├── research.md          # Decisions, and where the skills are stale
├── contracts/
│   └── endpoints.md     # The 26 endpoints, their names and their statuses
├── quickstart.md        # How to prove a phase works
├── checklists/requirements.md
└── tasks.md             # Created by /speckit-tasks, not by this command
```

### Source Code (repository root)

```text
app/Http/Controllers/Api/
├── TemplateController.php                    # P1 index, show; P2 create, update, destroy
├── TemplateActivationController.php          # P2
├── TemplatePositionController.php            # P2
├── TemplateDuplicateController.php           # P2
├── TemplateVersionController.php             # P1 index, show; P3 create, destroy
├── TemplateVersionPublicationController.php  # P3
├── TemplateVersionPageController.php         # P4
├── TemplateVersionPagePositionController.php # P4
├── TemplateVersionSectionController.php      # P4
├── TemplateVersionSectionPositionController.php, …PageController.php  # P4
└── FieldController.php, FieldPositionController.php                   # P5

app/Http/Resources/
├── TemplateResource.php          # P1
├── TemplateVersionResource.php   # P1, and the nested read
├── TemplateVersionPageResource.php, TemplateVersionSectionResource.php, FieldResource.php

app/Actions/                      # Corrected, not created
├── CreateTemplateVersionDraft.php  # return the open draft instead of a second
└── PublishTemplateVersion.php      # absorb the section publishing walk

routes/api.php                    # 26 lines added, grouped by phase
resources/docs/api/11-templates.php  # one group, grown per phase
tests/Feature/Controllers/Api/    # one test class per controller
tests/Unit/Actions/               # the two corrected actions
```

**Structure Decision**: the API mirrors the web controllers under `Api` rather than nesting a
`Templates` folder, because every existing API controller is flat under `app/Http/Controllers/Api`
and the `Auth` subfolder is the only exception. Names carry the nesting instead.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Changing `PublishTemplateVersion`, an action this feature does not own | Publishing the draft sections a version carries lives in `TemplateVersionPublicationController`, so an API controller cannot publish anything the builder can without copying it. Principle I puts it in the action. | Copying the loop into the API controller leaves two copies of the rule that freezing depends on, and they will drift. |
| Changing `CreateTemplateVersionDraft`, likewise | Nothing stops a second draft, while `TemplateBuilderViewModel` and `TemplatesViewModel` both read the draft with `firstWhere`, so a second one is invisible. A retrying client would hit this immediately. | Guarding in the API controller alone leaves the browser able to create the unreachable draft, which is the defect rather than a symptom of it. |
| `spec.md` at 132 lines against the 120 line cap of Principle XI | Five phases were specified together at the request that produced the feature, under one set of requirements. | Five separate specs would restate the freezing rule, the account scoping rule and the ordering rule five times, which Principle XI forbids more strongly. |
