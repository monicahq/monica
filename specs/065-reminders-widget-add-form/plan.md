# Implementation Plan: Add a reminder from the Reminders widget

**Branch**: `2026-09-26-reminder-answers` | **Date**: 2026-09-27 | **Spec**: [spec.md](spec.md)

## Summary

An Add button in the widget's header swaps the widget for a form that posts to the route the
Reminders screen's form already uses. The controller sends the reader back to the overview when the
form came from there ([research R1](research.md)), Alpine Ajax redraws the widget alone (R2), and a
refusal reopens the form from the old input (R3). "Every [n] weeks" is assembled in the browser from
counted phrases, so it reads correctly in every locale (R5).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: none new; `CreateContactReminder` writes `reminders` as today

**Testing**: PHPUnit; unit for the block, feature for the controller and the view

**Target Platform**: the web application

**Project Type**: web application, server rendered

**Performance Goals**: no new query on the overview beyond the form's defaults

**Constraints**: WCAG 2.2 AA, every configured locale, 063's refusals unchanged

**Scale/Scope**: one controller change, one block extension, one partial, one Alpine component

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. `CreateContactReminder` unchanged and reused. |
| II. Simplicity | Pass. No new route or controller; one small JavaScript component for R5. |
| III. Tested | Pass. Redirect, messages, reopening and the view contract each get a test. |
| IV. Laravel conventions | Pass. Inline validation with messages; no query string. |
| V. Localized | Pass. Counted phrases through `trans_choice` wordings; strings via `monica:localize`. |
| IX. Accessible | Pass. Labels, required, `aria-expanded` and `aria-controls`, focus moved on open and cancel. |
| X. API | Pass. The API already creates a contact's reminder; nothing new to expose. |
| XI. Short record | Pass. No `data-model.md`, `contracts/`, `quickstart.md`: no entity, no endpoint. |

## Project Structure

### Documentation (this feature)

```text
specs/065-reminders-widget-add-form/
├── spec.md
├── research.md
├── plan.md
└── checklists/requirements.md
```

### Source Code (repository root)

| File | Change |
| --- | --- |
| `app/Http/Controllers/App/Vaults/ContactReminderController.php` | `create()` returns to the overview when it came from there (R1); `create()` and `update()` pass the two messages (R4) |
| `app/ViewModels/Vaults/ContactDeadlinesBlock.php` | `canAdd()` (not archived), `isAdding()` (R3), `form(): ContactReminderForm`, `repeats()` (Once, Weekly, Monthly, Yearly), `intervalWordings()` keyed by cadence (R5), `aboutLine()` ("About :name") |
| `resources/js/reminder-interval.js` | New. Picks the wording for a number and returns the text before and after `:n` |
| `resources/js/app.js` | Registers `reminderInterval` |
| `resources/views/app/vaults/contacts/_reminders.blade.php` | `id="contact-reminder-panel"`, `adding` in the Alpine state, Add in the header, the form panel |
| `lang/*.json` | Through `php artisan monica:localize` |
| `tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php` | `canAdd`, `isAdding`, `repeats`, `intervalWordings`, `aboutLine` |
| `tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php` | Redirect from the overview, from elsewhere, the two messages |
| `tests/Feature/Views/Vaults/ContactRemindersPanelTest.php` | Add present, absent when archived, the form's action, `x-target`, field names, `aria-controls`, reopened with errors |

**Structure Decision**: everything stays where 064 put the widget; the JavaScript component sits with
the other Alpine components in `resources/js`.

## Test split

| Layer | Proves |
| --- | --- |
| Block | Defaults, labels, wordings per cadence, when Add is offered, when the form reopens |
| Controller | Where each submission lands and the messages it carries |
| View | The form's contract and that a refused submission shows the form with its errors |

What a reminder may be, and its refusals, stay proven by 063's action tests.

## Complexity Tracking

None.
