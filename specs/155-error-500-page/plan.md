# Implementation Plan: Error 500 page

**Branch**: `2026-11-24-error-500-page` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/155-error-500-page/spec.md`

## Summary

Add `resources/views/errors/500.blade.php`, built on the existing `x-error-page` shell exactly like the
404 page: two illustrations (daylight and night) swapped by the `dark:` variant, the heading and line
from FR-003, a primary "Try again" button and a secondary "Return home" button. Laravel already
renders `errors/500` for every server failure on a page request when debug mode is off, so no route,
class or exception configuration changes. The two illustrations are cropped from the supplied
mockups into `public/images`. One glyph (`reload`) is added to `x-icon` for the "Try again" button.
The four existing error pages and their shell are left as they are.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major), Blade, Tailwind CSS 4, Alpine.js

**Primary Dependencies**: none added. Uses `x-error-page`, `x-button`, `x-icon`, `x-guest-layout`

**Storage**: N/A. Two static image files under `public/images`

**Testing**: PHPUnit feature tests in `tests/Feature/Views/Errors/ErrorPageTest.php`

**Target Platform**: Web, every browser the application supports

**Project Type**: Web application (server rendered)

**Performance Goals**: Each illustration under 150 KB as WebP, loaded lazily like the 404 ones

**Constraints**: The page must render with the database unreachable (FR-007) and must not print the
exception (FR-008). See [research.md](research.md) R2 and R3.

**Scale/Scope**: One view, one icon glyph, two images, one test case extended, new strings in every
locale

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Not applicable: no business logic, no action |
| II. Simplicity over abstraction | Pass: reuses `x-error-page`; no new component, class or config |
| III. Tested before merged | Pass: the 500 status joins the existing data provider; two cases of its own (no leak, no database) |
| IV. Laravel conventions | Pass: Laravel's own `errors/{status}` view lookup, nothing custom |
| V. Localized and safe UX | Pass: every string through `__()`, translated with `monica:localize` in every locale |
| VI. Nothing changes under anybody's feet | Pass: replaces the framework screen; nothing a person relied on moves |
| VII. Stable core | Not applicable |
| VIII. Nothing disappears silently | Not applicable |
| IX. Accessible to everybody | Pass by design: decorative images with empty `alt`, one `h1` from the shell, real links with visible focus, icon hidden from assistive technology; checked by the `accessibility-reviewer` agent |
| X. Everything goes through the API | Not applicable: no capability; JSON requests keep their JSON error (FR-011) |
| XI. The written record is short | Pass |
| Self hosted and hosted are the same product | Pass: the copy promises no fix, which a self hosted instance could not keep (FR-003) |

No violation. Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/155-error-500-page/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
├── contracts/
│   └── error-page.md
└── checklists/
    └── requirements.md
```

No `data-model.md`: the feature stores and reads nothing.

### Source Code (repository root)

```text
resources/views/errors/500.blade.php                 new
resources/views/components/icon.blade.php            one glyph added: reload
public/images/server-error.webp                      new, daylight crop of the light mockup
public/images/server-error-dark.webp                 new, night crop of the dark mockup
tests/Feature/Views/Errors/ErrorPageTest.php         500 added to the provider, two cases added
lang/*.json                                          new strings in every locale
```

**Structure Decision**: Laravel's error view convention. The page lives beside the four other error
pages and shares their shell.

## Complexity Tracking

Nothing to justify.
