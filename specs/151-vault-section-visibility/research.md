# Research: Hide the main sections of a vault

## R1. Where the setting is stored

- **Decision**: three boolean columns on `vaults`: `shows_people`, `shows_things`, `shows_knowledge`, default `true`.
- **Rationale**: three fixed sections, fixed by the product (constitution VII), never user defined. The naming follows `users.shows_vault_list` (`UpdateVaultListVisibility`). Defaulting to `true` makes every existing vault unchanged without a backfill (FR-002).
- **Alternatives**: one JSON column of hidden keys (a second shape to validate and cast for three values); a `vault_sections` table (a table for three fixed rows).

## R2. One action, separate from `UpdateVault`

- **Decision**: a new action `UpdateVaultSectionVisibility(user, vault, showsPeople, showsThings, showsKnowledge)` with its own `UserActionEnum::VaultSectionVisibilityUpdate`, recording before and after values in `changes`.
- **Rationale**: the screen saves this panel on its own, as it does for the default template (`SetVaultDefaultTemplate`). `UpdateVault` takes a required name and replaces description and colour, so folding the three values into it would force the panel to resend them. One operation, one action (constitution I).
- **Alternatives**: three nullable parameters on `UpdateVault` (mixes "keep" and "replace" semantics in one constructor).

## R3. The API carries it on the existing vault endpoints

- **Decision**: `VaultResource` gains the three attributes. `PUT /vaults/{vault}` accepts each as `sometimes, boolean`; when at least one is sent, the controller calls the new action, taking the unsent values from the vault. `POST /vaults` does not accept them.
- **Rationale**: the controller already chains `SetVaultDefaultTemplate` and `SetDefaultVault` after `UpdateVault`, so this is the existing pattern. A new vault always starts with every section shown, so creation needs no input.
- **Alternatives**: a dedicated `PUT /vaults/{vault}/sections` endpoint (a second address for a property of the vault).

## R4. MCP: a new `UpdateVault` tool

- **Decision**: `app/Mcp/Tools/UpdateVault.php` mirrors `api.v1.vaults.update`, looks the vault up with `vault()`, then `answerThroughApi()`. Its schema exposes every field the endpoint accepts: `name`, `description`, `colour`, `is_default`, `structure_version_id` and the three values. Registered in `MonicaServer`; annotations per `docs/MCP_ANNOTATIONS.md`.
- **Rationale**: `.claude/rules/mcp.md` forbids a tool holding a rule of its own, so the tool can only be the whole update. `ListVaults` already shows the values once the resource carries them.
- **Alternatives**: a tool exposing only the three values (it would still call the full update, and its schema would lie by omission).

## R5. What hiding removes from the screens

| Section | Sidebar group removed | Search groups removed |
| --- | --- | --- |
| People | People, entity types, Groups | `people`, every `entity-type-*` |
| Things | Things (possessions) | none: possessions are not searched |
| Knowledge | Knowledge | none: knowledge is not searched |

- **Decision**: `SearchRecords` gains `respectsHiddenSections = false`. `SearchViewModel` passes `true`; `Api\SearchController` leaves the default, so the API is unchanged (FR-011). When set, the people and entity type groups search only the vaults in scope whose `shows_people` is true; across the account this skips each vault that hides People. Life events stay searched in every case: they belong to no sidebar group.
- **Rationale**: filtering before the search, not after, avoids running queries whose results are thrown away, and keeps paging correct.
- **Alternatives**: filtering groups in the view model after the action ran (wasted queries, and wrong for a vault scope mixing vaults across the account).

## R6. Direct addresses keep working

- **Decision**: no route, middleware or controller refuses anything because a section is hidden. `Vault\Sidebar` and the sidebar Blade read the three values; nothing else does.
- **Rationale**: spec assumption 2. Reminders, timeline entries and notifications link to contacts directly.

## R7. The switch and the panel

- **Decision**: `x-switch` inside a `<label>` per row, the row built as in `notifications/_channel.blade.php`; the panel is an `x-panel` with its own `x-form` and footer button, as the default template panel is. The state word (**Shown** or **Hidden**) is bound to the checkbox with Alpine so it follows the switch before saving, and is `aria-hidden`, since the switch already announces its state.
- **Rationale**: the user asked for the design system's existing toggle; `components.md` forbids re-drawing one. Unchecked checkboxes are not submitted, so the controller reads each value with `$request->boolean()`.

## R8. Documentation and changelogs

- Help centre: `docs/portal/en/6-vaults/3-vault-settings.md` gains a section and a screenshot label update.
- API reference: `resources/docs/api/05-vaults.php`, plus an entry in `resources/docs/api-changelog/2026-11-24.php` (`Added`, three fields; `Changed`, the update endpoint).
- MCP: `docs/portal/en/10-your-account-and-security/12-connected-assistants.md` only if it enumerates tools; otherwise the catalogue is generated and needs nothing.
- Product changelog: one entry in `changelog/` through the `changelog` skill.
