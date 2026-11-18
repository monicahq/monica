# Implementation Plan: Admin space sidebar

**Branch**: `2026-11-17-admin-sidebar` | **Date**: 2026-11-17 | **Spec**: [spec.md](spec.md)

## Summary

The admin layout drops its row of section links and renders the shared `x-nav-drawer` on the left,
under the dark admin bar, which folds into a drawer opened from a menu button below `lg`. The drawer
gains three optional behaviours so it can serve a space whose bar already holds the way out.
Decisions and their reasons: [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS 4, Alpine.js

**Primary Dependencies**: none added

**Storage**: N/A. The counts already come from `AdminNavigationViewModel`

**Testing**: PHPUnit feature tests on the layout (`tests/Feature/Views/Layouts/AdminLayoutTest.php`)

**Target Platform**: browsers from 375px wide up

**Project Type**: web application, server rendered

**Performance Goals**: no added query; the two counts run once per admin screen, as today

**Constraints**: WCAG 2.2 AA; no class or markup assertions in tests; every new string translated

**Scale/Scope**: one layout, one shared component, eleven admin sections, every admin screen

## Constitution Check

| Principle | Status |
| --- | --- |
| II. Simplicity | Pass. Reuses the existing drawer; three optional props, no new component |
| III. Tested | Pass. Layout test extended (R2 contract, counts, menu button); full suite for the shared component |
| V. Localized | Pass. One new string through `monica:localize`; labels already translated |
| VI. Nothing changes under anybody's feet | Pass. No data, no URL, no API change |
| IX. Accessible | Pass if the review holds: landmark, `aria-current`, `aria-expanded`, focus return, reduced motion |
| X. API | N/A. Navigation only, no capability added |
| XI. Short record | Pass. `data-model.md` and `contracts/` omitted: no data, and the UI contract is the test list below |

Re-checked after design: unchanged.

## Project Structure

```text
specs/129-admin-sidebar/
├── spec.md, plan.md, research.md, quickstart.md
└── checklists/requirements.md

resources/views/
├── layouts/admin.blade.php            # grid layout, menu button, scrim, drawer call
└── components/nav-drawer.blade.php    # optional leave link, group title, section marking
tests/Feature/Views/Layouts/AdminLayoutTest.php
lang/*.json                            # via monica:localize
```

**Structure Decision**: no new file in `app/`. The navigation list stays in the admin layout, built
as `x-nav-drawer` groups, as `settings/sidebar` and `support/sidebar` build theirs.

## What the tests prove

- Every available section is linked inside the "Admin sections" landmark; Support and Changelog only while switched on (existing).
- Exactly one `aria-current="page"`; a screen inside a section marks it `aria-current="true"` (existing).
- No link into a vault (existing; guards R2's dropped product link).
- A waiting count is announced with its wording, and absent at zero.
- The menu button names itself, controls `main-nav`, reports `aria-expanded`, and carries the waiting sentence only when something waits.
- Settings and support still render their leave link and group titles (existing tests, full suite).

## Risks

- `x-nav-drawer` is shared: a regression shows on settings and support. Mitigation: full suite and the last quickstart row.
- Focus return is added to the admin drawer only; the shell keeps closing without it. Follow-up, not this change.
