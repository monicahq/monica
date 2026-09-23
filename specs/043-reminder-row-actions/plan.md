# Implementation Plan: Reminder row actions

**Branch**: `2026-09-23-reminders` | **Date**: 2026-09-23 | **Spec**: [spec.md](spec.md)

## Summary

Redraw the reminder row as design 2a (envelope, stamp, one link, Dismiss and More) and put an
`x-modal.form` confirmation in front of Dismiss and Send me a test, on the list and on a reminder's
own screen. No action, route, controller or data changes. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest) | **Frontend**: Blade, Tailwind CSS 4, Alpine.js with
`@alpinejs/focus`, Alpine Ajax | **Storage**: none changed | **Testing**: PHPUnit feature and unit tests
| **Project Type**: server rendered web application | **Scope**: two screens, one shared component

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass. The three actions are reused as they are; the one new method is wording in a view model |
| II. Simplicity | Pass. No new component; one partial, as `_postpone` already is |
| III. Tested | Pass. `ReminderRowTest` covers `dismissalNote()`; controller tests assert the new copy and dialog actions |
| IV. Conventions | Pass |
| V. Localized, warned | Pass. New strings go through `monica:localize`; the request adds the warning V asks for |
| IX. Accessible | Pass by design: labelled buttons, `aria-expanded`, dialogs named by title, focus returned (R4), no nested interactive content (R7) |
| X. API | Pass. No capability added; dismiss, postpone and test already exist behind the same actions |
| XI. Short record | Pass. `data-model.md` and `contracts/` omitted: no entity or interface changes |

Re-checked after design: no change.

## Project Structure

```text
app/ViewModels/Vaults/ReminderRow.php                 + dismissalNote()
resources/views/components/actions-menu/index.blade.php  + variant prop, x-ref="menuTrigger"
resources/views/app/vaults/reminders/row.blade.php    redrawn as 2a
resources/views/app/vaults/reminders/_dismiss.blade.php  new: the dismiss confirmation
resources/views/app/vaults/reminders/_postpone.blade.php + inMenu trigger
resources/views/app/vaults/reminders/show.blade.php   next deadline card uses _dismiss
lang/*.json                                           via monica:localize
tests/Unit/ViewModels/Vaults/ReminderRowTest.php
tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php
tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php
```

## Row layout (2a mapped to the existing tokens)

| Part | Built from |
| --- | --- |
| Flap | the existing `clip-path` triangle, `bg-card`, height 22px as drawn |
| Stamp | `border-dashed border-hairline-strong bg-canvas`, month then day; cadence and `cadenceIcon()` under it |
| Name line | `x-contact-avatar` 24px, name, `chevron-right` in `text-muted` when the row is a link |
| Text line | `icon()`, `text()`, `·`, `relative()` inside `x-tooltip` with `exact()`; `text-warning` when `isLate()` |
| Actions | `_dismiss` (secondary, small, `check` icon) and `x-actions-menu` (ghost, "More", `chevron-down`) |

Dropped from today's row: the side stripe, the year, the dashed rule between name and text, the four
icon buttons.

## Menu contents

1. Remind me later: `_postpone` with `inMenu`, `clock` icon.
2. Send me a test: `x-modal.form`, `method="post"`, `action` `testUrl()`, `mail` icon.
3. Separator.
4. Go to the reminder: `x-actions-menu.item`, `arrow-right` icon; only when `linkToReminder` and
   `reminderUrl()` is not null.

## Risks

- `x-actions-menu` is used on three other screens. The default stays `secondary`, so they render
  unchanged; the whole suite runs, as `components.md` asks.
- The grouping switch replaces `#reminders-list` through Alpine Ajax. Teleported modals of the
  replaced rows must not linger: check it in [quickstart.md](quickstart.md).
- The dismissal flash says "the next one is still coming" for one time reminders too. It is out of
  scope (FR-010 keeps today's messages) and reported separately.
