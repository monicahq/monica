# Implementation Plan: Filters on the admin accounts list

**Branch**: `2026-11-19-admin-accounts-filters` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

## Summary

Four filters on the admin Accounts screen (state, access, joined, flag), read from the query string
by the existing view model and applied as `where` clauses on the existing paginated query. Nothing is
stored. The flag rule moves into one query that the Flags screen counts with too. Decisions are in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Laravel Pennant (`features` table), Laravel Cashier Stripe (`subscriptions`)

**Storage**: SQLite, MySQL/MariaDB, PostgreSQL; no migration

**Testing**: PHPUnit; the CI matrix runs all three databases, which matters for R4

**Project Type**: server rendered web application (Blade, Alpine)

**Performance Goals**: a filtered page opens as fast as today's unfiltered one at 100,000 accounts
(SC-004, R7)

**Constraints**: every string through `monica:localize`; WCAG 2.2 AA on the touched screen

**Scale/Scope**: one screen, one view model, one helper, one model scope

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. A read; no action is added |
| II. Simplicity | Pass. No new class; the filters live in the view model that already holds the search (R1) |
| III. Tested | Pass. See Tests |
| IV. Laravel conventions | Pass. Local scope, `whereHas`, `whereExists`; one driver switch with a precedent (R4) |
| V. Localized | Pass. Labels through `__()`; the empty state is a sentence, not a counted phrase |
| VI. Nothing changes underfoot | Pass. No data written; no filter gives today's list (SC-003) |
| VIII. Nothing disappears silently | Pass. Nothing removed |
| IX. Accessible | Pass. Labelled selects in a `role="search"` form, as the support queue |
| X. Through the API | Deferred: the admin accounts list has no API today, and this adds no capability to an account |
| XI. Short record | Pass. `data-model.md`, `contracts/`, `quickstart.md` omitted: no storage, no new interface beyond the query string below, and the validation is under Tests |
| Product invariant, same product hosted and self hosted | Pass. The access filter is an instance billing control, hidden where billing is, as the account page does (R6) |

## Query string

`query`, `size` and `page` as today, plus `state` (`active`, `deactivated`, `deleted`, `nobody`),
`access` (`free`, `subscription`, `lifetime`, `granted`; hosted only), `joined` (`last-7-days`, `last-30-days`, `last-12-months`)
and `flag` (a name from `Feature::defined()`). Any other value is dropped.

## Changes

| File | Change |
| --- | --- |
| `app/Helpers/FlagState.php` | `whereOn(Builder $accounts, string $flag): Builder` (R4); `accountsOn()` unchanged |
| `app/Models/Account.php` | `scopeWhereAccessKind(Builder $query, ?AccessKindEnum $kind)`, null meaning the free plan (R3) |
| `app/ViewModels/Admin/AccountsViewModel.php` | Constructor takes `state`, `access`, `joined`, `flag` and drops unoffered values; `matching()` applies them (R2, R5, R6); `stateOptions()`, `accessOptions()` (empty when self hosted), `joinedOptions()`, `flagOptions()`; `hasFilters()`, true when a filter is chosen; `isSearching()` keeps meaning the search box |
| `app/Http/Controllers/App/Admin/AccountController.php` | `index` passes the four strings with `$request->string(...)` |
| `resources/views/app/admin/accounts/index.blade.php` | Selects beside the search box, `role="search"` and a label on the form, `Filter` and `Clear filters` as on the support screen; `Clear filters` and the filtered empty state, which names no `:query`, follow `hasFilters()` (FR-007, FR-008) |
| `lang/*.json` | `monica:localize` adds the new labels |

## Tests

| File | Proves |
| --- | --- |
| `tests/Unit/Helpers/FlagStateTest.php` | `whereOn` for: on for nobody's scope with one account off, off with two accounts on, an account never asked; `whereOn(...)->count()` equals `accountsOn()` in each (SC-005) |
| `tests/Unit/Models/AccountTest.php` | `accessKind` scope returns each kind once, in the precedence of R3, matching `accessKind()` |
| `tests/Unit/ViewModels/Admin/AccountsViewModelTest.php` | Each state; joined periods; two filters combined with the search; an unknown value ignored; access ignored when self hosted |
| `tests/Feature/Controllers/App/Admin/AccountControllerTest.php` | The four parameters reach the list; the filtered empty state copy |

The access and flag tests run on all three databases in CI, which is where R4's concatenation is
checked.

## Out of scope

A link from the Flags screen to a filtered list; filters on storage or last sign in; sorting.
