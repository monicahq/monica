# Implementation Plan: Last in touch

**Branch**: `2026-11-20-last-in-touch` | **Date**: 2026-11-20 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/141-last-in-touch/spec.md`

## Summary

Start writing the `last_interaction_at` date every screen already reads, from life events whose type
updates **Last in touch**, and give each life event type that setting. The recount the life event
actions already share computes the date; a daily job catches future life events whose day arrives;
two migrations add the setting and fill the date for existing contacts. Decisions are in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)

**Primary Dependencies**: Blade, Alpine.js, Alpine Ajax, Laravel MCP

**Storage**: one new column on `life_event_types`; `contacts.last_interaction_at` exists

**Testing**: PHPUnit, `RefreshDatabase`, factories

**Target Platform**: web application, JSON API, MCP server

**Project Type**: web-service

**Performance Goals**: no extra query on the contact page; one grouped query per recount

**Constraints**: recount on write, not on read, because the list sorts on the date

**Scale/Scope**: one column, one setting, one daily job, two migrations, no new screen

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: the recount stays in `RecountsLifeEvents`; the type setting goes through the life event type actions. |
| II. Simplicity | Pass: one column, an existing trait extended, no observer, no new model. |
| III. Tested before merged | Pass: action, job, migration and controller tests per [quickstart.md](quickstart.md). |
| IV. Laravel conventions | Pass. |
| V. Localized | Exception by request: strings go to `lang/en.json` only, translations run after the user's English test (research R7). The localize check fails until then. |
| VI. Nothing changes under anybody's feet | Pass: no structure version moves; a date that was never written gains a value. |
| VII. Stable core | Pass: a setting on a life event type configures a nature, which VII allows; no field, no template option. |
| VIII. Nothing disappears silently | Pass: nothing is removed. |
| IX. Accessible | Pass: the switch carries its label and its line through `aria-describedby`, as "Offered when recording" does. |
| X. Through the API | Pass: [contract](contracts/life-event-type-setting.md) covers the API and the MCP tools. |
| XI. Short record | Pass: every artifact under its cap. |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/141-last-in-touch/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/life-event-type-setting.md
└── tasks.md          (next step)
```

### Source Code (repository root)

```text
app/Actions/RecountsLifeEvents.php           the date, beside the count (R2)
app/Actions/CreateLifeEvent.php              recounts the date of its participants
app/Actions/{Create,Update,Import}LifeEventType.php   the setting
app/Jobs/RecordLastInTouchOfTheDay.php       daily, future life events (R3)
app/Jobs/RecountLastInTouchOfType.php        after a type's setting changes (R5)
app/Jobs/CreateDefaultLifeEventTypes.php     supplied defaults (R6)
app/Enums/LifeEventTypeEnum.php              updatesLastInTouch()
app/Models/LifeEventType.php, Contact.php    column, docblocks
app/Http/Controllers/{App/Settings/Account,Api}/LifeEventTypeController.php
app/Http/Resources/LifeEventTypeResource.php
app/Mcp/Tools/{Create,Update}LifeEventType.php
app/ViewModels/Settings/Account/LifeEventTypes/LifeEventTypeFormViewModel.php
resources/views/app/settings/account/life-event-types/form.blade.php
routes/console.php                           the daily schedule
database/migrations/                         add column, backfill (R8)
resources/docs/api/, resources/docs/api-changelog/, changelog/, docs/portal/en/
tests/Unit/{Actions,Jobs,Migrations}/, tests/Feature/Controllers/{App,Api}/
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Notes for tasks

- Read `.claude/rules/mcp.md` before touching the two MCP tools, and the `migrations` skill before
  the two migrations. Never run them locally.
- `CreateLifeEventTest:176` asserts the date stays null; it is rewritten, not deleted.
- The product guide page on life event types and the help bubble on the switch follow the `writer`
  and `help-bubbles` skills.
