# Implementation Plan: Edit relationship form

**Branch**: `2026-09-23-edit-relationship-form` | **Date**: 2026-09-23 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/045-edit-relationship-form/spec.md`

## Summary

The edit screen swaps its type select and direction radios for one select of sentences, its status
radios for the 1c checkbox, and gains a block for the other side that renames a partial contact.
`UpdateRelationship` learns which contact the edit came from, clears the end date of a relationship
still going, and renames a partial other side through `UpdateContactName` inside its transaction.
Decisions are in [research.md](research.md); the posted fields and the screen states are in
[contracts/edit-relationship-screen.md](contracts/edit-relationship-screen.md). There is no schema
change, so there is no `data-model.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js

**Storage**: Eloquent; no migration

**Testing**: PHPUnit: the action, the edit view model, the controller

**Target Platform**: web application, the logged in vault screens

**Project Type**: server rendered web application

**Performance Goals**: no added query beyond loading the two sides, already loaded for the title

**Constraints**: WCAG 2.2 AA; every string in every configured locale; works before Alpine starts
(the server clears a hidden end date and renames only on `mode=known`)

**Scale/Scope**: one action, one view model, one controller, one view, one model method

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Clearing the end date, finding the other side and the rename live in `UpdateRelationship`; the controller maps fields |
| II. Simplicity over abstraction | Pass | Reuses `UpdateContactName`, `RelationshipTypeChoices`, `x-checkbox`, `x-option-row`, `x-contact-avatar`; inline Alpine, no new script |
| III. Tested before merged | Pass | Action, view model and controller tests, one layer each |
| IV. Laravel conventions | Pass | Inline validation, named route unchanged, `Model::query()` |
| V. Localized and safe | Pass | Four new strings; sentences reuse translated phrases; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | Only what the reader edits changes; saving unchanged is a no-op (SC-003) |
| VIII. Nothing disappears silently | Pass | Unticking clears an end date the reader can see go; a name change is journalled |
| IX. Accessible to everybody | Pass | Fieldset and legend for the radios, `aria-describedby` for the line, fields revealed in reading order |
| X. Everything goes through the API | Gap, not widened | See Complexity Tracking |
| XI. The written record is short | Pass | spec 120, plan under 100; `data-model.md` omitted |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/045-edit-relationship-form/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/
│   └── edit-relationship-screen.md
└── tasks.md                             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Models/Relationship.php                         # involves(Contact)
app/Actions/UpdateRelationship.php                  # contact, contactHoldsRoleA, rename, clears end date
app/ViewModels/Vaults/RelationshipEditViewModel.php # sentenceGroups(), otherSide(), otherState(), values()
app/Http/Controllers/App/Vaults/ContactRelationshipController.php  # edit/update: 404, new fields
resources/views/app/vaults/contacts/relationships/edit.blade.php   # design 2a
tests/Unit/Actions/UpdateRelationshipTest.php
tests/Unit/ViewModels/Vaults/RelationshipEditViewModelTest.php     # new
tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php
lang/*.json                                          # through monica:localize
```

**Structure Decision**: the existing layout. The screen’s data stays in its view model, and the rule
changes stay in the one action the screen calls.

## Complexity Tracking

| Item | Why | Simpler option rejected |
| --- | --- | --- |
| No API for editing a contact’s relationships (principle X) | Already missing before this feature; this change adds no capability the API lacked more than today’s screen | Building the endpoint here doubles the scope; it belongs to its own feature through `api-writer` |
