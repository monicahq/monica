# Implementation Plan: Manage relationships

**Branch**: `2026-09-13-manage-relationships` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

A Relationships tab on the contact, listing every link grouped by its nature's category, and the two operations the product has never had: correcting a link and removing one. One scope on `Relationship` carries the selection, two actions carry the writing, and the type picker [029](../029-add-relationship-form/spec.md) built moves into a shared object both forms read. No table, no column, no migration. Every decision and its rationale is in [research.md](research.md).

## Technical Context

| | |
|---|---|
| Language | PHP 8.4, Laravel 12 |
| Frontend | Blade, Tailwind 4, Alpine for the per-group Manage toggle, Alpine Ajax for the row that leaves |
| Storage | The existing `relationships`, `relationship_types`, `relationship_categories` and `contacts` tables, unchanged. `status`, `started_on` and `ended_on` are written for the first time |
| Testing | PHPUnit, `RefreshDatabase`, the existing relationship factory states |
| Scope | One page, one edit form, two actions, one shared picker object, around 600 lines of application code |
| Performance | Two index reads for the selection, one query for every age at once, neither growing with the number of relationships listed |

No unknowns remain.

## Constitution Check

| Principle | Verdict |
|---|---|
| I. Actions hold the business logic | Pass. `UpdateRelationship` and `DestroyRelationship`, one operation each. The controller validates and delegates. The view models only read. |
| II. Simplicity over abstraction | Pass, with one extraction: the type picker moves out of `RelationshipFormViewModel` because two forms now read it ([research.md](research.md) D7). The row markup repeats in one file and stays inline. |
| III. Tested before merged | Pass. Scope, both actions, both controllers, both view models, the page. See Testing below. |
| IV. Laravel conventions | Pass. A query scope, actions with promoted readonly inputs and one `execute()`, inline validation, camelCase route names, `up()`-only (none needed). |
| V. Localized and safe | Pass. Every string through `__()`, every count through `trans_choice()`, `monica:localize` run before done. Removal warns first through the existing `x-delete-action`. |
| VI. Nothing changes underfoot | Pass. Correcting a link is the owner's own act on their own record; nothing else moves under them. |
| VII. A stable core | Pass. Relationship is one of the nine fixed concepts, and this changes its content, never its nature. No new way to attach user data. |
| VIII. Nothing disappears silently | Pass, and this feature is the principle working: an ended link is deactivated rather than destroyed, keeps its place and its count, and says why it reads as over. A withdrawn nature still reads on the links that carry it. Removal is the one true deletion, and it is confirmed. |
| IX. Accessible to everybody | Pass. The Manage chip carries `aria-pressed`, hidden controls are hidden from focus, the group heading names its list, ages and statuses carry accessible names, and no meaning is carried by colour. |
| X. Everything goes through the API | **Deferred, and this is the second time.** [029](../029-add-relationship-form/spec.md) deferred create, [030](../030-immediate-family-widget/plan.md) deferred read, and the principle names "managing relationships" by name. The debt is now four operations. See [research.md](research.md) D9 and the note below. |
| XI. The written record is short | **One cap exceeded.** `spec.md` is 165 lines against 120; three user stories and 29 requirements are what the feature is. `data-model.md` and `contracts/` are omitted, not empty: no entity is added and no interface exposed, and XI requires omission rather than a placeholder. |

## Project Structure

```text
app/
├── Actions/
│   ├── UpdateRelationship.php                # new
│   ├── DestroyRelationship.php               # new
│   └── CreateRelationship.php                # the ancestry walk moves to a shared place
├── Enums/UserActionEnum.php                  # + RelationshipUpdate, RelationshipDeletion
├── Models/Relationship.php                   # + scopeForContact
├── Http/Controllers/App/Vaults/
│   └── ContactRelationshipController.php     # + edit, update, destroy
└── ViewModels/Vaults/
    ├── RelationshipsBlock.php                # new, beside ImmediateFamilyBlock
    ├── RelationshipTypeChoices.php           # new, the picker both forms read
    ├── RelationshipEditViewModel.php         # new
    ├── RelationshipFormViewModel.php         # the picker moves out
    └── ContactViewModel.php                  # + the reserved tab key and its count

resources/views/app/vaults/contacts/
├── show.blade.php                            # + the branch for the new tab
├── _relationships.blade.php                  # new, the page itself
└── relationships/edit.blade.php              # new, the edit form

tests/
├── Unit/Models/RelationshipTest.php          # + the scope
├── Unit/Actions/UpdateRelationshipTest.php
├── Unit/Actions/DestroyRelationshipTest.php
├── Unit/ViewModels/Vaults/RelationshipsBlockTest.php
├── Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php
├── Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php
└── Feature/Views/Vaults/RelationshipsTest.php
```

**Structure Decision**: the existing layout, no new directory. The page is a partial, drawn on one screen; the edit form is a view, because it has its own address.

## Approach

1. **Selection** (D2). `Relationship::scopeForContact` unions both indexed sides, eager-loading type, category and contacts.
2. **Shaping** (D3, D4). `RelationshipsBlock` groups by category in the account's order, drops empty groups, and returns rows of name, URL, avatar, age, role as read from this contact's side, and status. Ages come from one `FieldValue` query, as [030](../030-immediate-family-widget/plan.md) does.
3. **The tab** (D1). `relationships` joins `overview` and `version-history` as a reserved key; `show.blade.php` grows one branch.
4. **Correcting** (D6, D7, D8). `RelationshipTypeChoices` is extracted and given its optional keep-the-withdrawn-one. `UpdateRelationship` changes nature, direction, status and dates, refusing a backwards date range and an ancestry loop.
5. **Removing and managing** (D5, D6). `DestroyRelationship` soft-deletes the row, leaving both contacts standing, behind `x-delete-action`. Manage is `x-data="{ manage: false }"` per group.
6. **Strings**. `php artisan monica:localize`, then translate what it reports in every configured locale.

## Testing

| Target | What it proves |
|---|---|
| `RelationshipTest` | The scope: both directions, deleted excluded, another vault excluded, the same pair linked twice answered twice. |
| `UpdateRelationshipTest` | Nature changed, direction swapped, ended and current again, dates written and cleared, a backwards range refused, an ancestry loop refused, a withdrawn nature kept on save, another account refused as not found, the journal written. |
| `DestroyRelationshipTest` | The row soft-deleted, both contacts standing including a partial one, gone from both sides, another account and another vault refused as not found, the journal written. |
| `RelationshipsBlockTest` | Grouping and order, empty groups dropped, counts, the role read from each side, a partial person's row, an ended row, the names-waiting count. |
| `RelationshipTypeChoicesTest` | Active types grouped in the account's order, a withdrawn type absent, and present when it is the one kept. |
| `ContactRelationshipControllerTest` | The tab answers, edit answers, update and destroy do what they say, validation failures come back, and another account's contact is not found on all four. |
| `RelationshipsTest` (view) | The copy, the group headings, the links, the Manage chip's `aria-pressed`, the warning before removal, and the names-waiting notice. No class strings, per `.claude/rules/testing.md`. |

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| API coverage deferred (X) | The approved spec puts it out of scope, and widening that is the user's call | Shipping it now is scope nobody agreed to. Raised in the completion report instead: a second deferral on a capability the principle names is a decision, not an oversight. |
| `spec.md` 165 lines against a 120 cap (XI) | Three user stories, 29 requirements, ten edge cases | Already cut from 295. Dropping a story drops a journey the feature ships. |
