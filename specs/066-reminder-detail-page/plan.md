# Implementation Plan: Reminder detail page redesign

**Branch**: `2026-09-27-reminder-detail-page` | **Date**: 2026-09-27 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/066-reminder-detail-page/spec.md`

## Summary

Rewrite `reminders/show.blade.php` into four blocks (header, dates awaiting a response, history,
delivery details) and reshape `ReminderViewModel` to feed them. No schema, route or API change.
Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js with Alpine Ajax

**Storage**: none new; reads `reminders`, `reminder_occurrences`, `logs`, `notifications`

**Testing**: PHPUnit (view model unit tests, controller feature tests)

**Target Platform**: web

**Project Type**: web application (single Laravel project)

**Performance Goals**: no query per row; history pages four rows at a time

**Constraints**: WCAG 2.2 AA; every string in every configured locale

**Scale/Scope**: one screen, one view model, one controller method

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No new rule; answering and deleting reuse existing actions |
| II. Simplicity | Pass. No new class; one partial rewritten |
| III. Tested before merged | Pass. View model and controller tests updated |
| V. Localized | Pass. New phrases through `monica:localize`; counted phrases via `trans_choice()` |
| VIII. Nothing disappears silently | Pass. A stopped reminder names its reason and remedy |
| IX. Accessible | Pass. Menu reuses `x-actions-menu`; disclosure carries `aria-expanded`/`aria-controls` |
| X. Through the API | Pass. No new capability |
| XI. Short record | Pass. No data model or contract file: nothing changes shape |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/066-reminder-detail-page/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── tasks.md   (next step)
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `app/ViewModels/Vaults/ReminderViewModel.php` | Add `contact()` (name, url, avatar), `scheduleLine()`, `sourceLine()`, `editDateUrl()`, `problem()`, `awaiting()`, `history()` paginated, `historyCount()`, `deliveryLog()`, `contactRemindersUrl()`. `editUrl()` nullable. Remove `state()`, `next()`, `schedule()`, `createdLine()`, `builtFrom()`, `answers()`, `occurrences()`, `stillToComeEmptyLine()`, `delivery()` |
| `app/Http/Controllers/App/Vaults/ReminderController.php` | `destroy` redirects to the contact's reminders when there is a contact |
| `resources/views/app/vaults/reminders/show.blade.php` | Rewritten |
| `resources/views/app/vaults/reminders/_history.blade.php` | Rewritten as the delivery log (only caller is `show`) |
| `resources/views/app/vaults/reminders/_contact-card.blade.php` | Unreferenced afterwards; deletion needs an explicit yes |
| `lang/*.json` | Through `php artisan monica:localize` |
| `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php` | Rewritten for the new methods |
| `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` | Show renders both kinds; destroy lands on the contact's reminders |

**Structure Decision**: existing layout. The page keeps one view model; `ReminderRow` and
`ReminderHistory` stay as the blocks it delegates to.

## Complexity Tracking

None.
