# Implementation Plan: Group page

**Branch**: `2026-11-09-group-page` | **Date**: 2026-11-09 | **Spec**: [spec.md](spec.md)

## Summary

The group page becomes three linked tabs. Activities are life events carrying a `group_id`;
notes and files get a table each; reminders accept a group as their target. Web only, behind the
existing `groups` flag. Decisions in [research.md](research.md), tables in
[data-model.md](data-model.md), routes in [contracts/routes.md](contracts/routes.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine Ajax |
| Storage | Two new tables, one new column; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: action, view model and controller tests |
| Project type | Web application with a JSON API and an MCP server; the CardDAV card is unchanged |
| Scale | 5 new actions, about 12 changed, 3 controllers, 3 view models, 4 views, 2 models |
| Constraints | WCAG 2.2 AA; every string in every locale; no change to the group card |

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: controllers delegate to the actions in the structure below |
| II. Simplicity | Pass: reuses `CreateLifeEvent`, the reminder engine and `files.show` (R1, R6, R8) |
| III. Tested | Pass: test scope below |
| IV. Laravel conventions | Pass: inline validation, one named route per line, `foreignIdFor`, `up()` only |
| V. Localized | Pass: counted phrases through `trans_choice()`; `monica:localize` |
| VI. Nothing changes underfoot | Pass: no structure version is touched; a life event keeps its version |
| VII. Stable core | Pass with a reading: a group note has a fixed shape, so it is not user defined data (R4) |
| VIII. Nothing disappears silently | Pass: group deletion deactivates its reminders and restoring it reactivates them (R6); a removed file has its grace period (R8) |
| IX. Accessible | Pass: radio group, fieldset of checkboxes, named reminder ticks, link tabs (FR-024) |
| X. Through the API | Pass: every capability has an endpoint, an MCP tool (files: listing and removal only) and a reference page (R7) |
| XI. Short record | **Exceeded**: `spec.md` is 223 lines against 120; see Complexity Tracking |

Re-checked after design: unchanged.

## Project Structure

```text
database/migrations/2026_11_09_000000_add_group_id_to_life_events_table.php
database/migrations/2026_11_09_000001_create_group_notes_table.php
database/migrations/2026_11_09_000002_create_group_files_table.php
app/Models/{GroupNote,GroupFile}.php + factories; Group gains notes(), files(), lifeEvents(), reminders()
app/Models/{LifeEvent,Reminder}.php               group(); remindable docblock gains Group
app/Enums/LifeEventSourceEnum.php                 Group case
app/Enums/UserActionEnum.php                      five cases (R12)
app/Actions/CreateLifeEvent.php                   ?Group $group (R1)
app/Actions/{Create,Update,Destroy}GroupNote.php
app/Actions/{AddGroupFile,RemoveGroupFile}.php
app/Actions/CreateContactReminder.php → CreateManualReminder.php, callers renamed (R6)
app/Actions/{DeactivateReminders,ReactivateReminders,DestroyGroup,RestoreGroup}.php
app/Actions/{WriteAccountExport,WritesMonicaItems}.php                    R11
app/Http/Controllers/App/Vaults/GroupController.php                        show = Overview
app/Http/Controllers/App/Vaults/{GroupLifeEvent,GroupNote,GroupReminder,GroupFile}Controller.php
app/Http/Controllers/App/Vaults/GroupMemberController.php                  index = Members tab
app/Http/Controllers/Api/ReminderOccurrenceAnswerController.php, app/Mcp/Tools/AnswerReminderOccurrence.php  R7
app/ViewModels/Vaults/GroupViewModel.php          header and tabs, shared by the three screens
app/ViewModels/Vaults/{GroupHistoryBlock,GroupRemindersBlock,GroupFilesBlock}.php
app/ViewModels/Vaults/{ContactTimelineBlock,ReminderRow,ReminderViewModel,VaultDeadlines}.php
resources/views/app/vaults/groups/{show,members,files,_header,_composer,_history}.blade.php
lang/*.json
```

Tests mirror each changed class: `tests/Unit/Actions`, `tests/Unit/ViewModels/Vaults`,
`tests/Feature/Controllers/App/Vaults`. One test per refusal at the action; controllers prove the
route, inline validation, the redirect and the wrong vault.

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Order

1. Migrations, models, enums (blocks everything).
2. US3 page shell and tabs, members tab moved (the page every other story lives on).
3. US1 activities, then US2 notes, then the merged history (R5).
4. US4 reminders, including the readers that assumed a contact.
5. US5 files.
6. Export and import (R11), translations, accessibility review.
7. API, MCP tools and reference pages for notes, activities, reminders and files ([contracts/api.md](contracts/api.md)).

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
| --- | --- | --- |
| XI. `spec.md` over its cap | Five stories with their acceptance scenarios | Trimming to 120 lines before tasks is possible; the pull request must say so if not |
| VII. Group note as a table | The design's shared history needs dated records; groups have no template | A repeater field needs a group template, which the user ruled out |
