# Tasks: What’s new for signed out visitors, and its missing logo

Plan: [plan.md](plan.md). Decisions: [research.md](research.md).

## Phase 1: User Story 1, the logo (P1)

- [x] T001 [US1] Replace `x-shell` by `x-account-layout` in resources/views/app/changelog/index.blade.php, keeping the title and breadcrumb

## Phase 2: User Story 2, visitors (P2)

- [x] T002 [US2] Move `GET changelog` out of the auth group with `changelog`, `active` and `searchable` middleware in routes/web.php
- [x] T003 [US2] Skip the unseen lookup and `MarkChangelogSeen` when nobody is signed in in app/Http/Controllers/App/ChangelogController.php
- [x] T004 [US2] Take `?User`, read no votes and no comment counts and hide the link for a visitor, expose `allowsFeedback()` in app/ViewModels/ChangelogViewModel.php
- [x] T005 [US2] Pass `feedback` to the partials and include `_feedback` only when true in resources/views/app/changelog/index.blade.php, _entry.blade.php and _fixes.blade.php
- [x] T006 [P] [US2] Add a "Sign in" link for a guest in resources/views/components/bar-links.blade.php
- [x] T007 [P] [US2] List `changelog.index` as public in tests/Feature/RouteProtectionTest.php
- [x] T008 [P] [US2] Prove a visitor sees the page, no vote, no comment control, and no seen row in tests/Feature/Controllers/App/ChangelogControllerTest.php
- [x] T009 [P] [US2] Prove the visitor rows carry no vote, no comment count and no link in tests/Unit/ViewModels/ChangelogViewModelTest.php

## Phase 3: Polish

- [x] T010 Write the What’s new entry following the changelog skill in changelog/
- [x] T011 Run Pint, PHPStan, the changelog and route protection tests, then the definition-of-done skill

## Dependencies

T001 first (both stories edit index.blade.php). T002 to T005 in order. T006 to T009 after T005. T010, T011 last.
