# Implementation Plan: Bulk actions on the contact list

**Branch**: `2026-11-25-contact-bulk-actions` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/162-contact-bulk-actions/spec.md`

## Summary

The contact list gains a checkbox per row and one for the page. A selection swaps the filters for a
toolbar, drawn from the design `People.dc.html`, offering "Change category" and "Delete". Two new
actions repeat the existing single ones per person, so a bulk change can never differ from doing it one
by one (R1, R2). The delete confirmation is rendered by the server (R4); the category change ends on the
groups list's notice with an Undo form carrying the previous categories (R3). The same two endpoints
exist in the API v1, with two MCP tools over them (R7). Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Alpine.js, Alpine Ajax, Tailwind CSS, Laravel MCP, Sanctum; no new dependency

**Storage**: no migration, no new table

**Testing**: PHPUnit (`tests/Unit/Actions`, `tests/Unit/ViewModels`, `tests/Feature/Controllers`, `tests/Feature/Views`)

**Target Platform**: web application, the API v1 and the MCP server

**Project Type**: web application (Laravel monolith)

**Performance Goals**: one request per bulk action, at most 500 people (R6)

**Constraints**: WCAG 2.2 AA, reduced motion respected, every wording translated with its count

**Scale/Scope**: two actions, three controllers, five routes, two MCP tools, one Alpine component, one screen

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. `DestroyContacts`, `ChangeContactCategories`; controllers validate and delegate |
| II. Simplicity | Pass. Both actions loop over existing ones; Undo reuses the change endpoint (R3) |
| III. Tested before merged | Pass. Tests below |
| IV. Laravel conventions | Pass. Named routes, camelCase, inline validation, `x-target` |
| V. Localized and safe | Pass. Counts through `trans_choice()` and `CountedPhraseWordings`; confirmation before deleting |
| VI. Nothing changes under anybody's feet | Pass. Nothing changes without an explicit choice; no structure is touched |
| VII. Stable core | Pass. Categories stay values of a field; no column added |
| VIII. Nothing disappears silently | Pass. Who was left unchanged is said, with why; deletion deactivates reminders as today |
| IX. Accessible | Pass. Named checkboxes, mixed state, `role="toolbar"`, `x-actions-menu`, `x-modal.warning`, Escape clears |
| X. Everything through the API | Pass. Two endpoints, two MCP tools through `answerThroughApi()` |
| XI. Short record | Pass. No data model or quickstart: no schema, and the tests are the walkthrough |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/162-contact-bulk-actions/
├── plan.md
├── research.md
├── contracts/
│   ├── api.md
│   └── web.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Actions/DestroyContacts.php                                  # new (R1)
app/Actions/ChangeContactCategories.php                          # new (R1, R2)
app/Http/Controllers/App/Vaults/ContactDeletionController.php    # new, show and destroy
app/Http/Controllers/App/Vaults/ContactCategoryController.php    # new, update
app/Http/Controllers/Api/ContactDeletionController.php           # new
app/Http/Controllers/Api/ContactCategoryController.php           # new
app/Mcp/Tools/SetContactCategories.php                           # new
app/Mcp/Tools/DeleteContacts.php                                 # new
app/Mcp/Servers/MonicaServer.php                                 # registers the two tools
app/ViewModels/Vaults/ContactsViewModel.php                      # row uuid, menu categories, notice, wordings
app/ViewModels/Vaults/ContactDeletionViewModel.php               # new, the confirmation sentences
routes/web.php, routes/api.php                                   # contracts/web.md, contracts/api.md
resources/js/contact-selection.js                                # new Alpine component, registered in app.js
resources/views/app/vaults/contacts/index.blade.php              # toolbar, heading checkbox, notice
resources/views/app/vaults/contacts/_groups.blade.php            # row checkbox, row toggles while selecting
resources/views/app/vaults/contacts/_deletion.blade.php          # new, the confirmation fragment
resources/docs/api/…, resources/docs/api-changelog/2026-11-25.php
docs/portal/en/3-people/4-find-people.md                         # selecting several people
changelog/2026-11-25-<ulid>-contact-bulk-actions.md
lang/*.json                                                      # translated by Sonnet subagents
```

Tests: `tests/Unit/Actions/DestroyContactsTest.php`, `ChangeContactCategoriesTest.php`,
`tests/Unit/ViewModels/Vaults/ContactDeletionViewModelTest.php`, additions to `ContactsViewModelTest`,
one feature test per controller (web and API), one MCP tool test only where it adds to the lookup.

## Complexity Tracking

None.
