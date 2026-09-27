# Implementation Plan: Reminders widget on the contact page

**Branch**: `2026-09-26-reminder-answers` | **Date**: 2026-09-27 | **Spec**: [spec.md](spec.md)

## Summary

A Reminders panel on the contact's overview, under Immediate family, listing the deadlines the
contact's Reminders screen lists under All, filterable in place, answerable in place, with a blank
state. It reads through the Reminders screen's view model ([research R1](research.md)), filters in
the browser (R3), and reuses the answer forms and the row menu (R4, R5).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: none new; reads `reminders` and `reminder_occurrences`

**Testing**: PHPUnit, unit tests for the block, feature tests for the view and the controller

**Target Platform**: the web application

**Project Type**: web application, server rendered

**Performance Goals**: the overview adds the Reminders screen's queries, once per render

**Constraints**: WCAG 2.2 AA, every configured locale, no Add button

**Scale/Scope**: one panel, one block, one controller line

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No new write; answers and deletion go through existing actions. |
| II. Simplicity | Pass. One block, one partial moved, no new route. |
| III. Tested | Pass. Block, view and controller change each get a test. |
| IV. Laravel conventions | Pass. No query string, no new controller. |
| V. Localized, destructive warns | Pass. New strings through `monica:localize`; Delete keeps its warning. |
| IX. Accessible | Pass. Tabs report selection, menu reports its state, answers name their row. |
| X. API | Pass. Nothing new to expose; the API already lists and answers a contact's deadlines. |
| XI. Short record | Pass. `data-model.md`, `contracts/` and `quickstart.md` omitted: no entity, no endpoint. |

## Project Structure

### Documentation (this feature)

```text
specs/064-contact-page-reminders-widget/
├── spec.md
├── research.md
├── plan.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `app/ViewModels/Vaults/ContactDeadlinesBlock.php` | New. Rows with their category, the four filters and counts, empty and blank wordings, the Reminders screen's URL, `holdsNothing()` |
| `app/ViewModels/Vaults/ContactRemindersViewModel.php` | `emptyTitle()` and `emptyLine()` take an optional filter (R2) |
| `app/ViewModels/Vaults/ContactViewModel.php` | `deadlines(): ContactDeadlinesBlock`, memoised with `once()` like `immediateFamily()` |
| `app/Http/Controllers/App/Vaults/ContactReminderController.php` | `destroy()` returns `back()` (R6) |
| `resources/views/app/vaults/contacts/_reminders.blade.php` | New. The panel |
| `resources/views/app/vaults/contacts/reminders/_menu.blade.php` | New. The menu moved out of `_row` (R5) |
| `resources/views/app/vaults/contacts/reminders/_row.blade.php` | Includes `_menu` |
| `resources/views/app/vaults/contacts/show.blade.php` | Includes `_reminders` after `_immediate-family` |
| `lang/*.json` | Through `php artisan monica:localize` |
| `tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php` | New |
| `tests/Unit/ViewModels/Vaults/ContactRemindersViewModelTest.php` | The filter argument |
| `tests/Feature/Views/Vaults/ContactRemindersPanelTest.php` | New. Copy, blank state, the Alpine hook, the tab contract, no Add |
| `tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php` | Deletion goes back |

**Structure Decision**: the block lives with the other overview blocks under `app/ViewModels/Vaults`,
the partial with the other overview partials under `resources/views/app/vaults/contacts`.

## Test split

| Layer | Proves |
| --- | --- |
| `ContactDeadlinesBlock` | Order, categories, counts without History, per filter wordings, blank, cancelled and deleted left out |
| View | The panel renders with its copy; blank state has no tabs and no foot link; no Add button; `aria-selected` and `tabpanel` present |
| Controller | Deleting from a referring page returns there |

Answer rules, categories and refusals stay proven where 063 proved them.

## Complexity Tracking

None.
