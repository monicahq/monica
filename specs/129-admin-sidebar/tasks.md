# Tasks: Admin space sidebar

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution III; written in `tests/Feature/Views/Layouts/AdminLayoutTest.php` before the markup they prove.

## Phase 1: Foundational (the shared drawer, research R2)

- [X] T001 Make `leaveUrl` and `leaveLabel` optional in `resources/views/components/nav-drawer.blade.php`: without them, draw neither `x-product-link` nor the leave link, and show the top row (close button only) below `lg` alone
- [X] T002 Make a group `title` optional in `resources/views/components/nav-drawer.blade.php`: no `h2` when it is absent
- [X] T003 Let an entry carry `section: true` in `resources/views/components/nav-drawer.blade.php`, marking the current entry `aria-current="true"` instead of `"page"`, and update the `@var` block
- [X] T004 Run the settings and support feature tests to prove their drawers render as before

## Phase 2: User Story 1, sidebar on a wide screen (P1)

- [X] T005 [US1] Rewrite `resources/views/layouts/admin.blade.php`: the dark bar across the top, then a full-height `lg:grid-cols-[248px_minmax(0,1fr)]` grid holding `x-nav-drawer` (one untitled group, label "Admin sections", no leave link) and a scrolling content column with the warning band and the slot; remove the row of links
- [X] T006 [US1] Build the entries in `resources/views/layouts/admin.blade.php` from the existing list, same order, icons and availability conditions, with `current` set from `request()->routeIs()` and `section: true` when only the section matches
- [X] T007 [US1] Run `AdminLayoutTest`: its five existing tests (landmark, one current page, current section, no vault link, support switch) must pass unchanged

## Phase 3: User Story 2, the drawer on a phone (P1)

- [X] T008 [US2] Test in `AdminLayoutTest` that the admin bar holds a button named "Open the menu" with `aria-controls="main-nav"` and an `aria-expanded` binding
- [X] T009 [US2] Add to `resources/views/layouts/admin.blade.php` the `x-data="{ navOpen: false }"` wrapper, the menu button in the bar (`lg:hidden`, `x-ref`, dark bar focus ring), the shell's scrim, and the page lock below `lg`
- [X] T010 [US2] Close on Escape and on the scrim and return focus to the menu button in `resources/views/layouts/admin.blade.php`
- [X] T011 [US2] Keep the bar on one line at 375px in `resources/views/layouts/admin.blade.php`: hide the Instance badge and the leave label below `sm` as today, keep every control reachable

## Phase 4: User Story 3, waiting counts (P2)

- [X] T012 [US3] Test in `AdminLayoutTest` that a changelog comment to review and a support request to handle are each announced with their wording, that nothing is announced at zero, and that the menu button carries the waiting sentence only when something waits
- [X] T013 [US3] Pass `count` and `countLabel` for Support and Changelog into the entries in `resources/views/layouts/admin.blade.php`, keeping the existing `trans_choice()` wordings
- [X] T014 [US3] Add the waiting dot (`aria-hidden`) and an `sr-only` sentence to the menu button in `resources/views/layouts/admin.blade.php` when either count is above zero
- [X] T015 [US3] Run `php artisan monica:localize` and translate the new sentence in every locale through sonnet subagents, per the translations skill

## Phase 5: Polish

- [X] T016 Run `PAO_DISABLE=true php artisan test --parallel --compact`
- [X] T017 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check`
- [X] T018 [P] Run the `accessibility-reviewer` agent on the two changed views and fix what it confirms
- [X] T019 [P] Run the `rules-reviewer` agent on the change and fix what it confirms
- [X] T020 Decide the changelog entry per the `changelog` skill (administrators only see this change) and write it in `changelog/` if it earns one
- [ ] T021 Hand the quickstart's by-hand table to the user: the look and the drawer are checked by looking, not by a test

## Dependencies

- T001 to T003 block every story; T004 closes the phase.
- US1 (T005 to T007) blocks US2 and US3, which edit the same layout.
- US2 and US3 both touch `layouts/admin.blade.php`, so they run in sequence; T014 needs T009.
- T018 and T019 run in parallel after T017.

## Strategy

US1 alone is shippable on wide screens but not on a phone, since it removes the row without the drawer. Ship US1 and US2 together as the minimum, US3 with them since the counts exist today.
