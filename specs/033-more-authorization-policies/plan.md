# Implementation Plan: More Authorization Policies

**Branch**: `033-more-authorization-policies` | **Date**: 2026-09-14 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/033-more-authorization-policies/spec.md`

## Summary

Give eleven more duplicated rules one home each, as policy methods, and have the screen and the work behind it ask that home instead of each keeping a copy. Eight policy classes are new, and the one that exists gains a method. Three refusals that today answer a request arriving by its address join the template sharing rule, which is the only one written three times. The inventory, with the line each copy sits on, is [research.md](research.md) section 1.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.31, Blade, Tailwind CSS, Alpine.js. Nothing added.

**Storage**: N/A. No new data, no migration, no schema change.

**Testing**: PHPUnit, `RefreshDatabase`, run across SQLite, MySQL and PostgreSQL.

**Target Platform**: Server rendered web application, hosted and self hosted.

**Performance Goals**: no new query. Every count these rules read is already a column on the record, so the screens that ask per row touch no database. See [research.md](research.md) section 4.

**Constraints**: Pint clean, PHPStan level 5 clean, all 31 locales translated via `monica:localize`.

**Scale/Scope**: 11 policy methods across 9 policy classes, 8 of them new; 11 actions, 6 view models, 3 controllers, 2 API controllers, 8 Blade templates.

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1.*

| Principle | Verdict | Note |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | The work keeps the refusal. Controllers gain nothing; the three that lose a guard hand it to the same ability. |
| II. Simplicity over abstraction | Attention | Eleven at once is the largest slice this pattern has taken. Each is an existing duplication, not a new layer, and the four stories are independently shippable. |
| III. Tested before merged | Pass | Each method gets a permitted and a refused case, which `PolicyCoverageTest` already enforces for anything under `app/Policies`. |
| IV. Laravel conventions | Pass | Policies discovered by convention, no registration. |
| V. Localized and safe | Attention | Reasons must be written where a screen carries none today, so new strings reach all 31 locales. No existing wording changes. |
| VI. Nothing changes under anybody's feet | Pass | Every refusal keeps the response it gives today, including the three that answer as if the thing were not there. |
| VII. Stable core, unlimited personalisation | Pass | The rules governed are the product's own, not anything a user invents. |
| VIII. Nothing disappears silently | Strengthened | This is the principle the feature serves: eight screens stop withholding a control without saying why. |
| IX. Accessible to everybody | Attention | Every control that becomes visible-but-unavailable must be announced as such and tied to its reason. This is the acceptance condition, not a finishing touch. |
| X. Everything through the API | Pass | Two endpoints catch a different refusal and answer identically. See [research.md](research.md) section 5. |
| XI. The written record is short | Pass | plan 100, research 80, quickstart 60. `data-model.md` and `contracts/` omitted, see below. |
| Fixed `app/` tree | Pass | `app/Policies` was admitted by the 2.3.0 amendment. |
| Mono-user invariant | Pass | No role, no membership, no permission between people. Deciding on a listing version belongs to the instance, never to anybody's vault. |

No gate fails. The three marked Attention are conditions on how the work is done, carried into `tasks.md` rather than exceptions needing justification, so Complexity Tracking is empty and omitted.

## Project Structure

### Documentation (this feature)

```text
specs/033-more-authorization-policies/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2, not created here
```

`data-model.md` is omitted: the feature stores nothing and changes no schema, so it would repeat one line of Technical Context. `contracts/` is omitted: no route, payload or status changes, because every refusal keeps the answer it gives today. Constitution XI requires both omissions rather than permitting them.

### Source Code (repository root)

```text
app/
├── Policies/
│   ├── RelationshipTypePolicy.php      # delete()                    (new)
│   ├── ValueListPolicy.php             # delete(), addItem()         (new)
│   ├── ValueListItemPolicy.php         # delete()                    (new)
│   ├── EntityTypePolicy.php            # delete()                    (new)
│   ├── TemplatePolicy.php              # share()                     (new)
│   ├── TemplateVersionPagePolicy.php   # delete()                    (new)
│   ├── UserPolicy.php                  # removePassword(), disconnectProvider() (new)
│   ├── MarketplaceListingVersionPolicy.php # decide()                (new)
│   └── ContactPolicy.php               # absorb() added; create() gains its context
├── Actions/                            # 11 files: the refusal becomes one line
├── ViewModels/                         # 6 files: the predicate asks the ability
└── Http/Controllers/
    ├── App/Settings/Account/Templates/ # 3 guards become the same ability
    └── Api/                            # 2 files catch the framework's refusal

resources/views/app/settings/          # 8 templates: the control stays, marked unavailable
tests/Unit/Policies/                   # 8 new files, mirroring app/Policies
```

**Structure Decision**: one policy per model, named so Laravel discovers it without registration, which is what 028 established. The rule moves into the policy; the screen and the work become callers. The three controller guards become callers too, asking the same ability and refusing as if the thing were not there, which is the only way the template sharing rule collapses from three statements to one.

## Delivery order

Each user story is independently shippable and none depends on another. P1 first, because it is five of the eleven on one screen family and proves the presentation rule FR-003 settles. P2 second, because it is the only one that also removes a controller copy. P3 and P4 are two capabilities each and can land in either order. FR-005, the rule 028 left split, rides with P4 since it touches the same policy class.
