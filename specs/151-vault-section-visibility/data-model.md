# Data model: Hide the main sections of a vault

## `vaults` (three new columns)

| Column | Type | Default | Meaning |
| --- | --- | --- | --- |
| `shows_people` | boolean, not null | `true` | The People group (contacts, entity types, groups) appears in the sidebar and in vault search |
| `shows_things` | boolean, not null | `true` | The Things group (possessions) appears in the sidebar |
| `shows_knowledge` | boolean, not null | `true` | The Knowledge group appears in the sidebar |

- Migration `2026_11_24_000000_add_section_visibility_to_vaults_table.php`, `up()` only, each column with a `->comment()` as `shows_vault_list` has.
- `Vault`: the three added to `#[Fillable]` and cast to `boolean`; documented in the model docblock.
- `VaultFactory`: no state needed, the defaults are the common case.

## `UserActionEnum::VaultSectionVisibilityUpdate`

- Value `vault_section_visibility_updated`, label "Changed which sections the vault :name shows".
- Written with `loggable: $vault` and `changes` holding only the columns that changed, `before` and `after` as booleans.

## Validation

| Surface | Rule |
| --- | --- |
| Web `PUT vaults/{vault}/settings/sections` | none beyond the bound vault: each value is `$request->boolean()`, absent meaning off |
| API `PUT vaults/{vault}` | each of the three `['sometimes', 'boolean']` |
