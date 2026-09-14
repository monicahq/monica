# Implementation Plan: Activity category endpoints

**Branch**: `2026-09-13-activities` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

One new table, one model, seven actions, seven endpoints and a reference group, listed in
[contracts/activity-categories.md](contracts/activity-categories.md) and
[data-model.md](data-model.md). No screen, which is what was asked for, so nothing under
`app/Http/Controllers/App`, no view model, no template and no change to `routes/web.php`.

Unlike specs 021 and 033, every action is new: this record has none, because the record itself is
new. The shape is `RelationshipCategory` almost exactly, with one deliberate difference that
[research.md](research.md) section 1 argues from `spec/spec.md` §9.3: a supplied category may be
renamed, and being supplied governs deletion alone.

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 12, Sanctum. Already wired for the API group; nothing new. |
| Storage | New table `activity_categories`, one migration, `up()` only, no `constrained()`. Described in [data-model.md](data-model.md). No other table changes shape. |
| Testing | PHPUnit, `php artisan test --compact` |
| Target, type | Linux server, hosted and self hosted alike. Server rendered Laravel with a versioned JSON API beside it. |
| Performance | An account holds eight categories and a handful more. The list is one query; the count of natures is a method returning zero, so there is nothing to eager load until that record exists. |
| Constraints | 60 requests a minute per token. Page size 25, at most 100, clamped. `unique(account_id, key)` so an account holds each supplied category once. |
| Scope | 7 endpoints, 3 controllers, 1 resource, 7 actions, 1 model, 1 migration, 1 factory, 2 enums touched, 1 reference group. 26 files, 24 of them new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Seven operations, seven actions, controllers holding nothing but transport. The settings screen this record will get calls the same seven. |
| II. Simplicity over abstraction | **Pass** | Three short controllers, one resource, one enum, no base class shared with `RelationshipCategory`. The two records repeat a little rather than growing a parent nobody asked for. |
| III. Tested before merged | **Pass** | A test class per action, one for the model, three for the controllers. Nothing existing changes behaviour, so the only existing tests that move are the docs counts. |
| IV. Laravel conventions | **Pass** | `up()` only, no `constrained()`, foreign key at the bottom, every column commented, generated with `make:migration` then renamed to the next free prefix. Eloquent resource, inline validation, account scoped `firstOrFail` for the 404, kebab-case URLs, camelCase route names. |
| V. Localized and safe | **Attention** | Fifteen new strings, the eight supplied names and seven journal sentences, each translated in all 31 locales. That is 450 values and the largest single piece of this change. |
| VI. Nothing changes under anybody's feet | **Pass** | Purely additive. The only shipped file that gains behaviour is `CreateAccount`, which seeds the eight, and no existing account is backfilled because none is in production. |
| VII. A stable core | **Pass** | Activity stays a fixed concept. Its vocabulary is content, and this adds no second way to attach user defined data. |
| VIII. Nothing disappears silently | **Pass, and it shaped the design** | Retiring is the answer wherever deletion is refused, which is why availability has an address of its own. A supplied category and one holding natures are both refused deletion, never cascaded, and each refusal names its reason. |
| IX. Accessible to everybody | **Pass, vacuously** | No screen is built. The reference gains a group through the existing data driven portal and inherits markup already audited. |
| X. Everything goes through the API | **Pass** | The API is the only surface this record has, so the principle is satisfied by construction rather than caught up with later. |
| XI. The written record is short | **Over cap, declared** | spec 119, research 76, data model 52 and contract 60 are all inside. This plan is 125 against 100 and the quickstart 75 against 60. `data-model.md` earns its place here, unlike in spec 033: there is a new table to describe, and no note in `spec/models` gives the columns this project actually writes. |

**Gate result: passes, with two things the pull request must state**: this plan and the quickstart
over their principle XI caps, and the 450 translation values under principle V. **Complexity
Tracking** carries the two judgement calls.

## Project Structure

