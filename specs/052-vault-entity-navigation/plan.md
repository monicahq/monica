# Implementation Plan: Entity types in the vault navigation

**Branch**: `2026-09-25-vault-entity-navigation` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

A scope on `EntityType` decides which types a vault shows (R1). The vault sidebar component lists
them under People (R2), and a new route opens a read-only list of one type's records in the vault
(R3, R4), answering not found for a type the scope leaves out. No data changes, so there is no
`data-model.md`, no `contracts/` and no `quickstart.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS

**Storage**: reads `entity_types`, `vault_default_templates`, `structure_versions`, `templates`,
`entities`

**Testing**: PHPUnit: the scope, the sidebar component, the view model, the controller

**Target Platform**: every vault screen (sidebar) and one new screen

**Project Type**: server rendered web application

**Performance Goals**: one query added to each vault page

**Constraints**: WCAG 2.2 AA; every string in every locale; nothing specific to a type

**Scale/Scope**: 1 model scope, 1 component class, 1 controller, 1 view model, 2 views, 1 route,
about 4 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Nothing written; the rule is a model scope read by the component and the controller |
| II. Simplicity over abstraction | Pass | One scope, no helper class, no new component |
| III. Tested before merged | Pass | Tests table below |
| IV. Laravel conventions | Pass | Local scope, one named route, thin controller |
| V. Localized and safe | Pass | Four strings; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | Read only |
| VII. Stable core | Pass | Entities stay built on templates |
| VIII. Nothing disappears silently | Pass | A turned-off type keeps its entry while records remain, and says why it cannot grow |
| IX. Accessible to everybody | Pass | `aria-current="page"` on the entry; the notice is text; the archived mark is a word, not a colour |
| X. Everything goes through the API | Deviation | No entity endpoint yet (R6); see Complexity Tracking |
| XI. The written record is short | Pass | spec 115, plan under 100, research under 80 |

Re-check after Phase 1: unchanged.

## Project Structure

```text
specs/052-vault-entity-navigation/  spec  plan  research  checklists/
```

```text
app/Models/EntityType.php                               # scopeVisibleInVault
app/View/Components/Vault/Sidebar.php                   # entityTypes
resources/views/components/vault/sidebar.blade.php      # entries under People
routes/web.php                                          # entities.index
app/Http/Controllers/App/Vaults/EntityController.php    # new: index
app/ViewModels/Vaults/EntitiesViewModel.php             # new
resources/views/app/vaults/entities/index.blade.php     # new
tests/Unit/Models/EntityTypeTest.php                    # scope
tests/Feature/Views/Components/SidebarTest.php          # entries, order, current
tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php  # new
tests/Feature/Controllers/App/Vaults/EntityControllerTest.php  # new
lang/*.json                                             # through monica:localize
```

**Structure Decision**: the existing layout; the screen mirrors `contacts/index` in its shell.

## Tests

| Layer | Proves |
| --- | --- |
| Scope | shown with an active published default; shown by a record alone when turned off or without a template; hidden with neither; an archived record counts; a deleted one does not; a retired or inactive template does not count; each vault decided on its own; another account's types never |
| Sidebar | entries under People in settings order with plural name; the current entry has `aria-current="page"`; the entry's address |
| View model | records of this vault and type only, ordered by name, archived marked; `isActive` |
| Controller | the screen renders its name; the notice for a turned-off type; the empty state; not found for a hidden type and for the wrong vault |

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| X: the list has no API endpoint | Scope was cut to navigation and a read-only list on 2026-09-25 | Adding one list endpoint now would ship a partial entity API ahead of the lifecycle it belongs to |
