# Implementation Plan: Add a relationship

**Branch**: `2026-09-13-spec-relations` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

Build the `Relationship` model the product spec has been describing but nothing has written yet, and one screen that creates a row in it. The screen is a page at `contacts/{contact}/relationships/new`, reached from the relationships tab that today renders an "unbuilt" placeholder. Progressive disclosure, the swap, the three-way choice and the preview sentence are Alpine on a server-rendered form; `app/Actions/CreateRelationship.php` holds every rule and is the only thing the later API endpoint will need.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12 · **Frontend**: Blade, Tailwind v4, Alpine.js, Alpine Ajax, Turbo

**Storage**: Eloquent. The suite runs on SQLite, MySQL and PostgreSQL, so no driver-specific SQL.

**Testing**: PHPUnit. `tests/Unit` mirrors the app namespace, `tests/Feature/Controllers` and `tests/Feature/Views`.

**Project Type**: server-rendered web application, one screen added to an existing area.

**Performance Goals**: the contact picker must not load the vault's contacts into the form. Search filters and pages in the database.

**Constraints**: WCAG 2.2 AA; every string through `__()` and translated in all 31 locales; PHPStan level 5; Pint clean.

**Scale/Scope**: 1 new table, 1 model, 1 enum, 1 action, 1 controller, 1 view model, 1 view, 2 routes, 1 factory.

## Constitution Check

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | `CreateRelationship` holds every rule. The controller validates and delegates. The view model only reads. |
| II. Simplicity over abstraction | No new abstraction. The form copies `relationship-types/form.blade.php`, which already does disclosure and a preview sentence. |
| III. Tested before merged | Unit tests for the model, the enum and the action; feature tests for both routes and the view; the compartmentalisation trio. |
| IV. Laravel conventions | `up()` only, no `constrained()`, inline validation, camelCase route names, kebab-case URL and view file. |
| V. Localized and safe | Every string through `__()`, then `monica:localize` across all locales. No destructive action on this screen. |
| VI. Nothing changes underfoot | Nothing existing is rewritten. A relationship points at a type by id and does not follow a renamed one. |
| VII. Stable core | Relationship is one of the nine fixed concepts. It gets a table, not a personalisation mechanism. |
| VIII. Nothing disappears silently | Soft deletes on the table from the start, though this feature never deletes. |
| IX. Accessible | `fieldset`/`legend` for the three-way choice, `aria-live` on the preview, `x-cloak` so nothing flashes, reveal honours reduced motion. |
| X. Everything through the API | **Deferred, and said so.** The user scoped this to the form. Every rule sits in `CreateRelationship`, so `Api\RelationshipController` is later transport and nothing more. |
| XI. The written record is short | `data-model.md` and `contracts/` are omitted: see Complexity Tracking. |

**Gate: passes**, before research and again after design, with the entries below declared rather than hidden.
The design added no new violation: it introduced no folder, no dependency and no second mechanism.

## Project Structure

### Documentation

```text
specs/029-add-relationship-form/
├── plan.md          # this file
├── spec.md
├── research.md      # the decisions and what they cost
├── quickstart.md    # how to see it work and what to run
└── checklists/requirements.md
```

`data-model.md` is **omitted**: [spec/models/026](../../spec/models/026-relationship-not-yet.md) already specifies the table field for field, its two indexes and its rules, and principle XI forbids saying it twice. `contracts/` is **omitted**: no interface is exposed by this feature.

### Source code

```text
app/Enums/RelationshipStatusEnum.php            new   current | ended, Translatable
app/Models/Relationship.php                     new   ResolvesModelInVault, HasUuids, SoftDeletes
app/Models/RelationshipType.php                 edit  relationshipCount() stops returning 0
app/Enums/UserActionEnum.php                    edit  RelationshipCreation + its description
app/Actions/CreateRelationship.php              new   every rule, including the parentage cycle
app/Http/Controllers/App/Vaults/ContactRelationshipController.php   new   new() and create()
app/ViewModels/Vaults/RelationshipFormViewModel.php                 new   types, candidates, copy
resources/views/app/vaults/contacts/relationships/form.blade.php    new
routes/web.php                                  edit  two routes, inserted above the {page?} catch-all
database/migrations/2026_09_10_000005_create_relationships_table.php   new
database/factories/RelationshipFactory.php      new
tests/Unit/{Models,Actions,Enums}/…             new   Relationship, CreateRelationship, the enum
tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php   new
tests/Unit/Models/RelationshipTypeTest.php      edit  the assertion that relationshipCount() is 0
```

**Structure Decision**: no new folders. Every file lands in a directory the constitution already names.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| `spec.md` is 150 lines against a 120-line cap | 31 atomic requirements, 4 stories and 11 edge cases, each one sentence and each testable | Merging requirements would hide testable behaviour behind compound sentences, which costs more than the 30 lines |
| `RelationshipType::relationshipCount()` stops returning `0` | It is a deliberate stub (`app/Models/RelationshipType.php:109-119`) whose comment says "the relationship record does not exist yet". Once it does, six call sites start being told the truth | Leaving it at `0` would let a type in use be deleted and its direction reversed, silently breaking rows this feature writes. That is worse than the behaviour change |
| No API endpoint | The user scoped this to the form. Principle X allows it if the plan says where the coverage is and the logic sits in an action | Building the endpoint now is work the user explicitly excluded |
