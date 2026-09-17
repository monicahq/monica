# Implementation Plan: Activity type management

**Branch**: `2026-09-16-activity-type-management` | **Date**: 2026-09-16 | **Spec**: [spec.md](./spec.md)

## Summary

Two screens and the custom fields behind them. A list screen pairs a rail of categories with the
types of whichever one is selected, and carries every category gesture in place: a modal to add one,
an inline rename, move up and down, switch off and on, delete. A form screen on its own URL adds and
edits a type, and leads into its custom fields. Nothing in the schema changes: the tables, the model
and `CreateActivityType` already exist, and the structure a type owns is created with it. What is
missing is every controller, view model and view, four actions, two policies and one count.

## Technical Context

**Language, dependencies**: PHP 8.4 on Laravel 12, with Blade, Tailwind CSS 4, Alpine.js, Alpine
Ajax and Turbo. No new dependency.

**Storage, testing**: MySQL, with `activity_categories` and `activity_types` unchanged. PHPUnit, in
`tests/Unit/{Actions,Policies,ViewModels}` and `tests/Feature/{Controllers,Views}`.

**Platform, type**: server rendered web in a Laravel monolith, responsive from 400px.

**Constraints**: WCAG 2.2 AA, reordering operable from the keyboard; every string translated in the
31 configured locales; `spec.md` 119 lines, `plan.md` and `research.md` at their caps.

**Scale/Scope**: 2 screens plus a custom fields screen, 13 web routes, 4 new actions, 2 policies,
3 view models, roughly 10 Blade files.

## Constitution Check

| Principle | How this plan meets it |
| --- | --- |
| I. Actions hold the logic | Four new actions (`UpdateActivityType`, `UpdateActivityTypePosition`, `ActivateActivityType`, `DeactivateActivityType`) join the six that exist. Controllers validate and delegate. View models only read. |
| II. Simplicity over abstraction | The custom fields screen is written again rather than abstracted out of the template builder (D1). |
| III. Tested before merged | Every action, policy, controller and view model gets a test; `PolicyCoverageTest` forces three cases per ability. |
| IV. Laravel conventions | One named route per line, inline validation, class array controller references, no `Route::resource`. |
| V. Localised and safe | Every string through `__()` or `trans_choice()`; `monica:localize` run and translated in all locales. |
| VI. Nothing changes underfoot | Publishing a type's fields never moves an activity already recorded; the engine already guarantees it. |
| VII. Stable core | No second field mechanism: the type's fields go through the one engine, on its own structure. |
| VIII. Nothing disappears silently | Switching a category or a type off states what leaves and what stays before it runs; deletion is soft and confirmed. |
| IX. Accessible | Rail entries are links marked with `aria-current`; reordering is a button per direction, not only a drag; a refused control stays on screen, `aria-disabled`, pointing at its reason. |
| X. Everything through the API | **Not met here.** The ActivityType API is deferred to its own feature, as `spec/models/035` states and as 024 and 033 did for relationship types. Recorded below. |
| XI. The written record is short | Every artifact at or under its cap; `contracts/` holds the route table and nothing restated. |

## Project Structure

### Documentation (this feature)

```text
specs/037-activity-type-management/
├── plan.md, research.md  # six decisions, with what was rejected
├── data-model.md        # what the tables already carry, and the one count that changes
├── quickstart.md        # how to see it work
├── contracts/routes.md  # the thirteen web routes
└── tasks.md             # /speckit-tasks output, not written here
```

### Source Code (repository root)

```text
app/
├── Actions/                          UpdateActivityType, UpdateActivityTypePosition,
│                                     ActivateActivityType, DeactivateActivityType
├── Enums/UserActionEnum.php          four cases added, four wordings
├── Exceptions/                       three CannotDelete* classes removed (D4)
├── Http/Controllers/App/Settings/Account/ActivityTypes/
│                                     ActivityTypeController, ActivityTypePositionController,
│                                     ActivityTypeActivationController, ActivityCategoryController,
│                                     ActivityCategoryPositionController,
│                                     ActivityCategoryActivationController,
│                                     CustomFieldsController, CustomFieldSectionController,
│                                     CustomFieldController, CustomFieldPositionController,
│                                     CustomFieldPublicationController
├── Models/ActivityCategory.php       activityTypeCount() counts
├── Models/StructureVersionSection.php  isReachableThrough() takes any owner (D1)
├── Policies/                         ActivityTypePolicy, ActivityCategoryPolicy
└── ViewModels/Settings/Account/ActivityTypes/
                                      ActivityTypesViewModel, ActivityTypeFormViewModel,
                                      ActivityTypeCustomFieldsViewModel

resources/views/app/settings/account/activity-types/
    index.blade.php, form.blade.php, custom-fields.blade.php,
    _rail.blade.php, _category-actions.blade.php, _row.blade.php, _section.blade.php, _field.blade.php

resources/views/components/settings/sidebar.blade.php   one entry added
routes/web.php                                          thirteen routes
lang/*.json                                             every new string, 31 locales
```

**Structure Decision**: the Laravel monolith as it stands. Everything lands in a folder that exists,
grouped by domain the way `RelationshipTypes` and `Templates` are, which the constitution's fixed
repository layout requires.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle X not met in this feature | The design and the request are the screens; `spec/models/035` says the API follows separately, and 024 then 033 is the precedent | Shipping both at once roughly doubles the feature and was put to the author, who chose screens first |
| A second field builder screen | A type's structure has one page and none of a template's copy, export, import, share or history | Generalising `TemplateBuilderViewModel` rewrites a shipped screen for a caller needing under half of it (D1) |
