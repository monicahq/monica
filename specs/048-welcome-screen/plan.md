# Implementation Plan: Welcome screen

**Branch**: `2026-09-24-welcome-screen` | **Date**: 2026-09-24 | **Spec**: [spec.md](spec.md)

## Summary

`users` gains `welcomed_at`. `HomeController` sends a user without it to a new `welcome` screen,
drawn after direction 1a, whose **Let me in** calls `MarkUserAsWelcomed` and returns to the home
address. The API exposes the moment on the profile and marks it through `PUT me/welcome`. Decisions
are in [research.md](research.md), which also carries the column (R2) and the routes (R5), so there
is no `data-model.md` and no `contracts/`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, existing components; no JavaScript

**Storage**: one nullable datetime on `users`, in its create migration

**Testing**: PHPUnit: one action, one view model, two web controllers, one API controller

**Target Platform**: web application, outside any vault; JSON API v1

**Project Type**: server rendered web application

**Performance Goals**: the detour reads a column of the user already loaded; no query added

**Constraints**: WCAG 2.2 AA; every string in every locale; portable SQL on the three drivers

**Scale/Scope**: 1 action, 2 controllers added and 1 edited, 1 view model, 1 view, 1 resource field,
3 routes, 1 docs entry

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | `MarkUserAsWelcomed`, called by both controllers |
| II. Simplicity over abstraction | Pass | One column, one `if` in `HomeController`, no middleware |
| III. Tested before merged | Pass | One layer each, see Source Code |
| IV. Laravel conventions | Pass | `up()` only, edited create migration, one named route per line |
| V. Localized and safe | Pass | Strings through `monica:localize`; nothing destructive |
| VI, VII, VIII | Not touched | No structure, no core concept, nothing removed |
| IX. Accessible to everybody | Pass | One `h1`, a real form button with `focus-ring`, fluid width |
| X. Everything goes through the API | Pass | `welcomed_at` on `UserResource`, `PUT me/welcome`, documented |
| XI. The written record is short | Pass | spec 120, plan under 100, research under 80 |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/048-welcome-screen/
├── plan.md  research.md  quickstart.md
└── tasks.md                                          # /speckit-tasks
```

### Source Code (repository root)

```text
database/migrations/0001_01_01_000000_create_users_table.php   # welcomed_at
database/factories/UserFactory.php                    # welcomed_at now, notWelcomed()
app/Models/User.php                                   # cast, docblock
app/Actions/MarkUserAsWelcomed.php
app/Http/Controllers/App/HomeController.php           # the detour
app/Http/Controllers/App/WelcomeController.php        # index, update
app/Http/Controllers/Api/MeWelcomeController.php      # update
app/Http/Resources/UserResource.php                   # welcomed_at
app/ViewModels/Welcome/WelcomeViewModel.php
resources/views/app/welcome/index.blade.php
resources/docs/api/03-profile.php                     # the endpoint and the field
routes/web.php  routes/api.php
tests/Unit/Actions/MarkUserAsWelcomedTest.php
tests/Unit/ViewModels/Welcome/WelcomeViewModelTest.php
tests/Feature/Controllers/App/WelcomeControllerTest.php
tests/Feature/Controllers/App/HomeControllerTest.php  # the detour
tests/Feature/Controllers/Api/MeWelcomeControllerTest.php
tests/Unit/Actions/CreateAccountTest.php              # a new account starts not welcomed
lang/*.json                                           # through monica:localize
```

**Structure Decision**: the existing layout. The screen sits beside getting started: a controller
under `App`, a view model under its own folder, a view under `app/`.

## Complexity Tracking

None.
