---
description: "Task list for Last in touch"
---

# Tasks: Last in touch

**Input**: Design documents from `specs/141-last-in-touch/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Included. Principle III makes tests a merge condition. Follow `.claude/rules/testing.md`:
each rule once, in the layer that owns it; data from The Office (US).

**Translations**: new strings go to `lang/en.json` through `php artisan monica:localize` only. No
locale is translated in this change (research R7).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

- [X] T001 Confirm the branch is `2026-11-20-last-in-touch`, stacked on `2026-11-20-vault-chosen-contact-template`, and that `CreateLifeEventTest:176` still asserts `last_interaction_at` stays null

---

## Phase 2: Foundational (blocking)

**Purpose**: the column and the defaults both stories read (data-model.md, research R5, R6).

- [X] T002 Generate `database/migrations/2026_11_20_000001_add_updates_last_in_touch_to_life_event_types_table.php` with `php artisan make:migration`, renamed to that prefix: `boolean('updates_last_in_touch')->default(true)->after('is_active')` with the comment "whether its life events set the last in touch date of the people they are recorded with"; `up()` only
- [X] T003 Add `updates_last_in_touch` to `#[Fillable]`, the boolean cast and the docblock of `app/Models/LifeEventType.php`, and to `database/factories/LifeEventTypeFactory.php` (default `true`)
- [X] T004 [P] Add `updatesLastInTouch(): bool` to `app/Enums/LifeEventTypeEnum.php`, returning `false` exactly for the "No" keys of research R6 and `true` otherwise; read it in `app/Jobs/CreateDefaultLifeEventTypes.php` when creating each type
- [X] T005 [P] Correct the docblock of `last_interaction_at` in `app/Models/Contact.php` to the meaning in data-model.md, and the docblock of `app/Models/LifeEventCategory.php` that speaks of Call "counting as an interaction" so it names the type setting instead

---

## Phase 3: User Story 1 - The header says when I was last in touch (P1) 🎯 MVP

**Goal**: `last_interaction_at` is written from qualifying life events and read everywhere it already is.

**Independent Test**: record a Meal with Jim Halpert; his header, the **Last spoke** column and `GET /contacts/{contact}` agree.

- [X] T006 [US1] Extend `recountLifeEventsOf()` in `app/Actions/RecountsLifeEvents.php` to also set `last_interaction_at`: the latest `happened_on` not after today among the contact's non deleted life events whose type has `updates_last_in_touch`, at start of day, null when none (data-model.md conditions 1 to 4)
- [X] T007 [US1] In `app/Actions/CreateLifeEvent.php`, after `countOn()`, recount the date of every participant through the trait (keep the count increment as it is)
- [X] T008 [P] [US1] Create `app/Jobs/RecordLastInTouchOfTheDay.php`: recount the participants of qualifying life events dated yesterday or today; class docblock says why it runs daily (research R3); schedule it in `routes/console.php` with `Schedule::job(new RecordLastInTouchOfTheDay, 'low')->dailyAt('02:15')->sentryMonitor()`
- [X] T009 [US1] Generate `database/migrations/2026_11_20_000002_backfill_last_interaction_at.php`: with `DB::table()` only, set `updates_last_in_touch = false` on system types whose `key` is in the "No" list of research R6 (written as literals), then queue `RecountLastInTouchOfType` on `low`, delayed by five minutes, for every type that updates Last in touch and holds life events
- [X] T010 [US1] Rewrite `CreateLifeEventTest:176` in `tests/Unit/Actions/CreateLifeEventTest.php` and add there: a qualifying life event sets the date on each participant; an older one leaves it; a future one leaves it; a type that does not update leaves it
- [X] T011 [P] [US1] Add to `tests/Unit/Actions/DestroyLifeEventTest.php` and `tests/Unit/Actions/RestoreLifeEventTest.php`: deleting the latest falls back to the previous qualifying date or null, restoring brings it back; to `tests/Unit/Actions/UpdateLifeEventTest.php`: moving the date and removing the contact recompute it
- [X] T012 [P] [US1] Skipped: `MergeContacts` calls the same recount T011 already proves, and `.claude/rules/testing.md` tests a rule once
- [X] T013 [P] [US1] Create `tests/Unit/Jobs/RecordLastInTouchOfTheDayTest.php`: a life event dated today becomes the date; one dated tomorrow does not, until the clock is moved with `$this->travelTo()`
- [X] T014 [P] [US1] Create `tests/Unit/Migrations/BackfillLastInteractionAtTest.php`: Move is switched off, one delayed recount is queued per type that updates Last in touch and holds life events, and a contact with a past Meal gets its date once those recounts run

