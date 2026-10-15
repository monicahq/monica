# Tasks: Sign out from the main page

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by the constitution (III), one component test file and three layout test files.

## Phase 1: Setup

- [X] T001 Cut the branch `2026-10-13-sign-out-main-page` from an up to date `main`

## Phase 2: Foundational

- [X] T002 Create `resources/views/components/account-menu.blade.php` per the outline in plan.md: `x-actions-menu` (variant `ghost`, label `__('Account')`) with `<x-avatar :size="26">` as its glyph, an `x-actions-menu.item` to `route('settings.index')`, and a `<form>` posting to `route('auth.login.destroy')` with `@csrf` and a `<button type="submit" role="menuitem">` (research R3)
- [X] T003 [P] Create `tests/Feature/Views/Components/AccountMenuTest.php` proving the contract in research R7. No assertion names a class or an icon

## Phase 3: User Story 1, sign out from the list of vaults (P1) MVP

**Independent test**: `<x-account-layout>` shows the initials and the menu, and no email address.

- [X] T004 [US1] In `resources/views/layouts/account.blade.php`, add `<x-account-menu :user="auth()->user()" />` after `<x-theme-toggle />` and pass `:settings="false"` to `<x-bar-links>` (research R1)
- [X] T005 [US1] In `tests/Feature/Views/Layouts/TopBarTest.php`, rewrite the test that the bar names nobody into `the_bar_of_a_screen_outside_a_vault_shows_who_is_signed_in_but_not_their_email_address`, and add `the_way_out_sits_in_the_bar_only_where_no_navigation_column_carries_it` (FR-004)

## Phase 4: User Story 2, sign out with the keyboard or a screen reader (P1)

**Independent test**: Tab reaches the avatar, Enter opens the menu, Escape closes it, Sign out in it signs you out.

- [X] T006 [US2] Check that `account-menu.blade.php` adds nothing to the contract `x-actions-menu` already carries and `ActionsMenuTest` proves: `role="menuitem"` on both entries, a real submit button, `focus-ring-inset` written by name

## Phase 5: User Story 4, sign out from the marketplace and the admin space (P2)

**Independent test**: the marketplace shows the menu signed in and not to a visitor; the admin bar shows it.

- [X] T007 [P] [US4] In `resources/views/layouts/marketplace.blade.php`, replace `<x-avatar>` inside `@auth` with `<x-account-menu :user="auth()->user()" />` (FR-009)
- [X] T008 [P] [US4] In `resources/views/layouts/admin.blade.php`, replace `<x-avatar>` with `<x-account-menu :user="auth()->user()" />` (FR-010)
- [X] T009 [P] [US4] In `tests/Feature/Views/Layouts/MarketplaceLayoutTest.php`, add `it_offers_a_signed_in_person_the_way_out` and `it_offers_a_visitor_no_way_out`
- [X] T010 [P] [US4] In `tests/Feature/Views/Layouts/AdminLayoutTest.php`, add `it_offers_a_way_out`

## Phase 6: User Story 3, sign out from the other screens outside a vault (P3)

- [X] T011 [US3] Open the form that creates a vault and confirm the avatar menu is there. No test: `TopBarTest` already proves the layout

## Phase 7: Polish

- [X] T012 Run the test command in quickstart.md, then the whole suite with `APP_TRUSTED_PROXIES= php artisan test --compact --parallel`
- [X] T013 Run `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T014 Run the "By eye" section of quickstart.md on all four screens through the take-screenshot skill. Open: the admin bar overflows at 390 px, so its avatar leaves the screen
- [ ] T015 Open the menu in a browser on each bar and confirm it opens, closes with Escape, and is drawn above the page

## Dependencies

- T001 first. T002 before T004, T007 and T008. T003 runs in parallel with T002.
- US1 (T004, T005) is the shippable MVP. US4 and US3 depend on T002 only.
- T012 to T015 last.
