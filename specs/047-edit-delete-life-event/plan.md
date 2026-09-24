# Implementation Plan: Edit and delete a life event

**Branch**: `2026-09-23-contact-timeline` | **Date**: 2026-09-23 | **Spec**: [spec.md](spec.md)

## Summary

Three actions change a recorded life event: `UpdateLifeEvent` rewrites it through the engine and
moves it between versions when its type changes, `DestroyLifeEvent` soft deletes it, `RestoreLifeEvent`
brings it back; all three recount the affected contacts through one trait. The panel of 046 gains an
edit mode opened by the server, and each timeline entry gains Edit, Delete with an inline confirmation,
and an Undo notice. Decisions are in [research.md](research.md); routes, fields and copy in
[contracts/life-event-edit-delete.md](contracts/life-event-edit-delete.md). There is no schema change,
so there is no `data-model.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: the tables of 046; no migration

**Testing**: PHPUnit: three actions, the form and timeline blocks, two controllers

**Target Platform**: web application, the vault contact screen

**Project Type**: server rendered web application

**Performance Goals**: the recount is two queries per affected contact; the timeline adds none

**Constraints**: WCAG 2.2 AA; every string in every locale; portable SQL on the three drivers

**Scale/Scope**: 3 actions, 1 trait, 1 new controller and 3 methods, 3 enum cases, 2 blocks and 3 views
edited, 1 script edited

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Every write goes through one of the three actions; controllers map fields |
| II. Simplicity over abstraction | Pass | One trait for a rule three actions share; the panel of 046 is reused, not copied |
| III. Tested before merged | Pass | One layer each |
| IV. Laravel conventions | Pass | Soft deletes, one named route per line, inline validation |
| V. Localized and safe | Pass | Deletion confirmed before it runs, undoable after; strings through `monica:localize` |
| VI. Nothing changes under anybody’s feet | Pass | An unchanged type keeps the version the life event was recorded on |
| VIII. Nothing disappears silently | Pass | Soft delete, files held, Undo offered; removals journalled |
| IX. Accessible to everybody | Pass | Actions revealed on focus and without hover; alertdialog with managed focus |
| X. Everything goes through the API | Gap, widened | See Complexity Tracking |
| XI. The written record is short | Pass | spec 119, plan under 100; `data-model.md` omitted |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/047-edit-delete-life-event/
├── plan.md  research.md  quickstart.md
├── contracts/life-event-edit-delete.md
└── tasks.md                                         # /speckit-tasks
```

### Source Code (repository root)

```text
app/Actions/RecountsLifeEvents.php                   # trait
app/Actions/UpdateLifeEvent.php  DestroyLifeEvent.php  RestoreLifeEvent.php
app/Enums/UserActionEnum.php                         # LifeEventUpdate, LifeEventDeletion, LifeEventRestoration
app/Http/Controllers/App/Vaults/ContactLifeEventController.php            # edit, update, destroy
app/Http/Controllers/App/Vaults/ContactLifeEventRestorationController.php # update
app/Http/Controllers/App/Vaults/ContactLifeEventFieldController.php       # ?lifeEvent
app/ViewModels/Vaults/LifeEventFormBlock.php         # optional LifeEvent, edit wording, stored values
app/ViewModels/Vaults/ContactTimelineBlock.php       # edit url, isEditing, isEdited, participants' names
app/ViewModels/Vaults/ContactViewModel.php           # editing life event, deleted notice
resources/views/app/vaults/contacts/_timeline.blade.php  _life-event-panel.blade.php  show.blade.php
resources/views/app/vaults/contacts/_life-event-fields.blade.php          # kept_files[]
resources/js/life-event-panel.js
routes/web.php
tests/Unit/Actions/UpdateLifeEventTest.php  DestroyLifeEventTest.php  RestoreLifeEventTest.php
tests/Unit/ViewModels/Vaults/LifeEventFormBlockTest.php  ContactTimelineBlockTest.php
tests/Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php
tests/Feature/Controllers/App/Vaults/ContactLifeEventRestorationControllerTest.php
lang/*.json                                          # through monica:localize
```

**Structure Decision**: the existing layout, extending the files 046 created rather than adding
parallel ones.

## Complexity Tracking

| Item | Why | Simpler option rejected |
| --- | --- | --- |
| No API for life events (principle X) | Same gap as 046, widened by two capabilities | The API for the whole life event belongs in one feature; the logic sits in the three actions, so it is transport only |
