# Implementation Plan: Admin account screen redesign

**Branch**: `2026-11-09-admin-redesign` | **Date**: 2026-11-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/107-admin-account-redesign/spec.md`

## Summary

Redraw `admin/accounts/{account}` to the design inside the shell of specification 106: trail and
header with badges and the record button, two fact panels, a "Limits and access" list, the flags
list and a deactivation panel. Every action, route and confirmation stays. Decisions are in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js

**Storage**: N/A, nothing new is stored

**Testing**: PHPUnit, unit test for the view model, feature tests for the screen and `x-detail-list`

**Target Platform**: the responsive web application, desktop to 320 pixels

**Project Type**: server rendered web application

**Performance Goals**: the same queries as today

**Constraints**: WCAG 2.2 AA in both themes; `monica:localize --check` passes

**Scale/Scope**: 1 screen, 1 view model, 2 component props, 2 component fixes

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No action changes; the view model only reads |
| II. Simplicity | Pass. A prop on `x-detail-list` and on `x-row`, no new component |
| III. Tested before merged | Pass. See Tests |
| V. Localized | Pass. New wordings through `__()`; no counted phrase added |
| IX. Accessible | Pass. Refused deactivation stays on screen with its reason; storage bar has a text alternative |
| X. API | Not touched. A redesign adds no capability |
| XI. Short record | Pass. `data-model.md` and `contracts/` omitted: no data, no interface |

Post design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/107-admin-account-redesign/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `app/ViewModels/Admin/AccountViewModel.php` | `badges()` per R3 with `icon`; new `userEmailLine()`, `storageShareLine()`, `storageSharePercent()`, `accessForGoodLine()` |
| `resources/views/app/admin/accounts/show.blade.php` | Rewritten to the design (R1 to R8) |
| `resources/views/components/detail-list/index.blade.php`, `row.blade.php` | `layout` prop, `stacked` puts the label above the value (R5); caption labels in `text-muted` |
| `resources/views/components/row.blade.php` | `stack` prop: the far end goes under the text below `xs` (R6) |
| `resources/views/components/panel.blade.php`, `button/index.blade.php` | Danger subtitle in `text-body`; a refused button keeps its focus ring at full opacity |
| `lang/*.json` | Written by `php artisan monica:localize`, every locale translated |

### Tests

| File | Proves |
| --- | --- |
| `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` | Badge order and tones for the viewer, an active, a deactivated and a deleted person, billable and self hosted; the address line; the storage share at 40%, over 100% and with no limit; the lifetime line |
| `tests/Feature/Controllers/App/Admin/AccountControllerTest.php` | The screen renders with its trail and record link; on the viewer's own account the deactivation button carries `aria-disabled="true"` and `aria-describedby` |

**Structure Decision**: existing folders only. No new route, action, component or migration.

## Complexity Tracking

None.
