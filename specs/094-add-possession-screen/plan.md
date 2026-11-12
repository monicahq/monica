# Implementation Plan: Add a Possession Screen

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

The first possession screen of a vault: a form adding a possession with its type fields, location,
acquisition, tags and featuring, an inert photo area, and a link to it from All possessions. The
record gains five acquisition columns and a link table to tags; `CreatePossession` and
`UpdatePossession` write everything in one transaction; the API and the MCP tools carry the new data;
deleting a tag says how many possessions lose it. Principle VII is amended to describe the
possession's new shape. Decisions in [research.md](research.md), schema, actions and view models in
[data-model.md](data-model.md), routes, form, API and tools in
[contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax, Sanctum, Laravel MCP |
| Storage | The possessions migration edited, one new link table; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: enum, actions, helper, model accessor, view models, web and API controllers, tools |
| Scale | A few dozen types and tags per account, loaded whole; contacts searched, never listed whole |
| Constraints | Nothing across accounts or vaults; every wording translated; WCAG 2.2 AA; JavaScript required, as the life event form |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. One action writes the possession, its values and tags; web, API and tools call it |
| II Simplicity | Pass. No policy, no flag; one helper used by two actions (R6); one new JS component per widget |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. Migration edited until production as the skill says; inline validation; one route per line |
| V Localized | Pass. Enum wordings, counts through `trans_choice()`, currency from the locale (R4) |
| VI Nothing changes | Pass. Existing possessions gain empty columns; the API `PUT` full replace now also clears tags, documented |
| VII Stable core | **Amended.** A possession's description gains acquisition and tags (R2) |
| VIII Nothing disappears | Pass. A deleted giver returns on restore (R3); tag deletion is confirmed and states its reach |
| IX Accessible | Pass. Combobox and listbox pattern, labelled chips, radios for rating and acquisition (R7, R8) |
| X API | Pass. Every new datum on the endpoints and tools |
| XI Short record | Over by a few lines: spec 124/120, research 82/80, data model 63/60, contracts 66/60; the PR says so |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII, Sync Impact Report, 2.11.0
spec/models/062-possession-in-progress.md, 064-possession-tag-in-progress.md
app/Enums/PossessionAcquisitionEnum.php
database/migrations/2026_10_26_000002_create_possessions_table.php     five columns
database/migrations/2026_10_26_000006_create_possession_possession_tag_table.php
database/factories/PossessionFactory.php        states for acquisition
app/Models/Possession.php, PossessionTag.php    columns, casts, acquiredFrom(), tags(), possessions()
app/Helpers/PossessionTagNames.php
app/Actions/CreatePossession.php, UpdatePossession.php, DestroyPossessionTag.php (docblock)
app/ViewModels/Vaults/PossessionFormViewModel.php, PossessionTypeFieldsBlock.php,
  PossessionContactCandidatesViewModel.php
app/ViewModels/Settings/Account/PossessionTags/PossessionTagsViewModel.php   count per tag
app/Http/Controllers/App/Vaults/PossessionController.php (new, create),
  PossessionTypeFieldController.php, PossessionContactCandidateController.php
routes/web.php                                  four routes
resources/views/app/vaults/possessions/index.blade.php   the "Add a possession" action
resources/views/app/vaults/possessions/form.blade.php, _type-picker, _type-fields, _field,
  _acquisition, _tags, _photos, contact-candidates
resources/views/app/settings/account/possession-tags/_row.blade.php   the count in the confirmation
resources/js/possession-type-picker.js, tag-input.js; registered in resources/js/app.js
app/Http/Controllers/Api/Possessions/PossessionController.php; app/Http/Resources/PossessionResource.php
app/Mcp/Tools/CreatePossession.php, UpdatePossession.php
resources/docs/api/27-possessions.php
docs/portal/en/9-templates-and-customisation/16-possessions.md (new guide), docs/FEATURES.md
lang/*.json                                     via `php artisan monica:localize`
tests/...                                       as quickstart lists
```

## Order of work

1. Amendment and model files; enum; migrations; models, factory states.
2. Helper and both actions with their tests; the tag deletion docblock and its test.
3. API rules, resource, tools, docs, with tests.
4. View models with tests; the three controllers and routes with tests.
5. Views and the two JS components; the index action; the tag row count.
6. Guide page, FEATURES.md, translations, PHPStan, Pint, whole suite, a look at the screen.

## Spec amendments made while planning

- Cut to the 120 line cap of principle XI (it was 288).
- Types show a colour, not an icon: possession types have none.
- The giver is searched, not listed; a deleted giver disappears and returns on restore (contacts soft
  delete), so the contact's confirmation is unchanged.
- The form requires JavaScript, as the life event form does; the no-JavaScript edge case is gone.

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | The possession's described shape grows | Leaving it stale breaks the governance rule on one description |
| Field value checks repeated in `CreatePossession` | One transaction, one journal entry (R5) | Calling `UpdatePossessionFieldValues` journals twice and is not rolled back with `after_commit` false |
