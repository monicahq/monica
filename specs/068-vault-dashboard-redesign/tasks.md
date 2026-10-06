# Tasks: Vault dashboard redesign

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`. View models own what each block shows; the job owns the timestamp; controller tests prove the screen renders, `show()` queues the visit and the API carries the field. No test asserts a class or the look.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/view-models/SKILL.md`, `.claude/skills/views/SKILL.md`, `.claude/skills/controllers/SKILL.md`, `.claude/skills/api-docs-writer/SKILL.md`, `.claude/skills/translations/SKILL.md`, `.claude/rules/components.md` and `.claude/rules/testing.md` before touching `app/ViewModels/Vaults/VaultDashboardViewModel.php`

## Phase 2: Foundational, the new frame (blocks every story)

- [X] T002 Remove `unbuiltSections()`, `settingsRows()`, `people()`, `peopleCaption()`, `allPeopleLabel()`, `deletionConsequence()`, `currentTemplateName()` and `settingsUrl()` from `app/ViewModels/Vaults/VaultDashboardViewModel.php`, and their tests from `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php`
- [X] T003 Add `tile()` returning `{initial, colour, tint}` built exactly as `app/ViewModels/Vaults/VaultsViewModel.php:57-59` builds them (`mb_strtoupper(mb_substr(...))`, `colour`, the `color-mix` tint), with a test of the initial in `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php`
- [X] T004 Rewrite `resources/views/app/vaults/show.blade.php`: header (tile, `h1` name, description, figures slot, "Add somebody" with its ceiling refusal kept as today), then a two column layout (main `flex-2`, side `flex-1`, stacked under `lg`), with no empty vault state, since every vault holds its Me contact (spec 069)

## Phase 3: User Story 1, see and answer the dates that need attention (P1) MVP

**Independent test**: one date overdue, one today, one in five days and one in eight; the dashboard shows three bands holding the first three; answering the overdue one redirects back and it is gone.

- [X] T005 [US1] Create `app/ViewModels/Vaults/VaultDeadlines.php` holding the body of `VaultRemindersViewModel::rows()` (awaiting occurrences up to today, plus `NextReminderDeadlines` for active rules, mapped to `ReminderRow`), and make `rows()` in `app/ViewModels/Vaults/VaultRemindersViewModel.php` read it (research R2)
- [X] T006 [US1] Run `tests/Unit/ViewModels/Vaults/VaultRemindersViewModelTest.php` unchanged to prove the extraction; add `tests/Unit/ViewModels/Vaults/VaultDeadlinesTest.php` only for what that test does not already prove
- [X] T007 [US1] Test in `tests/Unit/ViewModels/Vaults/VaultDashboardAttentionTest.php`: bands in the order Overdue, Today, Next seven days; a date eight days out left out; an empty band left out; an answered date left out; `isEmpty()`; `count()`
- [X] T008 [US1] Create `app/ViewModels/Vaults/VaultDashboardAttention.php` reading `VaultDeadlines`, keeping rows with `local_date` on or before the reader's today plus seven days, bucketed by `ReminderOccurrenceCategoryEnum::for()`, each band `{title, icon, isOverdue, rows}` with icons `clock`, `sun`, `calendar` (research R3, R7)
- [X] T009 [US1] Add `attention()` to `app/ViewModels/Vaults/VaultDashboardViewModel.php` returning one `VaultDashboardAttention`, built once
- [X] T010 [US1] Create `resources/views/app/vaults/_attention.blade.php`: section headed "Needs your attention" with the `bell` icon and the line "Overdue, today and the next seven days.", a band header naming each band in text, each row with the `reminder` icon tile, the contact link (`ReminderRow::contactUrl()`), `text()`, `relativeDay()`, and `@include('app.vaults.reminders._answers', ['row' => $row])`; the empty sentence when nothing is due; the "All reminders" link to `reminders.index`
- [X] T011 [US1] Include `_attention` in the main column of `resources/views/app/vaults/show.blade.php`

## Phase 4: User Story 2, reach favourite and recently viewed people (P2)

**Independent test**: two favourites and three contacts opened in order; the dashboard lists the two favourites by name and the three opened, newest first.

