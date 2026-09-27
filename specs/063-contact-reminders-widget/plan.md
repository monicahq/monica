# Implementation Plan: Reminder answers and the contact's reminders

**Branch**: `2026-09-26-reminder-answers` | **Date**: 2026-09-26 | **Spec**: [spec.md](spec.md)

## Summary

Replace moments, postponing, dismissing and pausing with one answer per deadline, stored on the
occurrence, and give a contact a Reminders screen from `Contact Reminders.dc.html`. The schema is
edited in place ([data-model.md](data-model.md)), categories are computed from the day, a policy
decides which answer is offered, and the same actions back the screens, the API and MCP
([contracts](contracts/)). Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest) | **Frontend**: Blade, Tailwind CSS 4, Alpine.js, Alpine
Ajax | **Storage**: MySQL, PostgreSQL, SQLite (CI runs all three) | **Testing**: PHPUnit | **Project
Type**: server rendered web application with a JSON API and an MCP server | **Scale**: 4 tables, about
70 existing test files touched, 4 screens, 7 endpoints, 7 tools

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions | Pass. `AnswerReminderOccurrence`, `CreateReminder` gains a manual path; controllers, jobs, API and MCP call them |
| II. Simplicity | Pass. Three enums and five actions deleted; no new component (R10) |
| III. Tested | Pass. One action test per rule, policy tested three ways, one view model test per screen |
| IV. Conventions | Pass. Migrations edited in place (R1); one route per line |
| V. Localized, warned | Pass. Deleting warns (FR-021); answers need no warning, they destroy nothing |
| VI. Nothing changes underfoot | Pass. An answer is final and editing a rule never rewrites one (FR-016) |
| VIII. Nothing disappears silently | Pass. Cancelled deadlines keep their reason; answers survive deletion of the rule |
| IX. Accessible | Pass by design: labelled answer buttons, chips report the selected filter, chart has a text label |
| X. API | Pass. Endpoints and tools ship in the same pull request ([api.md](contracts/api.md)) |
| XI. Short record | **Exceeded**: `spec.md` is about 280 lines against 120 (Complexity Tracking) |
| Product invariant, reminder parts | **Conflict**: it names postponing and skipping as actions (Complexity Tracking) |

Re-checked after design: no new conflict.

## Project Structure

```text
database/migrations/2026_09_02_00000{0,1,3}_*.php      edited (data-model.md)
app/Enums/                 + ReminderAnswerEnum, ReminderOccurrenceCategoryEnum; - 4 enums (data-model.md)
app/Models/Reminder.php, ReminderOccurrence.php        casts, scopes awaitingAnswer / answered, no status
app/Policies/ReminderOccurrencePolicy.php              new: remember, forget
app/Actions/{AnswerReminderOccurrence,CreateContactReminder}.php  new
app/Actions/{Create,Update,Destroy}Reminder.php        manual source, starts_on, no moments
app/Actions/{Generate,Cancel,Restore}ReminderOccurrences.php  one per cycle; includePast (R6)
app/Actions/{NotifyReminderOccurrence,DeliverNotification,SetFieldValues}.php  no status, no moments
app/Actions/{Postpone,Dismiss}ReminderOccurrence.php   deleted
app/Helpers/ReminderSchedule.php                       no moments, anchor on starts_on or value
app/Rules/ReminderMoments.php, exceptions for moments and postponing  deleted
app/Http/Controllers/App/Vaults/ContactReminderController.php         new
app/Http/Controllers/App/Vaults/ReminderOccurrenceAnswerController.php new
app/Http/Controllers/App/Vaults/ReminderOccurrence{Postponement,Dismissal}Controller.php, ReminderPauseController.php  deleted
app/Http/Controllers/Api/{ContactReminder,ContactReminderOccurrence,ReminderOccurrenceAnswer}Controller.php  new
app/Http/Resources/{Reminder,ReminderOccurrence}Resource.php          new
app/Mcp/Tools/*.php (7), MirrorsAnApiCapability.php, Servers/EricaServer.php
app/ViewModels/Vaults/ContactRemindersViewModel.php + ReminderAnswerSummary.php + ContactReminderForm.php  new
app/Helpers/{NextReminderDeadlines,ContactReminderDates}.php   new, shared by screens, API and MCP
app/ViewModels/Vaults/{VaultReminders,ReminderViewModel,ReminderRow,ReminderHistory,ContactReminderBlock,ContactViewModel}.php  changed
app/View/Components/Vault/Sidebar.php                  count awaiting an answer
app/Http/Dav/CalendarBackend.php                       active deadlines (R12)
resources/views/app/vaults/contacts/reminders/{index,_row,_summary,form}.blade.php  new
resources/views/app/vaults/reminders/{index,row,show,_tabs,history}.blade.php  answers; _dismiss, _postpone deleted
resources/views/components/field-reminder.blade.php, resources/js/field-reminder.js  no moments
resources/docs/api/20-reminders.php, app/ViewModels/Docs/McpDocsViewModel.php
database/factories/Reminder*Factory.php, database/seeders/ExampleAccountSeeder.php
spec/spec.md 10.1 to 10.3 and 14.2; spec/models/040 to 043 (kept `-done`: the tables match once this ships)
lang/*.json via monica:localize
```

Tests follow the classes: new tests for the policy, the answer action, both enums, the contact view
model and summary, the three API controllers and the seven tools; deleted with their subjects;
the rest rewritten where they build moments or statuses.

## Delivery order

1. Schema, enums, models, factories (the suite goes red here and stays red until step 3).
2. Schedule, generation, cancellation, delivery, answer action and policy.
3. Existing screens: date panel, vault page, reminder page, sidebar, CalDAV. Suite green again.
4. Contact Reminders screen and manual reminder form.
5. API, MCP, docs. 6. Seeder, living documents, translations.

## Risks

- One pull request touches about seventy test files. Steps 1 to 3 cannot be split, since the schema
  change breaks every reader at once; steps 4 and 5 could be separate pull requests if review needs it.
- A template page keyed `reminders` becomes unreachable (R10). No shipped template uses it
  (`DefaultPageEnum`); a page somebody named that way would be hidden, as `relationships` already is.
- MySQL, PostgreSQL and SQLite must agree on the conditional answer write (R5); covered by CI.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
| --- | --- | --- |
| Constitution product invariant names postponing and skipping | The user removed both | Implementing against it would ship a breach; an amendment through `/speckit-constitution` comes first |
| `spec.md` over 120 lines | Four surfaces, three clarifications, a new screen | Trimming it is a separate pass the user has not asked for; the PR must say so |
