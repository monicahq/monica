# Implementation Plan: Template version history

**Branch**: `2026-09-05-template-history` | **Date**: 2026-09-05 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/022-template-version-history/spec.md`

## Summary

A read-only screen listing every version of one template, newest first, each expandable into its pages, the section versions placed on them, and their fields. Reached from a **Version history** link in the builder header, shown only when the template has more than one version. No schema change: `template_versions`, `template_version_pages`, `template_version_sections`, `section_versions` and `fields` already hold everything, and `Template::versions()` already orders newest first. The work is one route, one controller, one view model with a per-version block, one Blade screen, and one line in the builder header.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Blade, Tailwind CSS 4, Alpine.js, Alpine Ajax

**Storage**: MySQL. No migration; the feature reads tables shipped in `2026_08_09_*`.

**Testing**: PHPUnit. Feature test for the controller and the builder link, unit tests for the view model and its block.

**Target Platform**: Laravel Herd at `http://monica.test`, the logged-in settings area.

**Project Type**: Server-rendered web application.

**Performance Goals**: One screen render, no N+1. The whole tree is eager loaded in one nested `with()` and expanded client-side.

**Constraints**: Read-only; the screen MUST NOT write. WCAG 2.2 AA. Every new string goes through `monica:localize` and is translated into the nine configured locales.

**Scale/Scope**: One screen, one link. Bounded by versions per template, in practice single digits.

## Constitution Check

| Principle | Gate | Verdict |
| --- | --- | --- |
| I. Actions hold the business logic | Nothing is written, so no action. The view model reads and holds presentation logic only, and is consumed by Blade alone. | Pass |
| II–IV. Structure, migrations | No migration, no new base folder. Files land in the existing `Settings/Account/Templates` groupings. | Pass |
| V. Mono-user compartments | `Template` uses `ResolvesModelInAccount` with `#[RouteKey('slug')]`, so another account's template is not found without any controller code. | Pass |
| IX. Accessible to everybody | Expanders follow `_email-sent-row.blade.php`: a real `<button>` carrying `:aria-expanded`, visible focus ring, content in the DOM. Status is carried by a worded badge, never colour alone. Verified by keyboard and at 200% zoom. | Pass |
| X. Everything goes through the API | No API. See Complexity Tracking. | **Deviation** |
| XI. The written record is short | `spec.md` 90, this plan under 100, `research.md` under 80. `data-model.md` and `contracts/` omitted. | Pass |

## Project Structure

### Documentation (this feature)

```text
specs/022-template-version-history/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── quickstart.md        # Phase 1 output
├── checklists/requirements.md
└── tasks.md             # Written by /speckit-tasks
```

`data-model.md` is omitted: the feature stores nothing new, and the spec's Key Entities plus the shipped migrations already carry the model. `contracts/` is omitted: the feature exposes no interface beyond one HTML screen, per the deviation below.

### Source Code (repository root)

```text
app/
├── Http/Controllers/App/Settings/Account/Templates/
│   └── TemplateVersionHistoryController.php     # new, index only
└── ViewModels/Settings/Account/Templates/
    ├── TemplateVersionHistoryViewModel.php      # new, the screen
    ├── TemplateVersionEntry.php                 # new, one version's block
    └── TemplateBuilderViewModel.php             # edit: hasHistory()

resources/views/app/settings/account/templates/
├── versions.blade.php                           # new, the screen
├── _version.blade.php                            # new, one entry
└── builder.blade.php                            # edit: the link

routes/web.php                                   # edit: one GET
lang/*.json                                      # 9 locales, via monica:localize

tests/
├── Feature/Controllers/App/Settings/Account/Templates/TemplateVersionHistoryControllerTest.php
└── Unit/ViewModels/Settings/Account/Templates/
    ├── TemplateVersionHistoryViewModelTest.php
    └── TemplateVersionEntryTest.php
```

**Structure Decision**: the existing settings/templates grouping, mirrored across controllers, view models, views and tests. The screen is rich enough that the main view model delegates one version to `TemplateVersionEntry`, as the view-models skill requires for a page with repeated blocks.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle X: no API coverage for reading template versions | The templates domain has no API at all today: `routes/api.php` and `app/Http/Resources` cover contacts, vaults, entity types, logs, emails, keys and the profile, and nothing under templates. Serialising the nested page → section version → field tree, plus its `resources/docs/api` reference, is the size of the entity-types API (#136) on its own and is its own feature. | Adding two read endpoints here still needs the full nested resource and the docs page, and would leave the write half of the builder unexposed, so it buys no completeness. Recorded so it is spec'd rather than forgotten. |
