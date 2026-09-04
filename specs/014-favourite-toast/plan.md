# Implementation Plan: Confirmation when a contact is marked a favourite

**Feature**: `014-favourite-toast` | **Date**: 2026-09-02 | **Spec**: [spec.md](spec.md)

## Summary

Flash a status message from `ContactFavouriteController` on both branches of the favourite update, so
the existing toaster says what the star just did. No new component, no schema change, no new route.
The whole feature is a controller change, its copy, its translations, and the tests that pin it.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: Blade, Tailwind, Alpine.js, Alpine Ajax. All already present.

**Storage**: None. `contacts.is_favourite` already exists and is untouched.

**Testing**: PHPUnit. `tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php`.

**Target Platform**: Server rendered web, desktop, tablet and phone.

**Project Type**: Web application, single Laravel codebase.

**Performance Goals**: None beyond the existing request. The message rides the response already sent.

**Constraints**: Suite passes on SQLite, MySQL and PostgreSQL. Pint clean, PHPStan level 5 clean.

**Scale/Scope**: One controller, one view comment, two copy strings and their descriptions, nine
locales, one test file.

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1. No violations, so Complexity Tracking is omitted.*

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | No logic added. `FavouriteContact` and `UnfavouriteContact` are untouched; flashing a message is transport and belongs in the controller. |
| II. Simplicity | Two `->with()` calls on redirects that already exist. Nothing extracted. |
| III. Tested before merged | Two existing tests assert the opposite and are rewritten; new cases cover both directions and the failure path. |
| IV. Laravel conventions | Session flash plus redirect, the pattern all 78 other flashes in the app use. |
| V. Localized | Copy through `__()`, produced by `php artisan monica:localize`, translated in all nine locales. Not a destructive action, so no warning is owed. |
| VI. Nothing changes underfoot | The request still sends the state it wants rather than a toggle. Untouched. |
| VIII. Nothing disappears silently | This feature is that principle applied to a save that was silent. |
| IX. Accessible | The toaster is already an `aria-live="polite"` region that does not take focus. The message adds text to it and no new control. |
| X. Everything through the API | A toast has no API counterpart. The favourite capability has no API endpoint today, which is pre-existing debt this change neither adds to nor pays down: the logic stays in the two actions, so the endpoint adds only transport when it arrives. |
| XI. The record is short | `spec.md` 120 lines, this plan under 100, `research.md` under 80. `data-model.md` and `contracts/` are omitted, see below. |

## Project Structure

### Documentation (this feature)

```text
specs/014-favourite-toast/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

`data-model.md` is omitted: no entity, column, migration or factory changes. `contracts/` is omitted:
no interface is added or altered, the route and its payload are unchanged, and the only new surface
is copy, which `research.md` carries.

### Source code

```text
app/Http/Controllers/App/Vaults/ContactFavouriteController.php   # flash on both branches, rewrite the docblock
resources/views/app/vaults/contacts/show.blade.php               # correct the comment that says the star is the only answer
lang/*.json                                                      # nine locales, written by monica:localize
tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
```

**Structure Decision**: The existing layout absorbs this feature entirely. No new file is created in
`app/`, and the toaster component and its `x-sync` wiring are not touched.

## Phase 0

[research.md](research.md): why the recorded decision is reversed, why both directions are confirmed,
why nothing new is needed for the partial update and the no-scripting path, and the copy.

## Phase 1

[quickstart.md](quickstart.md): how to see it work and which tests prove it.
