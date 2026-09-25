# Implementation Plan: Entity list and creation form

**Branch**: `2026-09-25-entity-list-and-creation` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

One action, `CreateEntity`, writes the record and every section's values in one transaction, the
way `CreateLifeEvent` does (R1). A new policy ability, `addEntity`, decides whether the list's Add
control is enabled and whether the action may run (R2). The form is a new screen drawn from the
vault default's published version with the record page's controls (R4, R6). An API controller lists
and creates records through the same action (R7). No schema changes, so there is no `data-model.md`,
and the tests table below is the validation guide, so there is no `quickstart.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js (only what `x-field-repetitions` brings)

**Storage**: writes `entities`, `field_values`, `field_value_rows`, `files`; increments
`structure_versions.record_count` and `entity_types.entity_count`

**Testing**: PHPUnit: action, policy, view models, web and API controllers, the docs test

**Target Platform**: the entity list and one new screen; two API endpoints

**Project Type**: server rendered web application with a JSON API

**Performance Goals**: the form loads the version's assembly in one eager query, as the record page does

**Constraints**: WCAG 2.2 AA; every string in every locale; nothing specific to a type

**Scale/Scope**: 1 action, 1 exception, 1 policy ability, 2 view models (1 new), 2 web routes, 2 API
routes, 1 API resource, 2 views (1 new), 1 enum case, about 6 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | `CreateEntity`; controllers validate and translate refusals |
| II. Simplicity over abstraction | Pass | Reuses `SetFieldValues`, `FieldValueRules`, `x-field-input`; no new component |
| III. Tested before merged | Pass | Tests table below |
| IV. Laravel conventions | Pass | Policy ability, one named route per line, inline validation |
| V. Localized and safe | Pass | About six strings; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | A record is bound to the version it was drawn from; a changed default refuses (R3) |
| VII. Stable core | Pass | Entities stay built on templates |
| VIII. Nothing disappears silently | Pass | A refused Add stays on screen with its reason |
| IX. Accessible to everybody | Pass | Disabled control with `aria-describedby`; errors beside their field; fields keep `for`/`id` |
| X. Everything goes through the API | Pass | List and create endpoints, documented; closes 052's deviation |
| XI. The written record is short | Pass | spec 118, plan under 100, research 68, contract 48 |

Re-check after Phase 1: unchanged.

## Project Structure

```text
app/Actions/CreateEntity.php                                    # new (R1, R3)
app/Exceptions/EntityTemplateChanged.php                        # new (R3)
app/Policies/EntityTypePolicy.php                               # addEntity (R2)
app/Models/Field.php                                            # canBeFilledIn (R4)
app/Models/EntityType.php                                       # defaultVersionIn (R2)
routes/web.php                                                  # entities.new, entities.create
app/Http/Controllers/App/Vaults/EntityController.php           # new, create
app/ViewModels/Vaults/EntitiesViewModel.php                    # addUrl, addRefusal
app/ViewModels/Vaults/EntityFormViewModel.php                  # new (R4)
resources/views/app/vaults/entities/index.blade.php            # Add control
resources/views/app/vaults/entities/form.blade.php             # new (R6)
routes/api.php                                                  # entities, entities.create
app/Http/Controllers/Api/EntityController.php                   # new (R7)
app/Http/Resources/EntityResource.php                           # new
resources/docs/api/07-entity-types.php                          # two sections
tests/Unit/Actions/CreateEntityTest.php                         # new
tests/Unit/Policies/EntityTypePolicyTest.php                    # addEntity
tests/Unit/ViewModels/Vaults/EntityFormViewModelTest.php       # new
tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php         # add control
tests/Feature/Controllers/App/Vaults/EntityControllerTest.php  # new, create
tests/Feature/Controllers/Api/EntityControllerTest.php         # new
lang/*.json                                                     # through monica:localize
```

**Structure Decision**: the existing layout. The form mirrors `contacts/form` in its shell and the
record page's section markup for the fields.

## Tests

| Layer | Proves |
| --- | --- |
| Action | record on the given version with its values; empty fields write nothing; both counters move; a refusal in a later section leaves no record; turned-off type, unusable default, changed default, another account's vault or type refused; journal entry dispatched |
| Policy | `addEntity` allowed; denied when turned off, without a default, with an inactive template or a draft; denied with nobody signed in |
| Form view model | pages and sections in order; relation and avatar left out; a repeater with a required relation child left out; display fields left out; old input restored |
| List view model | Add URL, and the refusal reason when denied |
| Web controller | form renders; not found for a hidden type and for the wrong vault; missing name refused; one action refusal reaches the form; redirect to the list with the status |
| API controller | list shape, pagination, another vault left out; create 201 with shape; 422 on a missing name; 404 for a hidden type |

## Complexity Tracking

None.
