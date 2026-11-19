# Tasks: Flag usage metrics

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: included, since principle III makes them a delivery condition.

## Phase 1: Setup

- [X] T001 Update main, create branch `2026-11-19-flag-usage-metrics` from it, and carry specs/135-flag-usage-metrics/ over to it

## Phase 2: Foundational

None. The two stories share no code.

## Phase 3: User Story 1, how many accounts have each flag on (P1) 🎯 MVP

**Independent test**: switch a flag on for two accounts out of five while it is off for every account; the Flags screen and `monica:list-flags` read on for 2 of 5.

- [X] T002 [P] [US1] Add `public static function accountsOn(string $flag): int` to app/Helpers/FlagState.php: count `features` rows named `$flag` whose scope is not `Feature::serializeScope(null)`, split into stored `true` and all stored; return stored true + (`Feature::for(null)->active($flag)` ? `Account::query()->count()` − all stored : 0), as in research.md R3
- [X] T003 [P] [US1] Test in tests/Unit/Helpers/FlagStateTest.php, with five accounts: off for every account and on for Jim and Pam gives 2; then on for every account gives 5; on for every account then off for Dwight gives 4; an account never asked counts with the instance answer
- [X] T004 [US1] Add `'reach' => trans_choice('On for :on of :count account|On for :on of :count accounts', $total, ['on' => FlagState::accountsOn($flag)])` to each row of `flags()` in app/ViewModels/Admin/FlagsViewModel.php, counting the total once outside the `array_map`, and add `reach: string` to the `@return` shape
- [X] T005 [US1] Print `{{ $flag['reach'] }}` under the flag's name, in the row's existing secondary text style, in resources/views/app/admin/flags/index.blade.php
- [X] T006 [US1] Assert the row carries "On for 2 of 5 accounts" in tests/Unit/ViewModels/Admin/FlagsViewModelTest.php
- [X] T007 [US1] Append `, on for {$on} of {$total} accounts` to each line, counting the total once, and say so in `#[Description]`, in app/Console/Commands/ListFlagsCommand.php
- [X] T008 [US1] Assert each line carries the count in tests/Unit/Console/Commands/ListFlagsCommandTest.php
- [X] T009 [US1] Run `php artisan monica:localize` and translate the new phrase into every lang/*.json through the translations skill, with sonnet subagents, then `php artisan monica:localize --check`
- [X] T010 [US1] Run `php artisan test --compact` on FlagStateTest, FlagsViewModelTest, ListFlagsCommandTest and tests/Feature/Controllers/App/Admin/FlagControllerTest.php

## Phase 4: User Story 2, who uses a flag, read in Nightwatch (P2)

**Independent test**: with a flag on for Jim and off for Dwight, Jim's check leaves `flags: [name]` in the context once, however often asked; Dwight's check and a check for nobody leave nothing; a job queued after Jim's check carries no `flags`.

- [X] T011 [P] [US2] Create app/Listeners/NameFlagInContext.php with `handle(FeatureRetrieved $event): void`: return unless `$event->scope instanceof Account` and `$event->value !== false`; return if `Context::stackContains('flags', $event->feature)`; else `Context::push('flags', $event->feature)` (research.md R1, R2)
- [X] T012 [US2] In `configureFlags()` of app/Providers/AppServiceProvider.php, add `Context::dehydrating(fn (Repository $context) => $context->forget('flags'))` with a one line comment on why a queued job must not inherit its request's flags
- [X] T013 [US2] Test in tests/Unit/Listeners/NameFlagInContextTest.php: Jim's flag asked three times gives `['vcard-export']`; Dwight's refused flag and `Feature::for(null)` give no `flags`; `Context::dehydrate()` holds no `flags` while `Context::get('flags')` still does
- [X] T014 [US2] Run the whole suite with `PAO_DISABLE=1 php artisan test --parallel`, since the listener runs on every flag check in every test

## Phase 5: Polish

- [X] T015 Run `vendor/bin/pint --dirty --format agent` and PHPStan on the touched files
- [X] T016 Run the definition-of-done skill; record in its report that no API, MCP tool, help page or What's new entry is due (plan.md, Changes)
- [ ] T017 Follow plan.md Validation steps 2 to 4 on a deployment with Nightwatch on, and record in research.md R5 whether Nightwatch filters requests by a context value (SC-003)

## Dependencies

- T001 before everything.
- US1: T002 before T004 and T007; T004 before T005 and T006; T007 before T008; T009 after T004; T010 last.
- US2: T011 and T012 before T013; T014 last. US2 does not depend on US1.
- Polish after both stories; T017 needs the change deployed.

## Parallel examples

- US1: T002 and T003 together (helper and its test), then T004 and T007 together (different files).
- US2: T011 alongside any US1 task; US2 touches none of US1's files.
- Across stories: Phase 3 and Phase 4 can run at the same time once T001 is done.

## Implementation strategy

Ship US1 first: it is useful alone and touches only admin code. US2 follows in the same branch; it is
small but runs on every flag check, so T014 is the gate before Polish.
