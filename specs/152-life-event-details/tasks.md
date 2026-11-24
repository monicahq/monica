# Tasks: Details of a life event on the vault timeline

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/interfaces.md](contracts/interfaces.md)

**Tests**: required by constitution principle III and listed in the plan. Rules in `.claude/rules/testing.md`.

## Phase 1: Setup

- [X] T001 Read `.claude/rules/components.md`, `.claude/rules/testing.md`, and the `view-models`, `views` and `controllers` skills before writing code

## Phase 2: Foundational

- [X] T002 Reuse `GroupPeriod::date()` and `between()` (`app/ViewModels/Vaults/GroupPeriod.php`) for the panel's full date: the dashboard's `dateOf()` drops the year, so moving it would share nothing
- [X] T003 Add `Route::get('vaults/{vault}/life-events/{lifeEvent}', [VaultLifeEventController::class, 'show'])->name('vaults.lifeEvents.show')` in `routes/web.php`, next to the other `vaults.lifeEvents.*` routes

## Phase 3: User Story 1, read everything known about one life event (P1)

**Goal**: **Details** opens a panel with every recorded detail of the event. **Independent test**: spec User Story 1.

- [X] T004 [P] [US1] Write `tests/Unit/ViewModels/Vaults/LifeEventDetailsViewModelTest.php`: type and category, date by precision with end date, time zone shown only when it differs from the reader's, duration, place, group, fields in section then field order with empty ones left out, file and image fields under files only, contact and entity participants with role and notes, partial contact without a link, relationship to the `is_me` contact, footer source and dates, every block absent when empty
- [X] T005 [US1] Create `app/ViewModels/Vaults/LifeEventDetailsViewModel.php` taking `User $user`, `Vault $vault`, `LifeEvent $lifeEvent`, eager loading the relations of research R3 and returning the lines of research R4
- [X] T006 [US1] Add `show(Request $request, Vault $vault, LifeEvent $lifeEvent): View` to `app/Http/Controllers/App/Vaults/VaultLifeEventController.php`, returning `app.vaults._life-event-details` with the view model
- [X] T007 [US1] Create `resources/views/app/vaults/_life-event-details.blade.php` with `id="life-event-details-{uuid}"`, using `x-panel`, `x-badge`, `x-detail-list` with `x-detail-list.row` and `x-contact-avatar`, and a **Close** button that sets `open = false` and focuses **Details**
- [X] T008 [US1] Add `detailsUrl` to each entry in `app/ViewModels/Vaults/DashboardTimelineBlock.php::entry()`, built with `route('vaults.lifeEvents.show', ...)` from the loaded event
- [X] T009 [US1] In `resources/views/app/vaults/_timeline.blade.php`, give each entry `x-data="{ open: false, loaded: false, failed: null }"`, a **Details** / **Hide details** ghost button beside **Edit** (shown whether or not **Edit** is) with `aria-expanded`, `aria-controls` and a chevron that turns, and an `x-show="open"` target holding an `aria-busy="true"` placeholder; the first open calls `$ajax(detailsUrl, { target: 'life-event-details-{uuid}', focus: false })` as `resources/views/app/changelog/_feedback.blade.php` does
- [X] T010 [US1] Handle `@ajax:error` in `resources/views/app/vaults/_timeline.blade.php`: "This life event no longer exists." on 404, otherwise "The details could not be loaded." with **Try again** (check the `ajax:error` detail in the installed Alpine Ajax, research R5); placeholder pulse and chevron turn under `motion-safe:` only
- [X] T011 [P] [US1] Add to `tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php`: `show` answers with copy the reader recognises, a trashed event answers 404, an event reached through another vault of the same account answers 404
- [X] T012 [P] [US1] Add to `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php`: every entry carries `detailsUrl`, including one with no `editUrl`

## Phase 4: User Story 2, the timeline stays fast (P1)

**Goal**: nothing is read before somebody asks. **Independent test**: spec User Story 2.

- [X] T013 [US2] Run the existing query count test in `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php` (around line 311) unchanged and confirm it passes with the expected count left as it was
- [X] T014 [US2] Add to `tests/Unit/ViewModels/Vaults/LifeEventDetailsViewModelTest.php` a test counting queries for an event with 1 participant and 1 field and one with 5 participants and 5 fields, asserting the same count

## Phase 5: Polish and cross-cutting concerns

- [X] T015 [P] Extend `app/Http/Resources/LifeEventResource.php` with `date_precision`, `ended_on`, `happened_at_time`, `timezone`, `role` and `notes` on each participant, and `entity_participants` (research R6, contract in `contracts/interfaces.md`)
- [X] T016 [P] Add to `tests/Feature/Controllers/Api/LifeEventControllerTest.php` assertions on the new attributes and `entity_participants`, with `$jsonStructure` updated
- [X] T017 [P] Document the new attributes in `resources/docs/api/22-life-events.php` and add an entry to `resources/docs/api-changelog/2026-11-24.php`, following the `api-docs-writer` skill
- [X] T018 [P] Add a paragraph on **Details** to `docs/portal/en/4-relationships-and-life-events/5-the-timeline.md`, section "The timeline of a whole vault", following the `writer` skill
- [X] T019 [P] Write the What's new entry in `changelog/` following the `changelog` skill
- [X] T020 Run `php artisan monica:localize`, then translate every new string in every locale with Sonnet subagents per the `translations` skill, then `php artisan monica:localize --check`
- [X] T021 Run the `accessibility-reviewer` agent on `resources/views/app/vaults/_timeline.blade.php` and `resources/views/app/vaults/_life-event-details.blade.php` and fix what it confirms
- [X] T022 Run the `definition-of-done` skill: affected tests, `composer test:parallel`, PHPStan, Pint
- [X] T023 Walk through `quickstart.md` on the local instance and take the screenshot for the pull request with the `take-screenshot` skill

## Dependencies

- T002 and T003 before Phase 3. T005 before T006 and T007. T007 and T008 before T009, T009 before T010.
- Phase 4 after Phase 3. Phase 5's T015 to T019 need only Phase 2; T020 to T023 come last.

## Parallel opportunities

- T004, T011, T012 (three test files) alongside T005.
- T015 to T019 (five different files) together once Phase 2 is done.

## Implementation strategy

- MVP: Phases 1 to 4. User Story 2 is a constraint on User Story 1, proven by tests rather than built separately.
- Then Phase 5 for the API, documentation, translations and review.