```text
database/migrations/2026_09_13_000000_create_activity_categories_table.php     new
database/factories/ActivityCategoryFactory.php      system, inactive states    new
app/Models/ActivityCategory.php                                                new
app/Enums/ActivityCategoryEnum.php                  eight cases, Translatable  new
app/Exceptions/CannotDelete{ASupplied,An}ActivityCategory{,InUse}.php  two     new
app/Actions/CreateDefaultActivityCategories.php                                new
app/Actions/{Create,Update,Destroy,Activate,Deactivate}ActivityCategory.php    new
app/Actions/UpdateActivityCategoryPosition.php                                 new
app/Http/Resources/ActivityCategoryResource.php                                new
app/Http/Controllers/Api/ActivityCategoryController.php   index, show,          new
                                                    create, update, destroy
app/Http/Controllers/Api/ActivityCategory{Activation,Position}Controller.php   new
resources/docs/api/18-activity-categories.php       the reference group         new
tests/Unit/Models/ActivityCategoryTest.php                                     new
tests/Unit/Actions/*ActivityCategory*Test.php       seven classes               new
tests/Feature/Controllers/Api/ActivityCategory*ControllerTest.php  three        new
app/Models/Account.php                              one relation                edit
app/Actions/CreateAccount.php                       one seeding call            edit
app/Enums/UserActionEnum.php                        seven cases and sentences   edit
routes/api.php                                      seven routes in the group   edit
lang/*.json                                         fifteen keys, 31 files      edit
tests/**/Docs/*                                     counts, arms, sidebar       edit
```

**Structure Decision**: the shipped layout, exactly. No new folder, so no amendment. `hasMany
ActivityType` is deliberately not declared, because that model does not exist.

## Order of work

1. The migration, the model, the enum and the factory, then the model test.
2. `CreateDefaultActivityCategories`, wired into `CreateAccount`, then the six management actions
   and their tests, then `monica:localize` for all fifteen strings.
3. `ActivityCategoryResource`, whose field list the reference and the tests both quote, then
   `ActivityCategoryController` and the two single gesture controllers.
4. The seven routes and `resources/docs/api/18-activity-categories.php` in the same commit: either
   alone fails `ApiDocsViewModelTest`, which compares the reference against the route table both
   ways, and `ApiDocsReturnFieldsTest`, which calls every documented endpoint and diffs the fields it
   answers with against the reference.
5. The three controller test classes, then the quickstart run.

## The risk worth naming

Nothing existing changes behaviour except account creation, so the harm this can do is narrow. Five
tests are the guard:

- Creating an account writes exactly eight categories, in order, each supplied, offered, holding
  nothing, and each account gets its own set.
- A supplied name reads through the translator until somebody renames it, and their words then stand
  in every language.
- A rename leaves `is_system`, `key`, `position` and `is_active` alone, on a supplied category as on
  any other.
- Deleting a supplied category is refused and the record stays; deleting one somebody added succeeds
  and is recoverable.
- All seven addresses answer 404 for another account's category and for a deleted one, never 403.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| A count that is hard-wired to zero | FR-004 puts `activity_type_count` in every answer and FR-010 refuses a deletion on it, so neither can wait for `ActivityType`. One named method holds the zero, so that record replaces a method body and nothing else. | Leaving the field out until natures exist was rejected: a field appearing later changes a published contract. Building `ActivityType` here was rejected as several times the feature that was asked for. |
| Diverging from the sibling record on rename | `spec/spec.md` §9.3 says a supplied category may be renamed, and the constitution makes `spec/` decide what the product does. | Matching the shipped relationship behaviour would mean copying a defect forward and amending two sections of the product specification to fit the code. |

## Constitution Re-check (post Phase 1)

Re-run after the design artifacts were written. **Still passes**, with two things settled.

- **Principle VIII held without a screen to help.** Every refusal names its reason and points at
  retiring, and retiring is reachable even though nothing observes it yet. A record whose guards
  arrive only with its consumer was the easy shortcut and is not what ships.
- **Principle XI moved one artifact.** `data-model.md` is written here where spec 033 omitted it, on
  the rule's own terms: it restates no other artifact and is the only description of a table this
  project is about to create.
