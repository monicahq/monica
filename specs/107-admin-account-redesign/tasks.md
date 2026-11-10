# Tasks: Admin account screen redesign

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III; each story writes its tests first.

## Phase 1: Foundational

- [X] T001 No test: `layout="stacked"` changes only the look, which `.claude/rules/testing.md` keeps out of view tests
- [X] T002 Add a `layout` prop (`columns` default, `stacked`) to `resources/views/components/detail-list/index.blade.php`, read through `@aware` in `row.blade.php`, where `stacked` drops the label column so the label sits above the value (R5)

## Phase 2: User Story 1, reading the account (P1, MVP)

- [X] T003 [US1] Rewrite the badge tests in `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` for the order and tones of R3 (Michael Scott viewing his own account, an active Pam, a deactivated Dwight, a deleted person, billable and self hosted), and add a test for `userEmailLine()` confirmed and unconfirmed
- [X] T004 [US1] Rewrite `badges()` per R3, returning `label`, `tone` and `icon`, and add `userEmailLine()` ("address · confirmed" or "· not confirmed"), in `app/ViewModels/Admin/AccountViewModel.php`
- [X] T005 [US1] Rewrite the top of `resources/views/app/admin/accounts/show.blade.php` inside a `max-w-240` column: `x-breadcrumb`, `x-avatar-initials` at 48, `h1`, mono address, badges with icons, the record `x-button.secondary` with a `timeline` icon, the deletion notice, the own account `x-notice tone="info"` (R1, R2, R4)
- [X] T006 [US1] In the same file, the two flush `x-panel`s "The account" and "The person" side by side with `x-detail-list layout="stacked" label-style="caption"` and the facts of FR-005, then the billing panel unchanged; remove the "What has been done to this account" panel (R5, R9)
- [X] T007 [US1] Update `tests/Feature/Controllers/App/Admin/AccountControllerTest.php` so the support email test asserts the trail to `admin.accounts.index` and the record link, and keep the deleted person and flags tests passing

## Phase 3: User Story 2, limits and access (P2)

- [X] T008 [US2] Add view model tests for `storageSharePercent()` (40 MB of 100 MB gives 40, over the limit gives 100, no limit gives null), `storageShareLine()` and `accessForGoodLine()` to `tests/Unit/ViewModels/Admin/AccountViewModelTest.php`
- [X] T009 [US2] Add `storageSharePercent()`, `storageShareLine()` and `accessForGoodLine()` (R6) to `app/ViewModels/Admin/AccountViewModel.php`
- [X] T010 [US2] Add "Limits and access" to `show.blade.php`: `x-page-section` with its line, a flush panel of Storage (line, over limit sentence, `role="img"` bar, "Change limit" trigger on the existing `x-modal.form`), Plan when billable, Lifetime access with the existing grant or withdraw `x-modal.warning` (R6)

## Phase 4: User Story 3, flags and deactivation (P3)

- [X] T011 [US3] Add a controller test to `AccountControllerTest.php`: on the viewer's own account "Deactivate account" carries `aria-disabled="true"` and an `aria-describedby` whose target holds the refusal sentence; on another person's account it does not
- [X] T012 [US3] Add the Flags `x-page-section` with its line and an `x-link` to `admin.flags.index` in the `actions` slot, and rows with "Switch on" or "Switch off" keeping the `sr-only` flag name, in `show.blade.php` (R7)
- [X] T013 [US3] Add the deactivation `x-panel tone="danger"` (or the reactivation panel for a deactivated person) with the existing modals, the refused button and its described reason for the viewer, and the `@error('activation')` notice, in `show.blade.php` (R8)

## Phase 5: Polish

- [X] T014 Run `php artisan monica:localize`, translate every reported wording in every locale following `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T015 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, fix every finding
- [X] T016 Run the admin and component tests, then the full suite with `php -d memory_limit=-1 vendor/bin/phpunit`
- [X] T017 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff, check their evidence, fix what holds
- [ ] T018 Walk the by hand table in `quickstart.md` and report what was and was not checked

## Dependencies

- T001 and T002 before T006.
- Inside a story: tests, then view model, then view. Every story edits `show.blade.php`, so stories run in order.
- Polish after every story.
