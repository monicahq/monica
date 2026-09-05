# Implementation Plan: Entity type endpoints

**Branch**: `2026-09-05-api-entity-types` | **Date**: 2026-09-05 | **Spec**: [spec.md](spec.md)

## Summary

Seven endpoints put the entity types settings screen behind the API, listed in
[contracts/entity-types.md](contracts/entity-types.md). Every one of them calls an action that already exists
and is already tested, so there is no new domain code, no model change and no migration. This is transport
and a reference group.

The two decisions worth arguing are both about addressing. A type is reached by its uuid rather than by the
slug the browser uses, and the two gestures that are not corrections to wording, whether records may still be
filed under the type and where it sits in the order, get an address each rather than a field in the update
body. [research.md](research.md) sections 1 and 2 carry the rejected alternatives.

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 12, Sanctum. All already wired for the API group; nothing new. |
| Storage | Table `entity_types`, already migrated. No schema change, no migration, no factory change. |
| Testing | PHPUnit, `php artisan test --compact` |
| Target, type | Linux server, hosted and self hosted alike. Server rendered Laravel with a versioned JSON API beside it. |
| Performance | An account holds a handful of types. The list is one query plus a count, with nothing to eager load: `entity_count` is a running total kept by the entity actions, not a count taken when somebody asks. |
| Constraints | 60 requests a minute per token. Page size 25, at most 100, clamped. Slug uniqueness spans deleted types, so it is a plain unique rule with no `whereNull`. |
| Scope | 7 endpoints, 3 controllers, 1 resource, 1 reference group. 8 files, 6 of them new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Six operations, six existing actions, named arguments. No new class under `app/Actions`, no edit to one, and no view model read: the list order is `position` then `id`, which is one clause rather than logic worth sharing. |
| II. Simplicity over abstraction | **Pass** | Three short controllers mirroring the two web ones, one resource, no base class, service, repository or form request. The validation rules are written out again rather than extracted, which is what `VaultController` already does and what the rule asks for at this length. |
| III. Tested before merged | **Pass** | Three API test classes covering every endpoint, every refusal and the compartment on each. No existing test changes except the reference assertion. |
| IV. Laravel conventions | **Pass** | Eloquent resource, inline validation, account scoped `firstOrFail` for the 404, config for the page bounds, kebab-case URLs, camelCase route names. Four declared deviations from `api-writer`, in [research.md](research.md) section 6. |
| V. Localized and safe | **Pass** | No new user-facing string. The one refusal carries the exception's own English text, as every API refusal here does; the browser keeps its translated copy. `monica:localize --check` runs anyway. |
| VI. Nothing changes under anybody's feet | **Pass** | Purely additive: no shipped file changes behaviour. The slug is settled once and never rebuilt, so a link that already went out keeps working through any rename made here. |
| VII. A stable core | **Pass** | An entity type is how an account invents a noun, which is the one personalisation mechanism. Nothing here adds a second, and nothing makes a fixed concept personalisable. |
| VIII. Nothing disappears silently | **Pass, and it shaped the design** | `UpdateEntityType` writes an absent field as its default, so a caller sending only a new name would reset the icon and erase the description. Every field on the correction is therefore required or `present`, the rule the vault endpoint settled. Deactivation is offered precisely so that a type in use has an answer other than deletion, and deletion of a type holding records is refused rather than cascading. |
| IX. Accessible to everybody | **Pass** | No screen is touched. The one rendered surface is the reference, which gains a group through the existing data driven portal and inherits markup already audited. |
| X. Everything goes through the API | **Pass** | The principle the feature exists to serve: it closes the last gesture of the entity types screen. The known gap is carried forward rather than hidden: a key's reach still cannot be narrowed, so any valid key can delete a type. |
| XI. The written record is short | **Pass** | spec 105, plan under 100, research under 80, contract and quickstart under 60. `data-model.md` is omitted: there is no schema to describe, and the spec's Key Entities and the contract's field list carry what it would have said. |

**Gate result: passes.** The deviations from `.claude/skills/api-writer/SKILL.md` are declared in
[research.md](research.md) section 6, as Principle IV requires. **Complexity Tracking** needs no entry:
nothing is extracted, nothing is abstracted, and no folder is added.

## Project Structure

```text
app/Http/Resources/EntityTypeResource.php                                        new
app/Http/Controllers/Api/EntityTypeController.php   index, show, create,         new
                                                    update, destroy
app/Http/Controllers/Api/EntityTypeActivationController.php   update             new
app/Http/Controllers/Api/EntityTypePositionController.php     update             new
routes/api.php                                      seven routes in the group    edit
resources/docs/api/10-entity-types.php              the reference group          new
tests/Feature/Controllers/Api/EntityType*ControllerTest.php   three classes      new
tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php  one assertSee      edit
```

**Structure Decision**: the shipped API layout, exactly. Controllers flat under `Api`, as `Api/VaultController`
already is for `App/Vaults/VaultController`, the resource beside the six in `app/Http/Resources`, the reference
one more numbered file. No new folder, so no amendment.

## Order of work

1. `EntityTypeResource`, whose field list the reference and the tests both quote.
2. `EntityTypeController`, then the two single gesture controllers.
3. The seven routes, with the uuid constraint on the parameter.
4. `resources/docs/api/10-entity-types.php`, in the same commit as the routes: either alone fails
   `ApiDocsViewModelTest`, which compares the reference against the route table in both directions.
5. The three test classes, then the quickstart run.

## The risk worth naming

Nothing migrates and no screen moves, so the only way this does harm is by answering differently from the
browser, or by letting one account's type be reached from another. Four tests are the guard:

- Declaring a type through the API and through the browser produces the same slug from the same name,
  including when the name carries nothing a slug can hold, and including when a deleted type holds the slug.
- A correction omitting the description, the icon or the colour is refused, or a caller silently loses them.
- Deleting a type holding records answers 422 and leaves the type, on both surfaces.
- All seven addresses answer 404 for another account's type and for a deleted one, never 403.

## Constitution Re-check (post Phase 1)

Re-run after the contract and the quickstart were written. **Still passes**, with two things settled that the
first pass could only assume.

- **Principle I held completely.** Seven endpoints, six existing actions, no edit to any of them and no new
  one, which is evidence the browser path kept its logic where it belonged. It belongs in the pull request.
- **Principle VIII needed no fix here.** Unlike the contacts change, no refusal is missing from the browser
  path: `EntityTypeController@destroy` already catches the in-use exception, so the API mirrors a guard that
  exists rather than repairing one that does not.
