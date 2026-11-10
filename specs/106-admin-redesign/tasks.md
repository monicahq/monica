# Tasks: Admin space redesign

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III; each story writes its tests first.

## Phase 1: Foundational

- [X] T001 Add the fixed tokens `--admin-bar` `#18181b`, `--on-admin-bar` `#fafafa`, `--on-admin-bar-muted` `#a1a1aa`, `--admin-signal` `#fbbf24` to `:root` only (never under `.dark`), with their `--color-*` entries in `@theme`, in `resources/css/app.css` (R1)

## Phase 2: User Story 1, the shell on every admin screen (P1, MVP)

- [X] T002 [US1] Add tests for the full warning sentence, the "Instance" badge, the `Admin sections` navigation label and exactly one `aria-current="page"` (on the Accounts route) to `tests/Feature/Views/Layouts/AdminLayoutTest.php`; keep the existing tests
- [X] T003 [P] [US1] Repoint the `inverted` tone from `on-primary` to the `on-admin-bar` and `on-admin-bar-muted` colours, with a `ring-on-admin-bar/40` focus ring, in `resources/views/components/theme-toggle.blade.php` (R2)
- [X] T004 [US1] Rewrite the header of `resources/views/layouts/admin.blade.php`: `aria-hidden` `print:hidden` striped band (R3), `bg-admin-bar text-on-admin-bar` bar 64px high, 29px high logo, "Admin space" and the `--admin-signal` "Instance" badge, remove the one line warning from the bar, keep the leave link, theme toggle and account menu (R2)
- [X] T005 [US1] In the same file, add an `icon` key to each `@foreach` item (activity, tasks, people, settings, tag, key, timeline), wrap the list in `<nav aria-label="{{ __('Admin sections') }}">`, mark the current item with `aria-current="page"`, full weight text and a 3px `border-admin-signal` underline, others in `text-on-admin-bar-muted` (R4)
- [X] T006 [US1] In the same file, add the full width `role="note"` warning strip under the header with the two sentence warning from FR-005 (R5)
- [X] T007 [US1] Run `tests/Feature/Views/Layouts/AdminLayoutTest.php` and `tests/Feature/Controllers/App/Admin/AdminAccessTest.php`, fix what fails

## Phase 3: User Story 2, the Overview figures (P2)

- [X] T008 [US2] Add view model tests for `countedAt()` (Michael Scott on `America/New_York`, 12 hour, frozen time), `moderationSentence()` at 0 and at 2, and the two period labels, to `tests/Unit/ViewModels/Admin/OverviewViewModelTest.php`; pass `user:` to every existing construction
- [X] T009 [US2] Give `OverviewViewModel` a promoted `private readonly User $user` constructor and add `countedAt()`, `moderationSentence()`, `signedInLabel()`, `newAccountsLabel()` with the wordings of R7, in `app/ViewModels/Admin/OverviewViewModel.php`
- [X] T010 [US2] Pass `user: $request->user()` to the view model in `app/Http/Controllers/App/Admin/OverviewController.php`
- [X] T011 [US2] Rewrite `resources/views/app/admin/overview/index.blade.php` above the email section: page header with `x-timestamp` "Counted at" and the "Count again" `x-button.secondary size="small"` (R6), the empty instance notice, "What the instance holds" strip of six totals with icons and the footnote (R9), the "Waiting for you" and "Coming and going" `x-panel`s side by side; remove the nine tiles and the moderation notice
- [X] T012 [US2] Update `tests/Feature/Controllers/App/Admin/OverviewControllerTest.php`: drop the `right now` and notice assertions, assert "Open moderation" and `route('admin.moderation.index')` are on the screen, keep one test per behaviour the controller owns

## Phase 4: User Story 3, where the emails got to (P3)

- [X] T013 [US3] Add view model tests for `emailShares()` (6, 2, 1, 1 gives 60, 20, 10, 10; nothing sent gives four zeros) and `emailSummary()` to `tests/Unit/ViewModels/Admin/OverviewViewModelTest.php`
- [X] T014 [US3] Add `emailShares()` and `emailSummary()` (R7, R8) to `app/ViewModels/Admin/OverviewViewModel.php`
- [X] T015 [US3] Replace the four email tiles in `resources/views/app/admin/overview/index.blade.php` with an `x-panel` holding the `role="img"` bar labelled by `emailSummary()`, segments sized by inline `style` widths, the hatched "On its way" segment (R3), and the four item legend

## Phase 5: Polish

- [X] T016 Run `php artisan monica:localize`, translate every reported wording in every locale following `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T017 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, fix every finding
- [X] T018 Run the full suite with `php artisan test --compact` (the layout and the theme toggle are shared)
- [X] T019 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff, check their evidence, fix what holds
- [X] T021 Apply the confirmed findings of both reviews (focus recipes, inverted button, aside, section matching, counted moment, test layers)
- [ ] T020 Walk the by hand table in `quickstart.md` at `https://erica.test/admin`, light and dark, and report what was and was not checked

## Dependencies

- T001 before T003, T004, T005.
- US1 (T002 to T007) needs only T001. US2 and US3 do not need US1.
- US3 after US2: both edit `OverviewViewModel.php` and `overview/index.blade.php`.
- Inside a story: tests, then view model, then controller, then view.
- Polish after every story.
