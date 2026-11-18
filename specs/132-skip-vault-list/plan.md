# Implementation Plan: Skip the list of vaults

**Branch**: `2026-11-18-public-changelog` | **Date**: 2026-11-18 | **Spec**: [spec.md](spec.md)

## Summary

A preference on the person, switched on the Preferences screen and through `PUT api/v1/me/vault-list`, which makes `/vaults` redirect to the default vault and makes `VaultPolicy::delete` refuse the default vault while it is off. Decisions and their reasons are in [research.md](research.md), and the API shape is in [contracts/api.md](contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Alpine Ajax, Sanctum

**Storage**: one column, `users.shows_vault_list` boolean, default `true` (research R1). No data model file: there is nothing else to model.

**Testing**: PHPUnit, `RefreshDatabase`

**Target Platform**: web, JSON API

**Project Type**: web application

**Performance Goals**: the redirect adds one query for the default vault, only when the preference is off

**Constraints**: WCAG 2.2 AA on the two screens touched; every new string in every shipped locale

**Scale/Scope**: two screens, one policy ability, one new endpoint, two documentation pages

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | `UpdateVaultListVisibility` is new; `DestroyVault` asks the policy |
| II. Simplicity | one column, one action, one ability; no flag, no middleware |
| III. Tested | tests per layer, below |
| IV. Laravel conventions | migration `up()` only, `foreignIdFor` not concerned |
| V. Localized and safe | strings translated by Sonnet subagents; the deletion stays confirmed by its modal |
| VI. Nothing changes under anybody's feet | default `true`, so nobody's landing changes until they switch it |
| VIII. Nothing disappears silently | the refused control stays visible and says why |
| IX. Accessible | `aria-disabled` and `aria-describedby` on the refused button; the switch reuses `x-switch` inside its `label` |
| X. Through the API | `PUT me/vault-list`, `shows_vault_list` on `GET me`, 422 on `DELETE vaults/{vault}`. No MCP tool: research R6 |
| XI. Short record | spec 120 lines, plan within 100, no data model file |

Gate: passes. Re-checked after design: passes, with R6 to be stated in the pull request.

## Project Structure

```text
database/migrations/2026_11_18_000003_add_shows_vault_list_to_users_table.php
app/Models/User.php                                    property, cast, fillable
app/Actions/UpdateVaultListVisibility.php              new
app/Actions/DestroyVault.php                           authorize('delete')
app/Enums/UserActionEnum.php                           VaultListVisibilityUpdate
app/Policies/VaultPolicy.php                           delete()
app/Http/Controllers/App/Vaults/VaultController.php    index redirect, destroy catch
app/Http/Controllers/App/Settings/Account/Preferences/VaultListController.php   new, update
app/Http/Controllers/Api/MeVaultListController.php     new, update
app/Http/Controllers/Api/VaultController.php           destroy catch, 422
app/Http/Resources/UserResource.php                    shows_vault_list
app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php   showsVaultList()
app/ViewModels/Vaults/VaultSettingsViewModel.php       deletionRefusal()
resources/views/app/settings/account/preferences/index.blade.php       section Vaults
resources/views/app/settings/account/preferences/_vault-list.blade.php new
resources/views/app/vaults/settings.blade.php          refused button
routes/web.php, routes/api.php                         one route each
docs/portal/en/10-your-account-and-security/15-preferences.md
docs/portal/en/6-vaults/4-delete-a-vault.md
API reference (api-docs-writer skill), lang/*.json, What's new entry (changelog skill)
```

Tests, one layer each (`.claude/rules/testing.md`):

| File | Proves |
| --- | --- |
| `tests/Unit/Policies/VaultPolicyTest.php` | `delete`: permitted, refused for the default vault while off, permitted for another vault, refused with nobody signed in |
| `tests/Unit/Actions/DestroyVaultTest.php` | the refusal reaches the action and deletes nothing |
| `tests/Unit/Actions/UpdateVaultListVisibilityTest.php` | saves the value, logs the action, account check |
| `tests/Unit/ViewModels/Vaults/VaultSettingsViewModelTest.php` | `deletionRefusal()` null or the sentence |
| `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` | index redirects when off, shows the list with no vault, keeps the flash; destroy refusal reaches the form |
| `tests/Feature/Controllers/App/Settings/.../VaultListControllerTest.php` | update answers, validation |
| `tests/Feature/Controllers/Api/MeVaultListControllerTest.php` | update answers, 422; `VaultControllerTest` (API) gets the 422 on destroy |

**Structure Decision**: the existing Laravel tree; no new folder.

## Complexity Tracking

None.
