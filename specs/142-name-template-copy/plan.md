# Implementation Plan: Name a template copy before it is made

**Branch**: `142-name-template-copy` (work stays on the current branch) | **Date**: 2026-11-20 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/142-name-template-copy/spec.md`

## Summary

The Copy button on the list of templates creates the copy at once and gives it a slug the person never sees and can never change. The plan puts a form between the button and the copy: a new screen asking for the name and the slug, prefilled with what the copy would get today, validated the way the creation form validates a slug, and posting to the existing copy route. `DuplicateTemplate`, the API endpoint and the MCP tool already take a name and a slug and do not change.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest), Blade, Alpine.js, Tailwind CSS

**Primary Dependencies**: none new

**Storage**: no schema change. The `templates.slug` column, its length (`Template::SLUG_LENGTH`, 64) and its per account uniqueness stay as they are

**Testing**: PHPUnit. Feature tests for the controller, a unit test for the new view model

**Target Platform**: web application, settings area

**Project Type**: web application (Laravel monolith)

**Performance Goals**: the copy form renders with one query for the template and one for the free slug, like the creation form

**Constraints**: the slug stays fixed after creation (FR-011); API and MCP behaviour unchanged (FR-012); WCAG 2.2 AA

**Scale/Scope**: one new screen, one new view model, one controller method added, one route added, one button changed, one guide step rewritten, one changelog entry

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | Why |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | The copy stays in `DuplicateTemplate`. The controller validates and delegates |
| II. Simplicity over abstraction | Pass | A dedicated screen and view model rather than a third mode bolted onto `TemplateFormViewModel` (research R2) |
| III. Tested before merged | Pass | Controller and view model tests listed in quickstart |
| IV. Laravel conventions | Pass | `new` and `create` on one controller, inline validation, named routes |
| V. Localized and safe UX | Pass | New strings go through `__()`, translated by Sonnet subagents; nothing destructive |
| VI. Nothing changes under anybody's feet | Pass | The slug of an existing template stays fixed, which is the reason for asking before creating. The original template is untouched |
| VII. Stable core, personalisation | Pass | Copying is how a supplied template is personalised; this makes that copy addressable by a name the person chose |
| VIII. Nothing disappears silently | Pass | A taken or invalid slug is refused with a reason, never silently rewritten on the screen (research R4) |
| IX. Accessible to everybody | Pass | Labelled inputs through `x-input`, errors tied to their field, keyboard reachable; the Copy control becomes a link |
| X. Everything goes through the API | Pass | `POST /templates/{template}/duplicate` already takes `name` and `slug`, documented in `resources/docs/api/11-templates.php`; the MCP tool mirrors it. No API change, so no API changelog entry |
| XI. The written record is short | Pass | Spec, plan and supporting files only |

No violation, so Complexity Tracking stays empty.

**Post-design re-check**: unchanged. The design adds no rule outside the action and no endpoint.

## Project Structure

### Documentation (this feature)

```text
specs/142-name-template-copy/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── web-routes.md
└── checklists/
    └── requirements.md
```

### Source Code (repository root)

```text
app/
├── Http/Controllers/App/Settings/Account/Templates/
│   └── TemplateCopyController.php          # adds new(), create() validates name and slug
└── ViewModels/Settings/Account/Templates/
    └── TemplateCopyViewModel.php           # new: the template, the prefilled name and slug

resources/views/app/settings/account/templates/
├── copy.blade.php                          # new: the copy form
└── _row.blade.php                          # Copy becomes a link to the form

routes/web.php                              # adds GET settings.templateCopy.new

lang/*.json                                 # new strings, translated by Sonnet subagents

docs/portal/en/9-templates-and-customisation/
└── 3-build-your-own-template.md            # Step 1 names the copy, Step 2 no longer renames it

What's new entry                            # per the changelog skill

tests/
├── Feature/Controllers/App/Settings/Account/Templates/
│   └── TemplateCopyControllerTest.php      # extended
└── Unit/ViewModels/Settings/Account/Templates/
    └── TemplateCopyViewModelTest.php       # new
```

**Structure Decision**: the existing Laravel layout. Every file sits next to its siblings for templates.

## Complexity Tracking

None.
