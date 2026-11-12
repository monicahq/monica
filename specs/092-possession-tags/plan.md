# Implementation Plan: Possession Tags

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

One table (`possession_tags`), three actions, one settings screen edited in place, five API
endpoints, five MCP tools, and their documentation. Tags are not put on possessions yet. Principle VII
is amended to name Possession tag. Decisions in [research.md](research.md), the table, actions and
view model in [data-model.md](data-model.md), routes, screen contract, endpoints and tools in
[contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax, Sanctum, Laravel MCP |
| Storage | One new table, one migration; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: every action, the view model, the settings and API controllers, every tool, the screen's contract |
| Scale | Tens of tags per account; the list is loaded whole and sorted in memory |
| Constraints | Nothing across accounts; every wording translated; WCAG 2.2 AA on the screen; new migration only |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Every write in an action; screen, API and tools call the same three |
| II Simplicity | Pass. No index for uniqueness, no policy, no flag, no new component (R3, R6, R10) |
| III Tested | Pass. Each layer proves its own rules (quickstart) |
| IV Laravel | Pass. `up()` only, `foreignIdFor()->constrained()`, inline validation, one named route per line |
| V Localized | Pass. Every wording through `monica:localize`; the count line through `trans_choice()` |
| VI Nothing changes | Pass. Nothing existing changes shape |
| VII Stable core | **Amended.** Possession tag joins the fixed concepts (R1) |
| VIII Nothing disappears | Pass. A deletion is confirmed and journaled; nothing else holds a tag yet |
| IX Accessible | Pass. Native radios for the colour, the preview announced, refusals announced, colour never alone (R6) |
| X API | Pass. Every operation of the screen is an endpoint and a tool |
| XI Short record | Pass |
| Invariant: vault is a compartment | Pass. A tag belongs to the account, as a location does |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md                 VII names Possession tag, Sync Impact Report, 2.9.0
spec/spec.md, spec/models/064-possession-tag-in-progress.md
app/Enums/UserActionEnum.php                    three cases
database/migrations/2026_10_26_000003_create_possession_tags_table.php
database/factories/PossessionTagFactory.php
app/Models/PossessionTag.php; Account gains possessionTags()
app/Actions/CreatePossessionTag.php, UpdatePossessionTag.php, DestroyPossessionTag.php
app/ViewModels/Settings/Account/PossessionTags/PossessionTagsViewModel.php
app/Http/Controllers/App/Settings/Account/PossessionTags/PossessionTagController.php
resources/views/app/settings/account/possession-tags/index.blade.php, _row.blade.php, _colours.blade.php
resources/views/components/settings/sidebar.blade.php   the entry, after Possession types (R8)
routes/web.php                                  four routes
app/Http/Resources/PossessionTagResource.php
app/Http/Controllers/Api/PossessionTags/PossessionTagController.php
routes/api.php                                  five routes
app/Mcp/Tools/                                  five tools; MirrorsAnApiCapability::possessionTag(); MonicaServer
app/ViewModels/Docs/McpDocsViewModel.php        two groups
resources/docs/api/28-possession-tags.php
docs/portal/en/9-templates-and-customisation/14-possession-tags.md
docs/FEATURES.md
lang/*.json                                     via `php artisan monica:localize`
tests/Unit/Actions, tests/Unit/ViewModels/Settings/Account/PossessionTags, tests/Unit/Mcp/Tools,
tests/Feature/Controllers/App/Settings/Account/PossessionTags, tests/Feature/Controllers/Api/PossessionTags
```

## Order of work

1. Amendment and model file; migration, model, factory, `Account::possessionTags()`.
2. The three actions and the journal cases, with their tests.
3. View model, settings controller, routes, the three views, the sidebar entry, with their tests.
4. Resource, API controller, routes, controller tests.
5. Tools, registration, docs groups, tool tests.
6. API reference, guide page, FEATURES.md, translations, PHPStan, Pint, whole suite, a look at the
   screen in light and dark.

## Spec amendments made while planning

- Edge cases: "two people adding the same name at once" removed; an account has one person (R3).
- US3.3: "Keep it" becomes cancelling the confirmation, which is what `x-delete-action` offers (R7).

## Departures from the design

| Design | Plan | Why |
| --- | --- | --- |
| "Personalise" heading in the sidebar | The existing Possessions group | The group exists and holds the siblings |
| `tag` icon | `hash` | Possession types already uses `tag` in the same group (R8) |
| Ochre, Brick, Rose, Graphite; Teal by default | Amber, Rust, Plum, Slate; Blue by default | One colour, one name; the type's default (R2) |
| `role="radio"` buttons | Native radio inputs | Keyboard and announcement for free (R6) |
| Inline red confirmation band | `x-modal.warning` card | The project's way to ask before destroying something (R7) |
| Usage per row, "in use", edit note, delete consequence | Left out | Possessions cannot carry tags yet (spec clarification) |

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| Principle VII amended | A tag is a record of the account with a fixed shape | A value list belongs to templates, which possessions do not use |
