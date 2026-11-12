# Implementation Plan: Tags on the Possession Page

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md)

## Summary

The tags under a possession's name become chips in their colour with a remove button, and an
"Add a tag" panel ticks, filters and creates tags without leaving the page. Two new actions, one
controller with three routes, a model method choosing the next colour, view model additions, one
Alpine component, and a confirmation built on `x-modal.warning`. No schema change, so no
`data-model.md`. Decisions in [research.md](research.md), routes in
[contracts/web.md](contracts/web.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | None new: `possession_possession_tag` exists (specification 092, 094) |
| Testing | PHPUnit: two actions, one model method, the view model, one controller |
| Scale | 100 tags in the account, 20 on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; WCAG 2.2 AA; every wording translated; one new JavaScript file (R5) |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Attach and detach are actions; creation reuses `CreatePossessionTag` (R2) |
| II Simplicity | Pass. No policy (the rule is stated once, in the actions), no flag, no new component (R5) |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. Route model binding, inline validation, one named route per line |
| V Localized | Pass. Counts through `trans_choice` with zero guarded (R7); removal warns first (R6) |
| VI Nothing changes | Pass. Nothing frozen is touched |
| VII Stable core | Pass. No new concept; tag and possession unchanged |
| VIII Nothing disappears | Pass. Taking a tag off keeps the tag; the confirmation says so |
| IX Accessible | Pass. Combobox and listbox contract, focus trap, colour never alone (R5, R6) |
| X API | Pass. API and MCP already set a possession's tags by name (R8) |
| XI Short record | Pass. All artifacts within caps |

Re-checked after design: unchanged.

## Project Structure

```text
app/Actions/AttachPossessionTag.php, DetachPossessionTag.php                     new
app/Models/Account.php                                                          nextPossessionTagColour()
app/Http/Controllers/App/Vaults/PossessionTagAttachmentController.php                     new: create, update, destroy
app/ViewModels/Vaults/PossessionViewModel.php                                   header tags with colour, tagOptions(), tagCount(), nextTagColour()
routes/web.php                                                                  three routes after possessions.featured.update
resources/js/possession-tag-picker.js, resources/js/app.js                      new component, registered
resources/views/app/vaults/possessions/_header.blade.php                        includes the partial below
resources/views/app/vaults/possessions/_header-tags.blade.php                   new: chips, removal modals, panel
tests/Unit/Actions/{Attach,Detach}PossessionTagTest.php                         new
tests/Unit/Models/AccountTest.php, tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php
tests/Feature/Controllers/App/Vaults/PossessionTagAttachmentControllerTest.php            new
spec/models/062-possession-in-progress.md, 064-possession-tag-in-progress.md    tags managed from the page
docs/portal/en/9-templates-and-customisation/16-possessions.md; docs/FEATURES.md; lang/*.json
```

## Order of work

1. `Account::nextPossessionTagColour()` and its test.
2. `AttachPossessionTag`, `DetachPossessionTag` and their tests.
3. View model additions and their tests.
4. Routes, controller, controller test; `RouteProtectionTest` passes unchanged.
5. `possession-tag-picker.js`, `_header-tags.blade.php`, `_header.blade.php`; check both the Overview
   and the Transactions section by hand, keyboard and screen reader, as quickstart says.
6. Model specs, guide, FEATURES.md, `monica:localize`, PHPStan, Pint, whole suite.

## Complexity Tracking

| Item | Why |
| --- | --- |
| New JavaScript file | A multi-select listbox with `aria-activedescendant` is too much for inline Alpine (R5) |
