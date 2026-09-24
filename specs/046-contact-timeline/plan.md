# Implementation Plan: Contact timeline and adding a life event

**Branch**: `2026-09-23-contact-timeline` | **Date**: 2026-09-23 | **Spec**: [spec.md](spec.md)

## Summary

Create the life event record and its participants as `spec/models/036` and `037` define, record one
through a new `CreateLifeEvent` action that reuses the field engine for the type’s custom fields,
and replace the Overview’s stub History with a Timeline and the two step panel. Decisions are in
[research.md](research.md), columns in [data-model.md](data-model.md), routes, posted fields and the
markup contract in [contracts/life-event-panel.md](contracts/life-event-panel.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: two new tables; `contacts` counters already exist

**Testing**: PHPUnit: the action, the engine widening, two view model blocks, three controllers

**Target Platform**: web application, the vault contact screen

**Project Type**: server rendered web application

**Performance Goals**: the Overview adds a fixed number of queries whatever the history holds
(one page of 30 entries, eager loaded; one count per year shown; the panel’s types eager loaded)

**Constraints**: WCAG 2.2 AA; every string in every locale; portable SQL on the three drivers

**Scale/Scope**: 2 migrations, 2 models, 2 enums, 1 action, 3 widened actions, 3 controllers, 2
view model blocks, 1 script, 3 views

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Everything written goes through `CreateLifeEvent`; view model blocks only read |
| II. Simplicity over abstraction | Pass | Engine widened by union type, no interface; reuses `x-field-input`, `x-toaster`, the relationship search pattern |
| III. Tested before merged | Pass | One layer each, per `.claude/rules/testing.md` |
| IV. Laravel conventions | Pass | `up()` only, no `constrained()`, one named route per line, inline validation |
| V. Localized and safe | Pass | New strings through `monica:localize`; counts through `trans_choice()`; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | A life event keeps the version its type published when it was recorded |
| VII. Stable core | Pass | The life event is the fixed concept; its type stays the configurable part. Wording fixed (R9) |
| VIII. Nothing disappears silently | Pass | No deletion in this feature |
| IX. Accessible to everybody | Pass | Markup contract in the contract file; field types already accessible by construction |
| X. Everything goes through the API | Gap, widened | See Complexity Tracking |
| XI. The written record is short | Over | spec 136 of 120; see Complexity Tracking |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/046-contact-timeline/
├── plan.md  research.md  data-model.md  quickstart.md
├── contracts/life-event-panel.md
└── tasks.md                                         # /speckit-tasks
```

### Source Code (repository root)

```text
database/migrations/2026_09_23_00000{0,1}_create_life_event{s,_participants}_table.php
database/factories/LifeEvent{,Participant}Factory.php
app/Models/LifeEvent.php  LifeEventParticipant.php  Contact.php (lifeEvents)
app/Enums/DatePrecisionEnum.php  LifeEventSourceEnum.php  UserActionEnum.php (LifeEventCreation)
app/Exceptions/LifeEventTypeIsNotAvailable.php
app/Actions/CreateLifeEvent.php
app/Actions/SetFieldValues.php  SetFieldValue.php  CreateFieldValueRow.php   # accept LifeEvent
app/Http/Controllers/App/Vaults/ContactLifeEvent{,Field,Candidate}Controller.php
app/ViewModels/Vaults/ContactTimelineBlock.php  LifeEventFormBlock.php  ContactViewModel.php
resources/js/life-event-panel.js  app.js (register)
resources/views/app/vaults/contacts/_timeline.blade.php  _life-event-panel.blade.php
resources/views/app/vaults/contacts/_life-event-fields.blade.php  _life-event-candidates.blade.php
resources/views/app/vaults/contacts/show.blade.php            # History replaced
routes/web.php
.specify/memory/constitution.md                              # 2.4.1, R9
tests/Unit/Actions/CreateLifeEventTest.php  SetFieldValuesTest.php
tests/Unit/Models/LifeEventTest.php                          # name accessor
tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php  LifeEventFormBlockTest.php
tests/Feature/Controllers/App/Vaults/ContactLifeEvent{,Field,Candidate}ControllerTest.php
lang/*.json                                                  # through monica:localize
```

**Structure Decision**: the existing layout. The Overview keeps one view model and delegates the two
new blocks to small objects beside it, as `ImmediateFamilyBlock` does.

## Complexity Tracking

| Item | Why | Simpler option rejected |
| --- | --- | --- |
| No API for life events (principle X) | A new capability ships screen first, as life event types did in 037 before 038 | Adding the endpoints doubles an already large scope; the logic sits in `CreateLifeEvent`, so the API is transport only |
| Spec over its cap (principle XI) | Two surfaces, and the panel’s twelve acceptance scenarios each decide a behaviour | Cutting them would leave the panel’s behaviour to the implementer |
