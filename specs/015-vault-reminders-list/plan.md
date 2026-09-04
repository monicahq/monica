# Implementation Plan: Vault reminders list

**Branch**: `2026-09-04-vault-reminders-list` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

## Summary

Two read screens over the finished reminder engine, plus three row actions and one new capability. The Active tab lists actionable `ReminderOccurrence` rows for a vault inside the 90 day generation window, bucketed in PHP and groupable by contact. The Sent tab paginates occurrences with `notified_at` set. Each Active row carries postpone (a dialog over the existing action), dismiss (the existing action), send now (one new action) and a link to the detail screen that a later pull request builds. Decisions are in [research.md](research.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.4, current major Laravel |
| Dependencies | Blade, Tailwind, Alpine.js, Alpine Ajax, Turbo. None added. |
| Storage | Existing `reminders`, `reminder_occurrences`, `notifications`. One new index; no new table or column. |
| Testing | PHPUnit. Feature tests for controllers and views; unit tests for the action, enum, view models and new scopes. |
| Platform | Server-rendered web, responsive from phone to desktop |
| Performance | Constant query count per screen regardless of row count; both tabs within the load time of the other vault list screens |
| Constraints | Suite runs on SQLite, MySQL and PostgreSQL, so no driver-specific date SQL. PHPStan level 5 over `app/`, `routes/`, `database/`. Pint clean. |
| Scale | One vault's 90 day window on the Active tab (tens of rows); unbounded history paginated on the Sent tab |

## Constitution Check

*GATE: checked before Phase 0 and re-checked after Phase 1. Both passes identical.*

| Principle | Verdict |
| --- | --- |
| I Actions hold the business logic | Pass. One new action, `SendTestReminderNotification`. Controllers validate and delegate. View models only read and are consumed only by Blade. |
| II Simplicity over abstraction | Pass. One enum, two view models, one row object. No repository, no service layer. |
| III Tested before merged | Pass by plan. Every controller, view model, action and enum listed below has a test, including the two compartment cases. |
| IV Laravel conventions | Pass. One class per file, `up()`-only migration, no `constrained()`, kebab URLs, camelCase route names, array validation rules. One named deviation: a query string for grouping, justified in research.md. |
| V Localized and safe | Pass. Every phrase through `__()`, counted phrases through `trans_choice()`, `monica:localize` run for nine locales. No destructive action on this screen; delete lives on the detail screen. |
| VI Nothing changes under anybody's feet | Pass. Postpone and dismiss touch one occurrence. Every instant the dialog produces is computed in the rule's frozen zone, never the reader's. |
| VII A stable core | Pass. Reminders are a fixed concept and this adds no second way to attach data. |
| VIII Nothing disappears silently | Pass. Sent rows survive rule deletion; a refused action says why; a test send is recorded. |
| IX Accessible to everybody | Pass by plan, and the largest single risk. See the checklist below. |
| X Everything goes through the API | **Deferred.** See Complexity Tracking. |
| XI The written record is short | Pass. spec.md 120 of 120, research.md 59 of 80, plan.md 95 of 100, quickstart.md within 60. `data-model.md` and `contracts/` omitted, see below. |

**Accessibility checklist for Principle IX**, since the design is icon-dense: the four icon-only controls need accessible names, their tooltips must appear on focus and not only on hover, the Overdue bucket must not rely on tint alone (it also carries an icon and elapsed wording), the postpone dialog needs a focus trap, an Escape key and focus returned to the button that opened it, the hover-only exact instant needs a focusable trigger, the date stamp must survive 200% zoom, and the tab bar must be arrow-key operable.

**Artifacts deliberately omitted.** `data-model.md`: no entity is added or changed, and the six this feature reads are described in [013's data-model](../013-reminder-delivery-engine/data-model.md). `contracts/`: the only interface added is the route table below, and duplicating it in a second file is what Principle XI forbids.

## Project Structure

### Routes

| Verb | URL | Controller | Name |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/reminders` | `VaultReminderController@index` | `reminders.index` |
| GET | `vaults/{vault}/reminders/sent` | `VaultReminderController@sent` | `reminders.sent` |
| PUT | `vaults/{vault}/reminders/occurrences/{reminderOccurrence}/postponement` | `ReminderOccurrencePostponementController@update` | `reminders.occurrences.postponement.update` |
| PUT | `vaults/{vault}/reminders/occurrences/{reminderOccurrence}/dismissal` | `ReminderOccurrenceDismissalController@update` | `reminders.occurrences.dismissal.update` |
| POST | `vaults/{vault}/reminders/occurrences/{reminderOccurrence}/test-notification` | `ReminderOccurrenceTestNotificationController@create` | `reminders.occurrences.testNotification.create` |

`sent` is registered before any `reminders/{reminder}` route the detail pull request adds.

### Source

```text
app/
├── Actions/SendTestReminderNotification.php                    new
├── Enums/ReminderPostponePresetEnum.php                        new
├── Http/Controllers/App/Vaults/
│   ├── VaultReminderController.php                             new
│   ├── ReminderOccurrencePostponementController.php            new
│   ├── ReminderOccurrenceDismissalController.php               new
│   └── ReminderOccurrenceTestNotificationController.php        new
├── Models/ReminderOccurrence.php                               two scopes added
└── ViewModels/Vaults/
    ├── VaultRemindersViewModel.php                             new, Active tab
    ├── VaultRemindersSentViewModel.php                         new, Sent tab
    └── ReminderRow.php                                         new, one row

database/migrations/2026_09_04_000000_add_vault_notified_index_to_reminder_occurrences_table.php   new

resources/views/
├── app/vaults/reminders/{index,sent,row,postpone}.blade.php    new
└── components/vault/sidebar.blade.php                          url, badge, current key

routes/web.php                                                  five routes
```

**Structure Decision**: the existing signed-in layout. Controllers join the `App/Vaults` namespace beside `ContactShareLinkController`, view models join `ViewModels/Vaults` beside `ContactReminderBlock`, and the rich screen keeps one view model per tab delegating rows to `ReminderRow`, the pattern `MarketplaceStatistics` already sets. No new folder.

### Tests

`tests/Feature/Controllers/App/Vaults/` gets one file per controller, each covering the happy path, the refusals, a vault in another account and a reminder reached through the wrong vault of the same account. `tests/Unit/` gets `Actions/SendTestReminderNotificationTest`, `Enums/ReminderPostponePresetEnumTest`, `ViewModels/Vaults/VaultRemindersViewModelTest`, `ViewModels/Vaults/VaultRemindersSentViewModelTest`, and additions to `Models/ReminderOccurrenceTest` for the two scopes. Data uses The Office (US) per `.claude/rules/testing.md`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X: no API coverage for reminders | `routes/api.php` has no reminder routes at all. 012 and 013 shipped the engine without any, so the debt predates this feature; adding the full reminder API here would roughly double a UI feature and would be designed against a surface still moving. | Covering only the one new capability (send now) leaves the read and the two existing operations uncovered, which is the same violation at a smaller size. The pull request must name this deviation, and the API surface is the recommended next feature after the detail screen. |
| Query string for the grouping toggle | `code-style.md` discourages query strings without a good reason. A grouping is a view option, not part of a resource's identity. | A route segment per grouping puts presentation in the URL structure; Alpine-only state cannot be linked to or restored by the back button. |
