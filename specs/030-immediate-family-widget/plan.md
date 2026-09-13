# Implementation Plan: Immediate family block on a contact record

**Branch**: `2026-09-13-immediate-family-widget` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

Read back the relationships [029](../029-add-relationship-form/spec.md) taught the product to write. Two query scopes on `Contact` carry the selection, a small view model object beside `ContactViewModel` shapes it, and one panel on the contact overview draws it. No table, no column, no migration. Every decision and its rationale is in [research.md](research.md).

## Technical Context

| | |
|---|---|
| Language | PHP 8.4, Laravel 12 |
| Frontend | Blade, Tailwind 4, no JavaScript in this feature |
| Storage | The existing `relationships`, `relationship_types`, `contacts` and `field_values` tables, unchanged |
| Testing | PHPUnit, `RefreshDatabase`, the existing `parentage()` and `romantic()` relationship factory states |
| Scope | One block, one screen, two model scopes, around 200 lines of application code |
| Performance | Two queries for the two lists, one more for every age at once, none of them growing with the number of people listed |

No unknowns remain.

## Constitution Check

| Principle | Verdict |
|---|---|
| I. Actions hold the business logic | Pass. Nothing is written, so no action. The reading rules go in model scopes, not in a view model, so both surfaces share them. |
| II. Simplicity over abstraction | Pass. The person row repeats twice in one file and stays inline; `.claude/rules/components.md` sets the bar at three files. |
| III. Tested before merged | Pass. Scopes, block object, the screen, and the controller. See Testing below. |
| IV. Laravel conventions | Pass. Query scopes, a view model, an anonymous-component-free Blade block. |
| V. Localized and safe | Pass. Every string through `__()`, every count through `trans_choice()`, `monica:localize` run before done. |
| VI. Nothing changes underfoot | Pass. Read only, no layout that reflows, no destructive control. |
| VIII. Nothing disappears silently | Pass. Empty lists say what is not recorded rather than vanishing. |
| IX. Accessible to everybody | Pass. A list of links, headings that name each list, ages given an accessible name, nothing hover-only, no colour-carried meaning. |
| X. Everything goes through the API | **Deferred, justified.** No endpoint ships here. The selection lives in two `Contact` scopes an API controller calls unchanged, so adding it later is transport and a resource. See [research.md](research.md) D7. |
| XI. The written record is short | **One cap exceeded.** `spec.md` is 130 lines against 120. Noted for the pull request. `data-model.md` and `contracts/` are omitted, not empty: this feature adds no entity and exposes no interface, and XI requires omission rather than a placeholder. |

## Project Structure

```text
app/
├── Models/Contact.php                        # + scopeChildrenOf, scopeCurrentPartnersOf
└── ViewModels/Vaults/
    ├── ImmediateFamilyBlock.php              # new, beside ContactReminderBlock
    └── ContactViewModel.php                  # + immediateFamily()

resources/views/app/vaults/contacts/
├── show.blade.php                            # + @include in the right-hand column
└── _immediate-family.blade.php               # new, the block itself

lang/*.json                                   # the new strings, in every locale

tests/
├── Unit/Models/ContactTest.php               # + the two scopes
├── Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php
└── Feature/Views/Vaults/ImmediateFamilyTest.php
```

**Structure Decision**: the repository's existing layout, with no new directory. The block is a partial rather than a component because it is used once, on one screen.

## Approach

1. **Selection** ([research.md](research.md) D1, D2). Two scopes on `Contact`, each narrowing by account and vault then by `whereIn('id', …)` over `relationships`, with type membership resolved through a nested subquery on `system_behavior`. Ordered by `sort_key`. Both directions are already indexed.
2. **Ages** (D3). One `FieldValue` query for every listed contact, keyed on `field_key = 'birthday'` with `year_is_unknown = false`.
3. **Shaping** (D4). `ImmediateFamilyBlock` returns the two lists as rows of name, URL, avatar URL and age, plus the two counts and the URL of the relationship form.
4. **Drawing** (D5). One `x-panel`, two captioned lists, one explanatory body when both are empty.
5. **Wiring**. `@include` in the right-hand column of the overview, between Facts and the placeholder Relationships panel, which stays.
6. **Strings**. `php artisan monica:localize`, then translate what it reports in every configured locale.

## Testing

| Target | What it proves |
|---|---|
| `ContactTest` | The two scopes: direction, status, distinctness, partial and archived kept, deleted excluded, a user-invented type named Spouse excluded, a renamed supplied type still counted. The thirteen scenarios in `spec/models/026` land here. |
| `ImmediateFamilyBlockTest` | Counts, ordering, the nameless fallback, an age present, absent, and suppressed for an unknown year. |
| `ImmediateFamilyTest` (view) | The copy, the two headings, the links to each person, the link to the relationship form, and the both-empty body. No class strings, per `.claude/rules/testing.md`. |
| `ContactControllerTest` | The overview still answers, and another account's contact is still not found. |

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| API coverage deferred (X) | The user approved a spec that puts the endpoint out of scope | Shipping the endpoint now widens an approved boundary. The rules still sit in shared scopes, which is what the principle's own escape clause asks for. |
| `spec.md` 130 lines against a 120 cap (XI) | The four-story template carries a fixed overhead the feature cannot shed | Cutting a user story would drop coverage of a real journey. Every restatement of `spec/models/026` was already removed. |