**Checkpoint**: the reported defect is fixed for every account with the product defaults.

---

## Phase 4: User Story 2 - I choose which life events update Last in touch (P2)

**Goal**: the setting on each type, on screen, in the API and through MCP (contracts/life-event-type-setting.md).

**Independent Test**: switch Move on in Settings, then Jim's header moves to the date of his Move.

- [X] T015 [US2] Add an `updatesLastInTouch` parameter to `app/Actions/CreateLifeEventType.php` (default `true`), `app/Actions/UpdateLifeEventType.php` (dispatches `RecountLastInTouchOfType` on `low` only when the value changes) and `app/Actions/ImportLifeEventType.php` (always `true`, which the column default already gives, so it is unchanged)
- [X] T016 [P] [US2] Create `app/Jobs/RecountLastInTouchOfType.php`: recount every contact participating in a life event of the type, in chunks; class docblock says why it is queued
- [X] T017 [US2] Read `updates_last_in_touch` in `app/Http/Controllers/App/Settings/Account/LifeEventTypes/LifeEventTypeController.php` (inline `['nullable', 'boolean']`) and pass it to the actions
- [X] T018 [US2] Add `updatesLastInTouch()` and its copy to `app/ViewModels/Settings/Account/LifeEventTypes/LifeEventTypeFormViewModel.php`, and the switch to `resources/views/app/settings/account/life-event-types/form.blade.php` beside "Offered when recording", with the wording of research R7 and the line tied by `aria-describedby`
- [X] T019 [US2] Accept `updates_last_in_touch` (`['sometimes', 'boolean']`) in `app/Http/Controllers/Api/LifeEventTypeController.php` create and update, left out keeps the stored value, and return it in `app/Http/Resources/LifeEventTypeResource.php`
- [X] T020 [P] [US2] Add the optional boolean `updates_last_in_touch` to the schemas of `app/Mcp/Tools/CreateLifeEventType.php` and `app/Mcp/Tools/UpdateLifeEventType.php`, per `.claude/rules/mcp.md`
- [X] T021 [P] [US2] Tests: `tests/Unit/Actions/UpdateLifeEventTypeTest.php` (a change dispatches the recount, no change dispatches nothing), `tests/Unit/Jobs/RecountLastInTouchOfTypeTest.php` (switching Move on moves Jim's date), `tests/Unit/ViewModels/Settings/Account/LifeEventTypes/LifeEventTypeFormViewModelTest.php` (the value read)
- [X] T022 [P] [US2] Tests: `tests/Feature/Controllers/App/Settings/Account/LifeEventTypes/LifeEventTypeControllerTest.php` and `tests/Feature/Controllers/Api/LifeEventTypeControllerTest.php` (the value is saved, a non boolean answers 422, the API returns it); add it to the shared `$jsonStructure`

---

## Phase 5: Polish

- [X] T023 [P] Document `updates_last_in_touch` in `resources/docs/api/` for the life event type endpoints and the new meaning of `last_interaction_at` in `resources/docs/api/06-contacts.php`; add an entry to `resources/docs/api-changelog/2026-11-20.php`, following the `api-docs-writer` skill
- [X] T024 [P] Add a help bubble to the switch per the `help-bubbles` skill, and update the life event type page of `docs/portal/en/` per the `writer` skill
- [X] T025 [P] Write the What's new entry in `changelog/` per the `changelog` skill (type `fix`, area `Contacts`)
- [X] T026 Run `php artisan monica:localize` (English keys only), Pint, PHPStan and `composer test:parallel`; report `LocalizeCommandTest` as the one expected failure
- [X] T027 Walk `quickstart.md`'s by-hand steps after the user has run the migrations

---

## Dependencies

- T002 → T003 → T006; T004 needs T003; T009 needs T002 and T006
- US1 (T006 to T014) needs Phase 2; US2 (T015 to T022) needs T006 for its recount
- T016 is used by T015; T018 needs T017; tests follow the code they test
- Polish needs both stories

## Parallel examples

- Phase 2: T004 and T005 together, once T003 is done
- US1: T011, T012, T013 and T014 together, once T006 to T009 are done
- US2: T016 and T020 together; then T021 and T022 together
- Polish: T023, T024 and T025 together

## Implementation strategy

Ship US1 alone first if needed: with the defaults of research R6 it fixes the report for every
account. US2 then adds the setting. Translations follow the user's English test, in a later commit.
