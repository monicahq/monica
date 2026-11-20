# Tasks: Filters on the admin accounts list

## Phase 1: Setup

- [X] T001 Update main, create branch `2026-11-19-admin-accounts-filters` from it, and carry specs/136-admin-accounts-filters/ over to it

## Phase 2: Foundational

- [X] T002 In app/ViewModels/Admin/AccountsViewModel.php add promoted `string $state = ''`, `string $access = ''`, `string $joined = ''`, `string $flag = ''` to the constructor (each later story narrows its own value), `hasFilters(): bool` (true when any of the four is not empty) and `clearFiltersUrl(): string` (`route('admin.accounts.index')`); keep `isSearching()` meaning the search box only
- [X] T003 In app/Http/Controllers/App/Admin/AccountController.php pass `state`, `access`, `joined` and `flag` to the view model with `(string) $request->string(...)`, as `query` is today
- [X] T004 In resources/views/app/admin/accounts/index.blade.php give the form `role="search"` and `aria-label="{{ __('Filter accounts') }}"`, keep the search box, show `Clear filters` with `x-button.ghost` when `isSearching() || hasFilters()`, and make the empty state say "Nothing matches these filters" without `:query` when `hasFilters()` (FR-007, FR-008), following resources/views/app/admin/support/index.blade.php

## Phase 3: User Story 1, filter by state (P1) 🎯 MVP

**Independent test**: with five accounts, one deactivated and one deleted, each state lists one row.

- [X] T005 [US1] In AccountsViewModel keep `$state` only when in `['active', 'deactivated', 'deleted', 'nobody']`; in `matching()` apply research.md R2 on `userIncludingDeleted` (`whereHas` with `is_active` and `whereNull('deleted_at')`, `onlyTrashed`, `doesntHave`); add `stateOptions()` returning `list<array{value: string, label: string}>` with "Any state" first
- [X] T006 [US1] Add the `state` `x-select size="small"` with label "State" beside the search box in resources/views/app/admin/accounts/index.blade.php
- [X] T007 [US1] Test in tests/Unit/ViewModels/Admin/AccountsViewModelTest.php: Toby deactivated, Angela deleted, one account with nobody on it, Jim and Pam active; each state returns its accounts; "active" combined with the search "jim" returns Jim; `state: 'sleeping'` returns all five

## Phase 4: User Story 2, filter by access (P2)

**Independent test**: Pam granted, Jim subscribed, Dwight free, Michael lifetime; each kind lists one row; self hosted shows no access filter.

- [X] T008 [P] [US2] Add `scopeWhereAccessKind(Builder $query, ?AccessKindEnum $kind): void` to app/Models/Account.php beside `accessKind()`: Lifetime is `whereHas('orders', paid)`; Granted is no paid order and `whereNotNull('access_granted_at')`; Subscription is neither and `whereHas('subscriptions', type default and active() or onTrial())`; null is none of the three (research.md R3)
- [X] T009 [P] [US2] Test in tests/Unit/Models/AccountTest.php: one account per kind, the scope returns each once, and for each account `Account::query()->accessKind($account->accessKind())` contains it
- [X] T010 [US2] In AccountsViewModel keep `$access` only when in `['free', 'subscription', 'lifetime', 'granted']` and `config('monica.self_hosted')` is false (R6); apply the scope in `matching()`, `free` mapping to null; add `accessOptions()`, empty when self hosted
- [X] T011 [US2] Add the `access` select, rendered only when `accessOptions()` is not empty, in resources/views/app/admin/accounts/index.blade.php
- [X] T012 [US2] Test in tests/Unit/ViewModels/Admin/AccountsViewModelTest.php: `access: 'granted'` returns Pam; with `monica.self_hosted` true it returns all four and `accessOptions()` is empty

## Phase 5: User Story 3, filter by flag (P2)

**Independent test**: `groups` off for every account but on for Jim and Pam lists exactly those two.

- [X] T013 [P] [US3] Add `public static function whereOn(Builder $accounts, string $flag): Builder` to app/Helpers/FlagState.php per research.md R4: prefix from `Feature::serializeScope(new Account)` up to and including `|`; the scope built as `CONCAT(?, accounts.id)` on `mysql`/`mariadb` and `? || accounts.id` otherwise; `whereNotExists` a `false` row when `Feature::for(null)->active($flag)`, else `whereExists` a `true` row; leave `accountsOn()` unchanged
- [X] T014 [P] [US3] Test in tests/Unit/Helpers/FlagStateTest.php with five accounts: off for every account and on for Jim and Pam returns those two; on for every account and off for Dwight returns four; an account never asked follows the instance answer; in each case `whereOn(Account::query(), $flag)->count()` equals `accountsOn($flag)` (SC-005)
- [X] T015 [US3] In AccountsViewModel keep `$flag` only when in `Feature::defined()`, apply `FlagState::whereOn()` in `matching()`, add `flagOptions()` with "Any flag" first and each flag's name as its label
- [X] T016 [US3] Add the `flag` select in resources/views/app/admin/accounts/index.blade.php
- [X] T017 [US3] Test in tests/Unit/ViewModels/Admin/AccountsViewModelTest.php: `flag: 'groups'` with Jim and Pam switched on returns them; `flag: 'removed-flag'` returns every account

## Phase 6: User Story 4, filter by joined period (P3)

**Independent test**: accounts created today, ten days ago and a year ago; "Last 7 days" lists one, "Last 30 days" two.

- [X] T018 [US4] In AccountsViewModel keep `$joined` only when in `['last-7-days', 'last-30-days', 'last-12-months']`, apply `where('created_at', '>=', now()->subDays(7) | subDays(30) | subMonths(12))` in `matching()` (R5), add `joinedOptions()` with "Any time" first
- [X] T019 [US4] Add the `joined` select in resources/views/app/admin/accounts/index.blade.php
- [X] T020 [US4] Test in tests/Unit/ViewModels/Admin/AccountsViewModelTest.php with `travelTo`: 7d returns one, 30d two, 12m two

## Phase 7: Polish

- [X] T021 Test in tests/Feature/Controllers/App/Admin/AccountControllerTest.php: `?state=deactivated&flag=groups` reaches the list (one row), a filtered miss shows "Nothing matches these filters", and a page 2 link keeps the filters (FR-006)
- [X] T022 Run `php artisan monica:localize` and translate the new labels into every lang/*.json through the translations skill, with sonnet subagents, then `php artisan monica:localize --check`
- [X] T023 Run `vendor/bin/pint --dirty --format agent`, PHPStan on the touched files, and `PAO_DISABLE=1 php artisan test --parallel`
- [X] T024 Run the accessibility-reviewer and rules-reviewer agents on the change, then the definition-of-done skill, which decides the changelog entry

## Dependencies

T001 → T002–T004 → each story. Stories are independent of each other but share AccountsViewModel and
the Blade view, so their tasks on those two files run one story at a time. T021–T024 come last.

## Parallel

- T008, T009, T013 and T014 touch their own files and can run while US1 is in progress.

## Strategy

US1 alone is shippable: state answers the moderation questions. Then US3 (the flag reach the Flags
screen counts), US2, US4.
