# Implementation Plan: Hide the main sections of a vault

**Branch**: `151-vault-section-visibility` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

## Summary

Three booleans on the vault say whether People, Things and Knowledge are shown. A new panel on Vault settings switches them with `x-switch`; the vault sidebar and the vault search screen honour them; the API vault update and a new `update-vault` MCP tool change them. Decisions are in [research.md](research.md), the schema in [data-model.md](data-model.md), the interfaces in [contracts/interfaces.md](contracts/interfaces.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)
**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax, Laravel MCP, Laravel Scout
**Storage**: the application database, three columns on `vaults`
**Testing**: PHPUnit, `composer test:parallel`
**Target Platform**: web application, JSON API, MCP server
**Project Type**: web application (single Laravel project)
**Performance Goals**: no new query on the sidebar: the three values are read from the vault already loaded
**Constraints**: API and MCP answers for records of a hidden section unchanged (FR-011)
**Scale/Scope**: one migration, one action, one web controller, one MCP tool, one panel, four documentation files

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: `UpdateVaultSectionVisibility` is used by the web controller and the API controller |
| II. Simplicity | Pass: three columns, no new abstraction |
| III. Tested | Pass: action, controller, API, sidebar, search and view model tests planned |
| IV. Laravel conventions | Pass: inline validation, named route per line, `up()` only migration |
| V. Localized | Pass: new copy through `__()`, translated by Sonnet subagents with `monica:localize` |
| VI. Nothing changes under anybody's feet | Pass: defaults keep every existing vault as it is |
| VII. Stable core | Pass: the three sections are fixed by the product, not user defined |
| VIII. Nothing disappears silently | Pass: hiding deletes nothing, direct addresses still open, the panel says so |
| IX. Accessible | Pass: native checkbox with `role="switch"`, labelled by the section name, visible focus |
| X. Everything goes through the API | Pass: API update and MCP tool in the same change |
| XI. Short record | Pass: every artifact within its cap |

No violation. Complexity Tracking is omitted.

## Project Structure

### Documentation (this feature)

```text
specs/151-vault-section-visibility/
├── spec.md  plan.md  research.md  data-model.md  quickstart.md
├── contracts/interfaces.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
database/migrations/2026_11_24_000000_add_section_visibility_to_vaults_table.php   new
app/Models/Vault.php                                    fillable, casts, docblock
app/Enums/UserActionEnum.php                            VaultSectionVisibilityUpdate
app/Actions/UpdateVaultSectionVisibility.php            new
app/Actions/SearchRecords.php                           respectsHiddenSections
app/Http/Controllers/App/Vaults/VaultSectionVisibilityController.php   new
app/Http/Controllers/Api/VaultController.php            three rules, calls the action
app/Http/Resources/VaultResource.php                    three attributes
app/ViewModels/Vaults/VaultSettingsViewModel.php        sections(), sectionVisibilityAction(), description
app/ViewModels/Vaults/SearchViewModel.php               passes respectsHiddenSections
app/View/Components/Vault/Sidebar.php                   skips counts of hidden groups
resources/views/components/vault/sidebar.blade.php      leaves out hidden groups
resources/views/app/vaults/settings.blade.php           What this vault shows panel
app/Mcp/Tools/UpdateVault.php                           new
app/Mcp/Servers/MonicaServer.php                        registers UpdateVault
routes/web.php                                          one PUT route
resources/docs/api/05-vaults.php                        fields and update parameters
resources/docs/api-changelog/2026-11-24.php             entry
docs/portal/en/6-vaults/3-vault-settings.md             new section
changelog/2026-11-24-<ulid>-vault-sections.md           What's new entry
lang/*.json                                             new strings in every locale

tests/Unit/Actions/UpdateVaultSectionVisibilityTest.php           new
tests/Unit/Actions/SearchRecordsTest.php                          hidden People respected only when asked
tests/Unit/ViewModels/Vaults/VaultSettingsViewModelTest.php       sections()
tests/Feature/Controllers/App/Vaults/VaultSectionVisibilityControllerTest.php   new
tests/Feature/Controllers/Api/VaultControllerTest.php             fields, partial update, 422
tests/Feature/Views/Components/SidebarTest.php                    hidden groups absent
```

**Structure Decision**: single Laravel project, files placed beside their siblings as listed above.

## Implementation notes

- The sidebar skips the possession and knowledge count queries for a hidden group, so hiding saves work rather than adding it.
- The panel posts all three values; the controller reads each with `$request->boolean()`, so an unchecked switch means hidden.
- The API controller calls the action only when one of the three keys is present, filling the others from the vault, so a rename does not write a second log entry.
- Rule `components.md`: the state word next to each switch is presentation bound to the checkbox with Alpine, not a component.
- `RouteProtectionTest` covers the new route without a change; `McpCatalogueTest` and the MCP walk cover the new tool.
