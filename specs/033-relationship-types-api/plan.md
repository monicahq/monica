# Implementation Plan: Relationship category and type endpoints

**Branch**: `2026-09-13-api` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

Fifteen endpoints put the Relationship types settings screens behind the API, listed in
[contracts/relationship-categories.md](contracts/relationship-categories.md) and
[contracts/relationship-types.md](contracts/relationship-types.md), plus two reference groups. No
migration, no model change, no screen touched.

Unlike spec 021, this is not pure transport: availability has no action of its own here, so four are
added, copied from `ActivateEntityType`. **Complexity Tracking** below and
[research.md](research.md) sections 2 and 3 carry the reasoning and the rejected alternatives.

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 12, Sanctum. Already wired for the API group; nothing new. |
| Storage | `relationship_categories` and `relationship_types`, already migrated. No schema change, migration or factory change. The records are described in [spec/models/024](../../spec/models/024-relationship-category-done.md) and [025](../../spec/models/025-relationship-type-done.md), which is why there is no `data-model.md`. |
| Testing | PHPUnit, `php artisan test --compact` |
| Target, type | Linux server, hosted and self hosted alike. Server rendered Laravel with a versioned JSON API beside it. |
| Performance | An account holds five categories and ten types. `relationship_count` has no denormalised column, so the lists carry `withCount` rather than a count per row. See [research.md](research.md) section 5. |
| Constraints | 60 requests a minute per token. Page size 25, at most 100, clamped. Names are unique per account, checked in PHP because supplied names are translation keys. |
| Scope | 15 endpoints, 7 controllers, 2 resources, 4 actions, 2 reference groups. 24 files, 20 of them new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Eleven operations, seven existing actions reused unchanged, four added because the gesture has no action today. Controllers call them with named arguments and hold no rule. No view model is read. |
| II. Simplicity over abstraction | **Pass** | Seven short controllers mirroring the four web ones, two resources, no base class, service, repository or form request. The four new actions are `ActivateEntityType` with the nouns changed. |
| III. Tested before merged | **Pass** | Seven API test classes and four action test classes. The only existing test that changes is the docs sidebar assertion, which gains two lines. |
| IV. Laravel conventions | **Pass** | Eloquent resources, inline validation, account scoped `firstOrFail` for the 404, config for the page bounds, kebab-case URLs, camelCase route names. Deviations from `api-writer`, which is stale, are declared in [research.md](research.md) section 7. |
| V. Localized and safe | **Attention** | Four journal sentences are new user-facing copy and must be translated in all 31 locales through `monica:localize`. Refusals carry the exceptions' own English text, as every API refusal here does. |
| VI. Nothing changes under anybody's feet | **Pass** | Additive. No shipped file changes behaviour: `UpdateRelationshipType` keeps its `isActive` argument and the settings screens are untouched. Rewording a type leaves every relationship written with it reading as before. |
| VII. A stable core | **Pass** | Relationship stays a fixed concept. Its vocabulary is content, and nothing here adds a second way to attach user defined data. |
| VIII. Nothing disappears silently | **Pass, and it shaped the design** | Retiring is the answer wherever deletion is refused, which is the reason availability gets its own address rather than living inside a rewording. Deleting a type in use, a supplied type, a supplied category or a category holding types is refused, never cascaded. |
| IX. Accessible to everybody | **Pass** | No screen is touched. The reference gains two groups through the existing data driven portal and inherits markup already audited. |
| X. Everything goes through the API | **Pass** | The principle the feature exists to serve. It discharges the deferral spec 024's plan recorded, and goes one gesture further: retiring a category, which the picker honours and no screen offers. |
| XI. The written record is short | **Over cap, declared** | spec 134 against 120, this plan 114 against 100, research 85 against 80, quickstart 66 and the type contract 65 against 60. `data-model.md` is omitted: the records exist and `spec/models/` describes them. |

**Gate result: passes, with two things the pull request must state**: the four new translation keys
under principle V, and the four artifacts over their cap under principle XI. **Complexity Tracking**
below carries the one judgement call.