- [X] T012 [P] [US2] Test in `tests/Unit/Jobs/RecordContactVisitTest.php` that the job writes `last_consulted_at` and leaves `updated_at` unchanged
- [X] T013 [P] [US2] Create `app/Jobs/RecordContactVisit.php` on the model of `app/Jobs/RecordVaultVisit.php`, writing `contacts.last_consulted_at` with a plain `DB::table()` update (research R1)
- [X] T014 [US2] Dispatch `RecordContactVisit::dispatch(contact: $contact)->onQueue('low')` in `ContactController::show()` after the `abort_unless`, in `app/Http/Controllers/App/Vaults/ContactController.php`, `app/Http/Controllers/Api/ContactController.php::show()`, `app/Mcp/Tools/GetContact.php` and `app/Mcp/Tools/GetMeContact.php`, each asserted with `Queue::assertPushedOn('low', ...)` in its test
- [X] T015 [P] [US2] Add `last_consulted_at` as `?->timestamp` after `last_interaction_at` in `app/Http/Resources/ContactResource.php`, add it to `$jsonStructure` in `tests/Feature/Controllers/Api/ContactControllerTest.php`, and document it in `resources/docs/api/06-contacts.php` (research R8)
- [X] T016 [US2] Test `favourites()` and `recentlyViewed()` in `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php`: name order, ten at most, `moreFavouritesUrl()` only beyond ten, archived and partial contacts left out, another vault's contacts left out, never opened contacts left out, newest first, "today", "yesterday", then the date in the reader's format
- [X] T017 [US2] Implement `favourites()` (`listed()`, `is_favourite`, collator name order, limit 10, avatar eager loaded), `moreFavouritesUrl()` (`contacts.index` with `filter` = `ContactGroupingEnum::Favourites`) and `recentlyViewed()` (`listed()`, `last_consulted_at` not null, latest five) in `app/ViewModels/Vaults/VaultDashboardViewModel.php` (research R6)
- [X] T018 [US2] Add the "Favourite people" (`star`) and "Recently viewed" (`clock`) sections to the side column of `resources/views/app/vaults/show.blade.php`, each row a link with `x-contact-avatar`, each with its one sentence empty state

## Phase 5: User Story 3, figures and recent life events (P3)

**Independent test**: 25 people, four dates in the attention list, five favourites and five life events in the last three months; the line and the list match.

- [X] T019 [US3] Test `figures()` and `lifeEvents()` in `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php`: each figure at zero, one and several; the three month window; latest five by `happened_on`; every participant named; the link to the first participant who is not partial, none when there is none
- [X] T020 [US3] Implement `figures()` as counted phrases through `trans_choice()` (people, reminders this week from `attention()->count()`, favourites, life events in the last three months) and `lifeEvents()` (type icon checked against `Section::ICONS`, `VaultColourEnum::tintFor()` tint, `DateFormatEnum::write()` date, participants, link) in `app/ViewModels/Vaults/VaultDashboardViewModel.php` (research R5, R6)
- [X] T021 [US3] Render the figures line in the header and the "Recent life events" (`timeline`) section at the foot of the side column of `resources/views/app/vaults/show.blade.php`, a row without a link rendered as plain text

## Phase 6: Polish

- [X] T022 Rewrite the dashboard test in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` to assert the copy that identifies the screen ("Needs your attention", "Favourite people") instead of the removed boxes (`it_opens_a_vault` asserts "Vault settings" at line 93), and make `it_leads_to_the_settings_of_the_vault` assert the sidebar link still reaches `vaults.settings.index`
- [X] T023 Run `php artisan monica:localize`, translate every new phrase in every locale following `.claude/rules/translations.md`, then `--check`
- [X] T024 Screenshot the dashboard full and empty, light and dark, at 1280px and 375px with the `take-screenshot` skill, and check keyboard order, headings and 200% zoom
- [X] T025 Run the whole suite, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`; open the pull request

## Dependencies

- T001 before everything. Phase 2 (T002 to T004) before every story: each story renders into the new layout.
- T005 before T006 and T008. T008 before T009, T009 before T010.
- US2 does not depend on US1. US3's "reminders this week" figure needs T009.
- T022 to T025 come last.

## Parallel examples

- US2: T012, T013 and T015 touch different files and run together; T014 waits for T013.
- US1 and US2 can proceed side by side after Phase 2, apart from both editing `show.blade.php` (T011, T018).

## Implementation strategy

US1 is the MVP: it replaces the placeholders with the one block somebody acts on. Then US2, US3 and polish, in one pull request.
