# Implementation Plan: Authorization Policies and Gates

**Branch**: `2026-09-12-authorization-policies` | **Date**: 2026-09-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/028-authorization-policies/spec.md`

## Summary

Give three duplicated permission rules one home each, as policy classes under `app/Policies`, and have
both the action and the view model ask that home instead of holding a copy. Scope narrowed from four
capabilities to three: the frozen template version already has a single source and its screen already
honours it, so it leaves the plan. See [research.md](research.md) section 1.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.8, Blade, Tailwind CSS, Alpine.js. Nothing added.

**Storage**: N/A. No new data, no migration, no schema change.

**Testing**: PHPUnit, `RefreshDatabase`, run across SQLite, MySQL and PostgreSQL.

**Target Platform**: Server rendered web application, hosted and self hosted.

**Project Type**: Web application, existing.

**Performance Goals**: No list asks a policy per row in this scope, so no N+1 surface is introduced.

**Constraints**: Pint clean, PHPStan level 5 clean, all 31 locales translated via `monica:localize`.

**Scale/Scope**: 3 policy methods, 2 policy classes, 3 actions touched, 3 view models touched.

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1.*

| Principle | Verdict | Note |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | The action keeps the refusal; controllers gain nothing. |
| II. Simplicity over abstraction | Pass, after narrowing | Dropping the frozen version removed the one item that was abstraction without a defect. |
| III. Tested before merged | Pass | Each policy method gets a permitted and a refused test; existing action tests keep asserting the same exception types. |
| IV. Laravel conventions | Pass | Policies discovered by convention, no manual registration. |
| V. Localized and safe | Attention | Unifying the two ceiling wordings changes shipped copy in 31 locales. |
| VI to IX | Not engaged | No versioning, no personalisation, no silent loss. Screens keep the presentation they already have. |
| X. Everything through the API | Pass | The API refuses by the same policy. It reports nothing in advance, per the clarification. |
| XI. The written record is short | Pass | plan 100, research 80. `data-model.md` and `contracts/` omitted, see below. |
| Fixed `app/` tree | Pass | `app/Policies` was admitted by the 2.3.0 amendment, merged. See Complexity Tracking. |

## Project Structure

### Documentation (this feature)

```text
specs/028-authorization-policies/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2, not created here
```

`data-model.md` is omitted: the feature stores nothing and changes no schema, so it would hold only the
sentence already in Technical Context. `contracts/` is omitted: no endpoint, route or payload changes,
because the clarification chose not to report permitted capabilities through the API. Constitution XI
requires both omissions rather than permitting them.

### Source Code (repository root)

```text
app/
├── Policies/                       # Admitted by the 2.3.0 amendment
│   ├── VaultPolicy.php             # create()
│   └── ContactPolicy.php           # create(), delete()
├── Actions/
│   ├── CreateVault.php             # refuseAboveTheCeiling() asks the policy
│   ├── CreateContact.php           # same
│   └── DestroyContact.php          # validate() asks the policy
└── ViewModels/
    ├── Vaults/VaultFormViewModel.php    # ceilingMessage() asks the policy
    ├── Vaults/ContactFormViewModel.php  # same
    └── Vaults/ContactEditViewModel.php  # mayBeDeleted() asks the policy

tests/Unit/Policies/                # New, mirrors app/Policies
```

**Structure Decision**: policies live beside the models they govern, named so Laravel discovers them
without registration. The rule moves into the policy; the action and the view model become callers. No
controller changes, which is what keeps the web and API surfaces from drifting.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| `app/Policies`, a folder that was outside the constitution's fixed `app/` tree | The clarification chose per record policy classes, and Laravel discovers them only at that path | Gates in `AppServiceProvider` needed no amendment and were offered, but were not the option chosen |

**Discharged.** The amendment merged as version 2.3.0, so `app/Policies` is an ordinary folder of the
tree and nothing here blocks implementation. Work starts at T002.
