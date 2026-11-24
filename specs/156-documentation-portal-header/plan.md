# Implementation Plan: Documentation portal header

**Branch**: `2026-11-24-documentation-portal-header` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

## Summary

Replace the body of `x-docs.header` with the marketing site's header and docs menu, translated to the
application's tokens (research R1), with marketing links built by a small helper (R2), the copy
through `__()` (R4), and the menu behaviour as one Alpine component (R5). The seven views that call
`<x-docs.header />` do not change.

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS 4.3, Alpine.js 3

**Primary Dependencies**: none added

**Storage**: none

**Testing**: PHPUnit: a helper unit test and a component view test

**Target Platform**: the documentation portal, every page under `/docs`

**Project Type**: web application

**Constraints**: no request to GitHub or the marketing site; works with scripts off; WCAG 2.2 AA

**Scale/Scope**: one component rewritten, one helper, one Alpine component, one glyph, five tokens,
72 phrases in 32 locales

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Not applicable: no business logic |
| II. Simplicity over abstraction | Pass: one helper for a table of URLs; no view model, no new component file beyond the one replaced |
| III. Tested before merged | Pass: helper test (locale mapping, fallback, slugs); view test (links, current item, signed in, registration closed, landmark) |
| IV. Laravel conventions | Pass: config value, named routes, `__()` |
| V. Localized and safe UX | Pass: every phrase in every locale; `monica:localize --check` clean (R4) |
| VI. Nothing changes under anybody's feet | Not applicable: no stored state |
| VII. Stable core | Not applicable |
| VIII. Nothing disappears silently | Pass: Sign in stays for a visitor; nothing removed |
| IX. Accessible to everybody | Pass by design: named landmark, native `<details>`, Escape returns focus to the button, `focus-ring` everywhere, `motion-reduce`; checked by the `accessibility-reviewer` agent |
| X. Everything goes through the API | Not applicable: no capability |
| XI. The written record is short | Pass; `data-model.md` omitted, nothing is stored |
| Self hosted and hosted are the same product | Pass: one header for both |

No violation. Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/156-documentation-portal-header/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── contracts/header.md
```

### Source Code (repository root)

```text
config/monica.php                                   # marketing_url
app/Helpers/MarketingSite.php                       # locale mapping, slug table, url()
resources/views/components/docs/header.blade.php    # rewritten
resources/views/components/icon.blade.php           # adds the `code` glyph from marketing
resources/js/header-menu.js                         # Alpine `headerMenu`
resources/js/app.js                                 # registers it
resources/css/app.css                               # identity colours, text-lede-sm, ease-pop
lang/*.json                                         # 54 new phrases
tests/Unit/Helpers/MarketingSiteTest.php
tests/Feature/Views/Components/Docs/HeaderTest.php
```

**Structure Decision**: the existing component keeps its name, so no call site moves. The menus stay
in the same file, since both menus are this header's alone (components rule: one file is not a
component).

## Complexity Tracking

None.
