---
description: "Task list for the top bar of design 1a"
---

# Tasks: One top bar, outside and inside a vault

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/top-bar.md](contracts/top-bar.md), [quickstart.md](quickstart.md)

**Tests**: Required by Principle III. View tests assert names, routes, ARIA and presence, never classes or markup order (`.claude/rules/testing.md`).

## Phase 1: Foundational (blocks every story)

- [X] T001 [P] Redraw `journal` and `bell` and add `shop` and `shield` with the 1a paths (R3) in resources/views/components/icon.blade.php
- [X] T002 [P] Add a test that `shop` and `shield` each render differently from the default glyph in tests/Feature/Views/Components/IconTest.php
- [X] T003 [P] Make the trigger a 32 pixel square ghost button, the count `bg-primary text-on-primary` with a two pixel `ring-page`, and the glyph 16 pixels at a 1.4 stroke (R4) in resources/views/components/notification-bar.blade.php
- [X] T004 [P] Show the avatar at 28 pixels and a 14 pixel `chevron-down` in `text-muted`, and name the trigger "Your account" (R5) in resources/views/components/account-menu.blade.php
- [X] T005 [P] Change the expected label from "Account" to "Your account" in tests/Feature/Views/Components/AccountMenuTest.php

## Phase 2: User Story 1, the vault list shows the new bar (P1, MVP)

**Goal**: The vault list bar is design 1a. **Independent test**: open `/vaults` as a regular person and as an administrator.

- [X] T006 [US1] Rebuild the cluster in the order of [contracts/top-bar.md](contracts/top-bar.md): Docs and Marketplace as `x-button.ghost size="small"` with `journal` and `shop`, Admin space as `x-button.secondary size="small"` with `shield`, labels shown from `topbar-labels`, each keeping its `aria-label`, then the divider, `x-notification-bar`, `x-theme-toggle` and `x-account-menu`, the whole wrapper `shrink-0`, in resources/views/components/bar-links.blade.php
- [X] T007 [US1] Make the logo at 28 pixels and the application name one link to `route('vaults.index')`, and leave `x-bar-links :settings="false"` alone on the right, removing the separate theme switch and account menu, in resources/views/layouts/account.blade.php
- [X] T008 [US1] Add tests that the cluster renders the notification button, the theme switch and "Your account", and keeps the existing Admin space and marketplace flag tests passing, in tests/Feature/Views/Components/BarLinksTest.php
- [X] T009 [US1] Add a test that the account layout's application name links to the vault list in tests/Feature/Views/Layouts/AccountLayoutTest.php

**Checkpoint**: `/vaults` matches 1a. A vault screen now shows the account menu twice (bar and column) until Phase 3.

## Phase 3: User Story 2, the bar carries over into a vault (P1)

**Goal**: Same right side inside a vault and in settings; the columns lose their copies. **Independent test**: go from `/vaults` to a vault dashboard and a settings screen.

- [X] T010 [P] [US2] Remove `x-theme-toggle` from the head and `x-sidebar-foot` from the foot, keeping the close button at the right of the head with `ml-auto`, in resources/views/components/vault/sidebar.blade.php
- [X] T011 [P] [US2] Make the same two removals in resources/views/components/settings/sidebar.blade.php
- [X] T012 [US2] Delete resources/views/components/sidebar-foot.blade.php once T010 and T011 leave it without a caller (confirm with `grep -rn "x-sidebar-foot" resources`)
- [X] T013 [US2] Rewrite `both_columns_carry_who_is_signed_in_and_the_way_out` and `the_way_into_the_settings_and_the_way_out_are_kept_behind_the_name` into one test that neither column renders "Sign out", `auth.login.destroy` or the theme switch label, in tests/Feature/Views/Components/SidebarTest.php
- [X] T014 [US2] Rewrite `the_bar_of_a_screen_with_a_navigation_column_leaves_the_account_settings_to_it` and `the_way_out_sits_in_the_bar_only_where_no_navigation_column_carries_it` so both layouts render "Account settings" and "Sign out" in the bar, and rename `it_leaves_who_is_signed_in_to_the_foot_of_the_navigation_column` to say the bar never prints the email address, in tests/Feature/Views/Layouts/TopBarTest.php

**Checkpoint**: SC-001 and SC-002 hold on a vault dashboard and a settings screen.

## Phase 4: User Story 3, the bar on a narrow screen (P2)

**Goal**: Nothing scrolls sideways at 375 pixels. **Independent test**: the narrow rows of [quickstart.md](quickstart.md).

- [X] T015 [US3] Make the breadcrumb wrapper `min-w-0` so it gives way before the cluster in resources/views/layouts/shell.blade.php
- [X] T016 [US3] Screenshot `/vaults`, a vault dashboard and a contact page at 375 pixels with the `take-screenshot` skill; if a screen's `actions` slot overflows, record it here as a new task for that screen (R7)

## Phase 5: Polish

- [X] T017 Run `php artisan monica:localize`, translate "Your account" in every locale, then `php artisan monica:localize --check`
- [X] T018 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`
- [X] T019 Run the whole suite with `php artisan test --compact` (shared components, `.claude/rules/components.md`)
- [X] T020 [P] Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and fix what they confirm
- [X] T021 [P] Screenshot `/vaults`, a vault dashboard and a settings screen at 1280 pixels, light and dark, and compare them with design 1a and with each other (SC-001, SC-002)

## Dependencies

- Phase 1 before everything. T006 needs T001, T003 and T004.
- US2 needs US1: until T006 the shell has no account menu in its bar, so removing the column copies first would drop it from vault screens.
- US3 needs US2. Polish runs last.

## Parallel work

- T001 to T005 touch five different files.
- T010 and T011 touch the two columns separately.
- T020 and T021 are independent reads.

## Implementation strategy

Phases 1 and 2 are the MVP: the vault list matches 1a. Phase 3 is shipped in the same pull request,
because a vault screen with two account menus must not reach main.
