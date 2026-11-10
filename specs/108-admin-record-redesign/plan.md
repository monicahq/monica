# Implementation Plan: Admin record screen redesign

**Branch**: `2026-11-09-admin-redesign` | **Date**: 2026-11-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/108-admin-record-redesign/spec.md`

## Summary

Redraw `admin/logs` to the design inside the shell of specification 106: a "Write once" badge,
entries grouped by day in the viewer's zone, each entry with an icon, a sentence whose flag is in a
fixed width face and whose account holder is linked, its author, and both the relative and the exact
time. Filters, paging and the empty state stay. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js

**Storage**: N/A, nothing new is stored; the four flag wordings change in the enum only

**Testing**: PHPUnit, unit test for the view model (which exercises the enum's new methods), feature test for the screen

**Target Platform**: the responsive web application, desktop to 320 pixels

**Project Type**: server rendered web application

**Performance Goals**: the same queries as today (`entries()` already loads each entry's account)

**Constraints**: WCAG 2.2 AA in both themes; `monica:localize --check` passes; SQL stays driver neutral

**Scale/Scope**: 1 screen, 1 view model, 1 enum, 1 controller line

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Nothing written; the view model reads and words |
| II. Simplicity | Pass. No new component; one enum method says which name is a person |
| III. Tested before merged | Pass. See Tests |
| V. Localized | Pass. Four wordings reworded and one counted phrase added, through `trans_choice()` |
| VIII. Nothing disappears silently | Pass. The record stays append only; only its wording changes |
| IX. Accessible | Pass. Days are labelled sections, entries an ordered list, times machine readable |
| X. API | Not touched. A redesign adds no capability |
| XI. Short record | Pass. `data-model.md` and `contracts/` omitted: no data, no interface |

Post design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/108-admin-record-redesign/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `app/Enums/AdministratorActionEnum.php` | Four flag wordings (R4); `icon()` and `namesTheAccountHolder()` (R2, R3) |
| `app/ViewModels/Admin/AdministratorLogsViewModel.php` | Takes the viewer; `days()`, `sentence()`, `icon()`, `authorIcon()`, `moment()`, `time()`, `ago()` (R1, R3, R5) |
| `app/Http/Controllers/App/Admin/AdministratorLogController.php` | Passes `$request->user()` |
| `resources/views/app/admin/logs/index.blade.php` | Rewritten to the design in a `max-w-240` column (R6) |
| `lang/*.json` | Written by `php artisan monica:localize`, every locale translated |

### Tests

| File | Proves |
| --- | --- |
| `tests/Unit/ViewModels/Admin/AdministratorLogsViewModelTest.php` | Days cut in the viewer's zone with their headings and counts; the sentence parts for a flag on an account, a deleted account, a marketplace listing and an unknown action; the time in a 12 hour clock |
| `tests/Feature/Controllers/App/Admin/AdministratorLogControllerTest.php` | The screen renders its sections, the account link and the "Write once" badge; filters and paging keep passing |

**Structure Decision**: existing folders only. No new route, action, component or migration.

## Complexity Tracking

None.
