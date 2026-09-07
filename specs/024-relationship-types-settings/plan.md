# Implementation Plan: Relationship types in account settings

**Branch**: `2026-09-07-relationships` | **Date**: 2026-09-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/024-relationship-types-settings/spec.md`

## Summary

Two account-owned tables, `relationship_categories` and `relationship_types`, seeded with the five categories and ten types of `spec/spec.md` §8.2, and two settings screens over them: a list grouped by category and a form for adding and editing a type. The screens copy the shape of Entity types, which already solves the same problem (list, form, activation, drag to reorder, soft delete). Nothing here reads or writes a relationship, because the relationship record does not exist yet.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: the default connection, and the suite runs on SQLite, MySQL and PostgreSQL

**Testing**: PHPUnit, `RefreshDatabase`, factories

**Target Platform**: server-rendered web, desktop, tablet and phone

**Project Type**: Laravel monolith

**Performance Goals**: the list is two queries whatever the number of categories, so no pagination and no lazy loading

**Constraints**: WCAG 2.2 AA, nine locales, PHPStan level 5, Pint clean

**Scale/Scope**: two screens, two tables, five categories and ten types per account

## Constitution Check

*GATE: checked before Phase 0 and again after Phase 1. Both passes give the same result.*

| Principle | Verdict |
| --- | --- |
| I Actions hold the business logic | Pass. Nine actions, thin controllers, two read-only view models. |
| II Simplicity over abstraction | Pass. No base class shared with Entity types; the two screens repeat a little Blade rather than hide it. |
| III Tested before merged | Pass. A test per model, action, controller and view model. |
| IV Laravel conventions are the default | Pass. `up()` only, no `constrained()`, camelCase route names, inline validation. |
| V Localized and safe | Pass. `monica:localize` in nine locales, and both deletions confirm first. |
| VI Nothing changes under anybody's feet | Pass, and it is the reason direction is frozen once a type is supplied or in use. Rewording a role never rewrites a recorded relationship. |
| VII A stable core | Pass. Relationship stays a fixed concept; its vocabulary is content, not a schema change. |
| VIII Nothing disappears silently | Pass. Deactivation is the answer everywhere deletion is refused, deletes are soft, and every refusal says why. |
| IX Accessible to everybody | Pass only with the deviation in Phase 1: type rows get the same up and down buttons the design gives categories, because dragging alone fails 2.1.1 and 2.5.7. The existing `sortableRows` helper is pointer-only. |
| X Everything goes through the API | **Deferred.** See Complexity Tracking. |
| XI The written record is short | **`spec.md` over cap.** See Complexity Tracking. |

## Project Structure

### Documentation (this feature)

```text
specs/024-relationship-types-settings/
├── plan.md
├── research.md          # the eight decisions and what was rejected
├── data-model.md        # the two tables and their rules
├── quickstart.md        # how to run and see it work
├── contracts/routes.md  # the web routes this feature adds
└── tasks.md             # written by /speckit-tasks
```

### Source Code (repository root)

```text
app/Actions/                    CreateRelationshipCategory, UpdateRelationshipCategory,
                                DestroyRelationshipCategory, UpdateRelationshipCategoryPosition,
                                CreateRelationshipType, UpdateRelationshipType,
                                DestroyRelationshipType, UpdateRelationshipTypePosition,
                                CreateDefaultRelationshipTypes
app/Enums/                      RelationshipBehaviorEnum
app/Models/                     RelationshipCategory, RelationshipType
app/Http/Controllers/App/Settings/Account/RelationshipTypes/
                                RelationshipTypeController, RelationshipTypePositionController,
                                RelationshipCategoryController, RelationshipCategoryPositionController
app/ViewModels/Settings/Account/RelationshipTypes/
                                RelationshipTypesViewModel, RelationshipTypeFormViewModel
resources/views/app/settings/account/relationship-types/
                                index.blade.php, form.blade.php, _row.blade.php,
                                _category.blade.php, _delete.blade.php
database/migrations/            create_relationship_categories_table,
                                create_relationship_types_table
database/factories/             RelationshipCategoryFactory, RelationshipTypeFactory
routes/web.php                  eleven routes under settings/account/relationship-types
tests/Unit/, tests/Feature/     mirroring every class above
```

**Structure Decision**: the Entity types feature is copied path for path, one folder deeper because this feature owns two records rather than one. `CreateAccount` gains a sixth step, `CreateDefaultRelationshipTypes`, beside `CreateDefaultValueLists`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle X, no API in this feature | The `Api` namespace exists, so the old escape clause is gone. The logic sits entirely in the nine actions and the controllers add nothing but transport, so the API controllers add no rework. The project has shipped this way three times (specs 019, 021, 023), each API in its own feature. | Building both surfaces at once doubles the feature and settles the API shape for a vocabulary whose consumer, the relationship record, is not designed yet. The follow-up feature is named in `research.md` and must land before relationships ship. |
| Principle XI, `spec.md` is 249 lines against a cap of 120 | Seven user stories, each an independently shippable slice, and thirty-three requirements. Cutting to 120 would mean dropping acceptance scenarios, which are what the tests are written from. | Splitting into two specs, types and categories, was rejected: the design puts both on one screen and the category rules only make sense against the types they hold. |
