# Implementation Plan: Flag usage metrics

**Branch**: `claude/admin-flags-metrics-edb65a` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

## Summary

The Flags screen and `monica:list-flags` show, for each flag, how many accounts it is on for out of
all accounts, counted from Pennant's stored answers in one query per flag. Every request, job and
command names in its Laravel context, under `flags`, the flags it asked for an account that answered
on, which Nightwatch records next to the user it served. Monica stores no usage. Decisions and their
reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Laravel Pennant 1.26 (`FeatureRetrieved`), Laravel Context, Laravel
Nightwatch 1.30.6 (records `Context::all()`)

**Storage**: the existing `features` table, read only; no migration

**Testing**: PHPUnit

**Project Type**: web application with a JSON API and an MCP server

**Performance Goals**: Flags screen under two seconds at 100,000 accounts (SC-004); the listener adds
an `instanceof` and an array lookup per flag check (SC-005)

**Constraints**: every string through `monica:localize`; WCAG 2.2 AA on the touched screen

**Scale/Scope**: seven declared flags today; one screen, one command, one listener

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. No user action is added; the count is a read, shared by the screen and the command in `FlagState` (R3) |
| II. Simplicity | Pass. One listener, one helper method, one context callback; no table, no job |
| III. Tested | Pass. The count, the listener and the job boundary are tested (below) |
| IV. Laravel conventions | Pass. Pennant's own event and Laravel Context, which is what Nightwatch documents |
| V. Localized | Pass. One counted phrase through `trans_choice()` (R4) |
| VI. Nothing changes underfoot | Pass. No data is written or migrated |
| VIII. Nothing disappears silently | Pass. Nothing is removed |
| IX. Accessible | Pass. The figure is plain text on the existing row, read with the flag's name |
| X. Through the API | Deferred, as the spec records: the admin flag screens have no API today, and this adds no capability to an account |
| XI. Short record | Pass. `data-model.md`, `contracts/` and `quickstart.md` omitted: no new storage, and this plan carries the validation |

No violation to justify.

## Changes

| File | Change |
| --- | --- |
| `app/Listeners/NameFlagInContext.php` | Handle `FeatureRetrieved`: return unless the scope is an `Account` and the value is not `false`, then push the name onto `flags` unless already there (R1, R2) |
| `app/Providers/AppServiceProvider.php` | In `configureFlags()`: `Context::dehydrating()` forgets `flags`, so a queued job starts without its request's flags (R2) |
| `app/Helpers/FlagState.php` | `accountsOn(string $flag): int`, the formula of R3 |
| `app/ViewModels/Admin/FlagsViewModel.php` | Each row gains `reach`, the phrase of R4; `@return` shape updated |
| `resources/views/app/admin/flags/index.blade.php` | Print `reach` under the flag's name, in the row's existing secondary text style |
| `app/Console/Commands/ListFlagsCommand.php` | Append `, on for {$on} of {$total} accounts` to each line; update the description |
| `lang/*.json` | `monica:localize` adds the one phrase |

Listeners in `app/Listeners` are discovered by Laravel, as `RecordEmailSent` is, so no registration.
No API, MCP tool, help centre page or What's new entry: the change is visible to the instance
administrator only.

## Tests

| Test | Proves |
| --- | --- |
| `tests/Unit/Helpers/FlagStateTest.php` | Off for every account and on for Jim and Pam: 2. Then on for every account: all. On for every account, off for Dwight: all but one. An account never asked counts with the instance answer. A stored row for a deleted account is not counted |
| `tests/Unit/Listeners/NameFlagInContextTest.php` | Asking a flag that is on for Jim names it once, however often asked. A flag off for Dwight, and a check for nobody, name nothing |
| `tests/Unit/Providers/` or the listener test | A job queued after Jim's check carries no `flags` in its payload's context, while the request's context keeps it |
| `tests/Unit/ViewModels/Admin/FlagsViewModelTest.php` | The row carries "On for 2 of 5 accounts" |
| `tests/Unit/Console/Commands/ListFlagsCommandTest.php` | Each line carries the same count |

The whole suite runs before the commit: the listener runs on every flag check in every test.

## Validation (quickstart)

1. `php artisan test --compact` on the files above, then the whole suite.
2. `php artisan monica:switch-flag vcard-export on --email=jim@dundermifflin.com`, then
   `php artisan monica:list-flags`: `vcard-export` reads on for 1 of N accounts; the Flags screen
   agrees.
3. With `NIGHTWATCH_ENABLED=true` on a deployment, sign in as Jim and export a card. The request in
   Nightwatch shows `"flags": ["vcard-export"]` in its context and Jim's user id; a request by an
   account with the flag off shows no `flags`.
4. In Nightwatch, try to filter requests by that context value. Record whether it works: SC-003
   rests on it (R5).

## Project Structure

```text
specs/135-flag-usage-metrics/
├── spec.md
├── research.md
├── plan.md
└── checklists/requirements.md
```
