# Implementation Plan: Admin space redesign

**Branch**: `2026-11-09-admin-redesign` | **Date**: 2026-11-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/106-admin-redesign/spec.md`

## Summary

Redraw the shell shared by the seven admin screens (band, dark bar, section list with icons, warning
strip) and the Overview screen (counted at, totals strip, two panels, email bar), following the
design. No data, route, action or permission changes. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js

**Storage**: N/A, nothing new is stored or counted differently

**Testing**: PHPUnit, feature tests for the layout and the controller, unit test for the view model

**Target Platform**: the responsive web application, desktop to 320 pixels

**Project Type**: server rendered web application

**Performance Goals**: the Overview runs the same queries as today, plus none

**Constraints**: WCAG 2.2 AA in both themes; `monica:localize --check` passes; no raw Tailwind palette

**Scale/Scope**: 1 layout, 1 screen, 1 view model, 5 components touched, 4 tokens, 2 focus recipes

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No write; the view model only reads and words what it reads |
| II. Simplicity | Pass. No new component: the section list and strip have one caller (R4, R5) |
| III. Tested before merged | Pass. Layout, controller and view model tests updated, see below |
| IV. Laravel conventions | Pass |
| V. Localized | Pass. Every new wording through `__()` or `trans_choice()` (R7), `monica:localize` run |
| VI. to VIII. | Not touched |
| IX. Accessible | Pass. Current section not colour only, bar alternative text, decorative band hidden, focus visible on the dark bar (R2) |
| X. API | Not touched. A redesign adds no capability |
| XI. Short record | Pass after trimming `spec.md` to its cap. `data-model.md` and `contracts/` omitted: no data, no interface |

Post design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/106-admin-redesign/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `resources/css/app.css` | Four fixed tokens and their `--color-*` utilities (R1), two focus recipes (R2) |
| `resources/views/layouts/admin.blade.php` | Band, bar, badge, section list with icons, warning strip (R1 to R5) |
| `resources/views/components/theme-toggle.blade.php`, `button/index.blade.php`, `account-menu.blade.php` | Bar colours and focus ring for the controls on the bar (R2) |
| `resources/views/components/badge.blade.php`, `icon.blade.php` | `signal` tone for the "Instance" badge; `warning` glyph (R5) |
| `app/ViewModels/Admin/OverviewViewModel.php` | Takes the `User`; adds `countedAt()`, `moderationSentence()`, `emailShares()`, `emailSummary()`, the two period labels (R6 to R8) |
| `app/Http/Controllers/App/Admin/OverviewController.php` | Passes `$request->user()` to the view model |
| `resources/views/app/admin/overview/index.blade.php` | Rewritten to the design (R6, R8, R9) |
| `lang/*.json` | Written by `php artisan monica:localize`, every locale translated |

### Tests

| File | Proves |
| --- | --- |
| `tests/Feature/Views/Layouts/AdminLayoutTest.php` | The full warning sentence, the "Instance" badge, the labelled section list, exactly one `aria-current="page"` |
| `tests/Feature/Controllers/App/Admin/OverviewControllerTest.php` | The screen renders and links to moderation; the obsolete period and notice assertions go |
| `tests/Unit/ViewModels/Admin/OverviewViewModelTest.php` | `countedAt()` in the user's zone and format, the two moderation sentences, shares summing to 100 and all zero on an instance that sent nothing, the bar alternative |

**Structure Decision**: existing folders only. No new route, action, component or migration.

## Complexity Tracking

None.
