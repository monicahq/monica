# Implementation Plan: QUERY requests in the API reference

> Superseded on 2026-11-23: QUERY requests no longer have pages of their own. Each is described on
> the page of its GET request. [research R12](research.md#r12-one-page-for-get-and-query-2026-11-23) has the design, and the
> [spec](spec.md) the requirements.

**Date**: 2026-11-22 | **Spec**: [spec.md](spec.md)

## Summary

Give every QUERY request of the JSON API its own entry in the reference portal (option B of the
clarification). Each QUERY entry is derived from the GET entry of the same address by one function,
so roughly 120 entries are added without a single hand written one. That function sits next to
`ApiDocsViewModel::offeredToEveryAccount()`, which the reference and the OpenAPI builder already
share, and it replaces the QUERY branch the OpenAPI builder holds today. Everything that walks the
sections (sidebar, previous and next, the Markdown copies, `llms.txt`, the sitemap) then lists the
QUERY entries with no change of its own.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: none added. Blade and Alpine for the portal, as today

**Storage**: none. The reference is read from `resources/docs/api/*.php`

**Testing**: PHPUnit, unit tests for the view model and the OpenAPI action, one feature test for the
page

**Target Platform**: the public docs portal at `/docs/api`

**Project Type**: web application (Laravel monolith)

**Performance Goals**: no measurable change. The derivation is an array walk over about 250 sections
held in memory per request, as the sections already are

**Constraints**: no hand written QUERY entry, no change to any existing entry address, no change to
what the OpenAPI document declares

**Scale/Scope**: 124 GET sections in 40 files today, so about 124 QUERY entries

## Constitution Check

| Principle | Status |
| --- | --- |
| X. Everything goes through the API, publicly documented | Met: this is the documentation of an existing capability. No endpoint changes |
| One rule, one place (actions reused, no second copy) | Met: one derivation, called by the reference and by the OpenAPI builder. The OpenAPI builder's own QUERY branch is deleted |
| Accessibility, WCAG 2.2 AA | Met: each sidebar link already holds its method badge, so its accessible name differs ("QUERY List contacts"). Each QUERY page has its own title ([research R3](research.md#r3-title-and-label)) |
| Flags (`.claude/rules/flags.md`) | Met: no new flag. A QUERY entry is derived after the flag filter, so it follows its GET entry |
| Translations | Not applicable: the API portal is written in English in the markup, like every portal string today |
| Testing rules (`.claude/rules/testing.md`) | Met: derivation proven in the view model test, the page proven to render once in its controller test, coverage enforced by the existing route tests |

No violation. Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/148-api-query-documentation/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── reference-entry.md
└── checklists/
    └── requirements.md
```

### Source Code (repository root)

```text
app/ViewModels/Docs/
├── ApiDocsViewModel.php        # withQuerySections(), groups() calls it, registeredRoutes() counts QUERY
├── ApiDocsSection.php          # querySectionId and derivedFromSectionId, read from the section array
└── ApiDocsMarkdown.php         # the two link sentences
app/Actions/
└── BuildOpenApiDocument.php    # calls withQuerySections(), its own QUERY branch removed
resources/views/components/api-docs/
└── endpoint.blade.php          # the two link sentences
resources/docs/api-changelog/
└── 2026-11-22.php              # the changelog entry
tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php
tests/Unit/Actions/BuildOpenApiDocumentTest.php          # registered() stops adding QUERY itself
tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php
tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php  # skips derived entries
```

**Structure Decision**: no new file under `app`. The derivation is one static method on the view
model that already owns the shared filter, and `ApiDocsSection` gains two nullable properties.

## Complexity Tracking

None.
