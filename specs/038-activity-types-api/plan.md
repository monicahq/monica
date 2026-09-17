# Implementation Plan: Activity type and category endpoints

**Branch**: `038-activity-types-api` | **Date**: 2026-09-17 | **Spec**: [spec.md](./spec.md)

## Summary

Activity types ship with settings screens and no API coverage, which principle X does not allow. The
seven actions they need already exist and are used by the web controller alone, so this is transport,
resources, routes, tests and a reference page, with no new business logic and no schema change.

Ten endpoints, listed in [contracts/endpoints.md](./contracts/endpoints.md). Nine are the types; the
tenth lists the types of one category, which the categories were missing. The decisions behind the
shapes, and the three tests that already hold the routes, the reference and the OpenAPI document
together, are in [research.md](./research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13 (`composer.json`, `laravel/framework: ^13.8`)

**Primary Dependencies**: Laravel Sanctum for the bearer token, nothing new

**Storage**: MySQL. No migration: `activity_types` and its structure tables already hold every column

**Testing**: PHPUnit. Feature tests under `tests/Feature/Controllers/Api`, and a unit test for
the new relation. The suite holds no `*ResourceTest.php`: a resource shape is asserted through the
feature tests' `$jsonStructure`, and this feature follows that

**Target Platform**: The JSON API on `routes/api.php`, self hosted and hosted alike

**Project Type**: Server rendered Laravel application with a JSON API beside it

**Performance Goals**: None specific. An account holds a few dozen types; the lists are paged the way
every other collection endpoint is

**Constraints**: No new top-level folder, no new dependency, no view model consumed outside Blade

**Scale/Scope**: 10 endpoints, 5 controllers, 1 resource, 1 relation, 1 reference page, 1 corrected
sentence

## Constitution Check

*Gate before Phase 0, re-checked after Phase 1. Both passes clean.*

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the business logic | Every endpoint calls an action that already exists. The controllers validate, delegate and answer. `ActivityTypeFieldController` turns a nested body into the action's arguments, which is transport: which field matches which stays in `ReplaceActivityTypeFields` |
| II. Simplicity over abstraction | No base class, no trait, no service. The paging block is repeated from `ActivityCategoryController` rather than extracted, which is what the rule asks for |
| III. Tested before merged | Each controller gets a feature test, the resource and the relation a unit test. The eleven cases the `api-writer` skill lists are covered, cross-account included |
| IV. Laravel conventions | Kebab-case URLs, camelCase route names and parameters, array validation rules, one class per file, early returns |
| V. Localized and safe | The one new string is the policy refusal, which exists. `php artisan monica:localize` runs at the end; the reference page is English, as the whole portal is |
| VI. Nothing changes under anybody's feet | The fields write publishes a new structure version beside the old one. An activity keeps the version it was written on. That is the action's behaviour and this feature does not touch it |
| VII. A stable core | Activity stays fixed; its natures and their fields stay configurable through the one Template, Page, Section, Field mechanism |
| VIII. Nothing disappears silently | Deleting is soft. A supplied type refuses deletion and the refusal points at retiring it |
| IX. Accessible to everybody | No screen is touched |
| X. Everything goes through the API | This is that principle being discharged for activity types |
| XI. The written record is short | `plan.md` 98 lines, `research.md` 50, `contracts/endpoints.md` 54. `data-model.md` and `quickstart.md` are omitted, below |

**Omitted artifacts**. `data-model.md` would carry nothing: there is no migration, no new model and no
new column, and the spec's Key Entities already names the four things involved. `quickstart.md` would
restate the reference page, which is the product's own worked guide and is generated into the OpenAPI
document. Principle XI requires both omissions rather than permitting them.

## Project Structure

New files:

```text
app/Http/Controllers/Api/ActivityTypeController.php
app/Http/Controllers/Api/ActivityTypeActivationController.php
app/Http/Controllers/Api/ActivityTypePositionController.php
app/Http/Controllers/Api/ActivityTypeFieldController.php
app/Http/Controllers/Api/ActivityCategoryTypeController.php
app/Http/Resources/ActivityTypeResource.php
resources/docs/api/19-activity-types.php
tests/Feature/Controllers/Api/ActivityTypeControllerTest.php
tests/Feature/Controllers/Api/ActivityTypeActivationControllerTest.php
tests/Feature/Controllers/Api/ActivityTypePositionControllerTest.php
tests/Feature/Controllers/Api/ActivityTypeFieldControllerTest.php
tests/Feature/Controllers/Api/ActivityCategoryTypeControllerTest.php
```

Changed files:

```text
app/Models/Account.php                        # activityTypes(), the HasMany the account-wide list needs
routes/api.php                                # ten routes, imports in alphabetical order
resources/docs/api/18-activity-categories.php # the activity_type_count sentence, and the types listing
resources/docs/api/19-mcp.php                 # renamed 20-mcp.php so the guide stays last
tests/Unit/Models/AccountTest.php             # the new relation
```

**Structure Decision**: the existing layout, unchanged. `Api` controllers on `routes/api.php` with
their resources in `app/Http/Resources`, the reference in `resources/docs/api`, tests mirroring the
controller namespace. No new folder, which the constitution would make an amendment.

## Complexity Tracking

No violations. Nothing to justify.
