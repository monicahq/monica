# Implementation Plan: Make a full contact partial again

**Branch**: `2026-11-23-partial-contact-promotion` | **Date**: 2026-11-23 | **Spec**: [spec.md](spec.md)

## Summary

A full contact becomes partial from a panel on its edit page (design 2a), after a confirmation that
names the consequences, phones and birthday calendar included (design 2b). The way back is the band
built earlier on this branch. Both directions go through one `PUT .../partial` route on the web and
on the API, as the favourite mark does, and one MCP tool. A new action `DemoteFullContact` sits
beside `PromotePartialContact`; the rule that the account holder's contact stays full becomes the
`demote` ability of `ContactPolicy`. The merge, which today makes a contact full without asking the
plan, asks it under the account lock when it would add a counted contact. Decisions are in
[research.md](research.md); the inventory of what reads the partial state, the state machine and the
defects are in [analysis.md](analysis.md).

## Technical Context

**Language/Version**: PHP 8.5, current Laravel, Blade, Tailwind CSS, Alpine.js

**Primary Dependencies**: none added

**Storage**: no schema change ([data-model.md](data-model.md))

**Testing**: PHPUnit

**Target Platform**: the web application, its JSON API, its MCP server; CardDAV unchanged (R9)

**Project Type**: web application

**Performance Goals**: one extra query on the edit page (does any relationship reach the contact), an
`exists` on an indexed column

**Constraints**: WCAG 2.2 AA; the plan's ceiling must hold under concurrent requests (FR-014)

**Scale/Scope**: 1 action added, 2 changed, 1 policy ability, 2 controllers (1 replacing the
promotion one), 1 MCP tool, 2 routes (1 replacing), 2 views, 1 view model

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass. `DemoteFullContact` holds the rules; controllers pick the action and shape the answer (R2). |
| II. Simplicity | Pass. One route per surface for a two valued state (R1); no lock where nothing can pass the ceiling (R5). |
| III. Tested | Pass, see Tests. |
| IV. Laravel conventions | Pass. Inline validation, named routes in camelCase, class array notation. |
| V. Localised | New copy through `monica:localize`, translated by Sonnet subagents. No counted phrase is added. |
| VI. Nothing changes under anybody's feet | Pass. Nothing written by the person is changed in either direction (FR-003). |
| VII. Stable core | Pass. |
| VIII. Nothing disappears silently | The contact leaves phones and the birthday calendar. The confirmation says so (FR-002), and the way back exists (FR-006). |
| IX. Accessible | The refused panel keeps its button with `aria-disabled` and `aria-describedby`; the dialog is the existing `x-modal.form`. Reviewed by the `accessibility-reviewer` agent. |
| X. Through the API | Pass. `PUT .../partial` on the API for both directions, documented, mirrored by `SetContactPartial`. The promotion gets its API in the same change. |
| XI. Short written record | Pass. |

Product invariant on the Me contact: kept, by the `demote` ability (R3).

Gate passes. Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Actions/DemoteFullContact.php` | New. Validate the account, `authorize('demote', ...)`, refuse already partial and archived, set the flag, journal `ContactDemoted` (R2, R3) |
| `app/Actions/PromotePartialContact.php` | Unchanged, apart from the route it is reached by |
| `app/Actions/MergeContacts.php` | When both contacts are partial, lock the account row and `authorize('create', Contact::class)` inside the transaction, before `stopBeingPartial()` (R4) |
| `app/Policies/ContactPolicy.php` | `demote(User, Contact)`: deny the account holder's contact (R3) |
| `app/Enums/UserActionEnum.php` | `ContactDemoted` |
| `app/Http/Controllers/App/Vaults/ContactPartialController.php` | New, replaces `ContactPromotionController`. `update`: validate `partial`, call one action, flash or `back()->withErrors(['partial' => ...])` (contracts) |
| `app/Http/Controllers/App/Vaults/ContactPromotionController.php` | Deleted |
| `app/Http/Controllers/Api/ContactPartialController.php` | New. `update`, same as the favourite controller, 422 on refusal |
| `app/Http/Controllers/App/Vaults/ContactMergeController.php`, `app/Http/Controllers/Api/ContactMergeController.php` | Catch `AuthorizationException` like their other refusals (R4) |
| `app/Mcp/Tools/SetContactPartial.php` | New, registered in `app/Mcp/Servers/MonicaServer.php` |
| `app/Mcp/Tools/CreateContact.php` | `is_partial` description corrected (R8) |
| `app/ViewModels/Vaults/ContactEditViewModel.php` | `demotion()`: null for a partial contact, else the copy, the URL, the refusal, and whether a relationship reaches the contact (R6, R7) |
| `app/ViewModels/Vaults/ContactViewModel.php` | `promotion()['url']` points at the new route |
| `app/ViewModels/Vaults/ContactsViewModel.php`, `app/Http/Controllers/App/Vaults/ContactController.php` | The "until they are more than a first name" sentences corrected (R8) |
| `routes/web.php` | `contacts.partial.update` replaces `contacts.promotion.create` |
| `routes/api.php` | `api.v1.contacts.partial.update` |
| `resources/views/app/vaults/contacts/edit.blade.php` | The panel above `x-delete-section`, through a new `_demotion.blade.php` partial (R7) |
| `resources/views/app/vaults/contacts/_promotion.blade.php` | The form sends `PUT` with `partial=0` |
| `resources/docs/api/06-contacts.php`, `resources/docs/api-changelog/2026-11-23.php` | The endpoint, and the merge's new refusal |
| `lang/*.json` | Through `monica:localize` only |
| `changelog/` | The existing entry of this branch rewritten to cover both directions |

## Tests

| File | Proves |
| --- | --- |
| `tests/Unit/Actions/DemoteFullContactTest.php` | New. Demotes and journals; refuses another account, the account holder's contact, a partial one, an archived one; nothing else on the contact changes |
| `tests/Unit/Actions/PromotePartialContactTest.php` | Unchanged |
| `tests/Unit/Actions/MergeContactsTest.php` | Merging two partial contacts at the ceiling is refused and nothing moves; below the ceiling, the result is full |
| `tests/Unit/Policies/ContactPolicyTest.php` | `demote`: permitted, refused for the account holder, refused with nobody signed in |
| `tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php` | `demotion()`: present for a full contact, null for a partial one, refused for the account holder, the reachability sentence only without a relationship |
| `tests/Feature/Controllers/App/Vaults/ContactPartialControllerTest.php` | Replaces `ContactPromotionControllerTest`. Both directions answer; validation; one refusal reaches the form; wrong vault is 404; the edit page renders the panel |
| `tests/Feature/Controllers/Api/ContactPartialControllerTest.php` | New. Both directions, the JSON structure, one refusal as 422, validation |
| `tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php`, `tests/Feature/Controllers/Api/ContactMergeControllerTest.php` | The ceiling refusal reaches the form and the API |
| `tests/Unit/Http/Dav/CollectionChangesTest.php` | A demoted contact lands in the removed set (R9) |

## Project Structure

### Documentation (this feature)

```text
specs/150-partial-contact-demotion/
├── spec.md
├── analysis.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/routes.md
└── checklists/requirements.md
```

**Structure Decision**: the existing Laravel layout. No new folder.

## Complexity Tracking

No violation to justify.
