# Tasks: Admin record screen redesign

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III; each story writes its tests first.

## Phase 1: Foundational

- [X] T001 Reword the four flag cases (R4) and add `icon(): string` and `namesTheAccountHolder(): bool` (R2, R3) in `app/Enums/AdministratorActionEnum.php`
- [X] T002 Give `AdministratorLogsViewModel` a `User $viewer` constructor argument, pass `viewer: $request->user()` from `app/Http/Controllers/App/Admin/AdministratorLogController.php`, and pass `viewer:` in every existing construction in `tests/Unit/ViewModels/Admin/AdministratorLogsViewModelTest.php`

## Phase 2: User Story 1, reading the record day by day (P1, MVP)

- [X] T003 [US1] Add view model tests to `AdministratorLogsViewModelTest.php`: `days()` for entries today, yesterday and on 2 November seen from `America/New_York` (headings, summaries, counts, order); `sentence()` for a flag on Pam Beesly's account (code part and link part), the same after her account is deleted (text part), an approved marketplace version (no link) and an unknown action (its stored name); `time()` in a 12 hour clock
- [X] T004 [US1] Add `days()`, `sentence()`, `time()`, `ago()` and `authorIcon()` per R1 and R5 to `app/ViewModels/Admin/AdministratorLogsViewModel.php`
- [X] T005 [US1] Rewrite `resources/views/app/admin/logs/index.blade.php` in a `max-w-240` column: `x-page-header` with the "Write once" `x-badge` (`lock`), the filter form unchanged, one `x-list-panel` holding a labelled `<section>` per day (header band with `h2` and summary) and an `<ol>` of entries with the kind icon, the sentence parts, the author line with its icon, `ago()` and the `x-timestamp` time (R6)
- [X] T006 [US1] Update `tests/Feature/Controllers/App/Admin/AdministratorLogControllerTest.php`: the first test asserts the "Write once" badge, a day heading and the link to the account; the filter, paging and empty state tests keep passing

## Phase 3: User Story 2, narrowing and paging (P2)

- [X] T007 [US2] Run the narrowing and paging tests in `AdministratorLogControllerTest.php` and `AdministratorLogsViewModelTest.php` against the new screen and fix what the redesign broke, changing no behaviour

## Phase 4: Polish

- [X] T008 Run `php artisan monica:localize`, translate every reported wording in every locale following `.claude/rules/translations.md` (the counted phrase in the segments each locale needs), then `php artisan monica:localize --check`
- [X] T009 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, fix every finding
- [X] T010 Run the admin tests, then the full suite with `php -d memory_limit=-1 vendor/bin/phpunit`
- [X] T011 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff, check their evidence, fix what holds
- [ ] T012 Walk the by hand table in `quickstart.md` and report what was and was not checked

## Dependencies

- T001 and T002 before T003.
- Inside a story: tests, then view model, then view.
- US2 after US1: it checks the screen US1 rewrites.
- Polish after every story.