## Project Structure

```text
app/Actions/{Activate,Deactivate}Relationship{Type,Category}.php   four          new
app/Http/Resources/Relationship{Category,Type}Resource.php         two           new
app/Http/Controllers/Api/RelationshipCategoryController.php   index, show,       new
app/Http/Controllers/Api/RelationshipTypeController.php       create, update,    new
                                                              destroy
app/Http/Controllers/Api/Relationship{Category,Type}ActivationController.php     new
app/Http/Controllers/Api/Relationship{Category,Type}PositionController.php       new
app/Http/Controllers/Api/RelationshipCategoryTypeController.php    index         new
resources/docs/api/1{6,7}-relationship-{categories,types}.php      two groups    new
tests/Unit/Actions/{Activate,Deactivate}Relationship{Type,Category}Test.php      new
tests/Feature/Controllers/Api/Relationship*ControllerTest.php      seven         new
app/Enums/UserActionEnum.php                        four cases, four sentences   edit
routes/api.php                                      fifteen routes in the group  edit
lang/*.json                                         four keys, 31 files          edit
tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php    two assertSee    edit
```

**Structure Decision**: the shipped API layout, exactly. Controllers flat under `Api`, resources
beside the nineteen in `app/Http/Resources`, actions beside the 173 in `app/Actions`, the reference
two more numbered files. No new folder, so no amendment.

## Order of work

1. The four actions and their enum cases, then `monica:localize` for the four sentences.
2. The two resources, whose field lists the reference and the tests both quote.
3. The two main controllers, then the five single gesture ones.
4. The fifteen routes, with the uuid constraint on every parameter, and the two reference files in
   the same commit: either alone fails `ApiDocsViewModelTest`, which compares the reference against
   the route table in both directions.
5. The eleven test classes, then the quickstart run.

## The risk worth naming

Nothing migrates and no screen moves, so the only harm is answering differently from the browser, or
letting one account's vocabulary be reached from another. Five tests are the guard:

- A type added through the API and one added in the browser are identical, `is_system` and
  `system_behavior` both null, whatever the name and the category.
- A correction omitting `is_symmetric` leaves the direction alone, one sending it against a supplied
  or in-use type answers 422 rather than silently keeping the old value as the form does, and no
  correction ever moves `is_active` or `position`.
- Deleting a supplied type, a type in use, a supplied category and a category holding types each
  answer 422 and leave the record, on both surfaces.
- All fifteen addresses answer 404 for another account's record and for a deleted one, never 403.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Four new actions, where spec 021 added none | Availability has no action here: `UpdateRelationshipType` folds `isActive` into a full rewording, and a category has no availability gesture at all. Without them, retiring a type through the API means resending its name and both roles, where one slip rewrites the wording, and retiring a category is impossible though the picker honours it. | Having the activation controller call `UpdateRelationshipType` with the current wording was rejected as logic in the transport. Refactoring `UpdateRelationshipType` to drop `isActive` and pointing the web controller at the new actions is the cleaner end state, but it changes a shipped screen this feature was not asked to touch; it stays open and nothing here blocks it. |
| Four artifacts over their principle XI cap | Two records, fifteen endpoints and two reference groups, where spec 021 had one record and seven endpoints. | Cutting further means dropping acceptance scenarios, which the tests are written from, or a contract's refusal table, which is what the tests assert. |

## Constitution Re-check (post Phase 1)

Re-run after the contracts and the quickstart were written. **Still passes**, with two things settled.

- **Principle I held for the seven existing actions.** Every refusal the spec asks for already lives
  in one, and the controllers add only the catch that turns it into a 422. The four new actions carry
  a state transition and a log line and nothing else, which is why they are not a rule in two places.
- **Principle VIII needed one decision the browser had not made.** `RelationshipTypeController`
  silently substitutes the current direction when it is frozen; the API refuses instead, because a
  form ignoring a disabled field is right and an API accepting a change it did not make is not.
