# Data Model: Possessions

Migration `2026_10_26_000002_create_possessions_table.php`, `up()` only.

## possessions

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | id, uuid unique | |
| account_id | `foreignIdFor(Account)` cascade | the vault's account |
| vault_id | `foreignIdFor(Vault)` cascade | |
| possession_type_id | `foreignIdFor(PossessionType)` cascade | same account; never changes (FR-005) |
| current_location_id | `foreignIdFor(Location)` nullable, null on delete | same account |
| name | string 255 | required |
| description | text nullable | up to 2,000 |
| identifier | string 255 nullable | |
| status | string | `PossessionStatusEnum`, default `owned` |
| quantity | unsigned integer | 1 to 1,000,000, default 1 |
| is_featured | boolean | default false |
| position | unsigned integer nullable | 1.. gapless among featured of the vault; null otherwise |
| timestamps | | |

Index `(vault_id, is_featured, position)`, `(vault_id, name)`.

## possession_field_values

| Column | Type | Rule |
| --- | --- | --- |
| id | id | |
| account_id | `foreignIdFor(Account)` cascade | |
| possession_id | `foreignIdFor(Possession)` cascade | |
| possession_field_id | `foreignIdFor(PossessionField)` cascade | a field of the possession's type |
| value | text | normalised by kind (research R2) |
| timestamps | | |

Unique `(possession_id, possession_field_id)`.

## Models

`Possession` (`HasUuids`, `ResolvesModelInVault`, `#[RouteKey('uuid')]`), relations
`vault`, `possessionType`, `location`, `values`. `PossessionFieldValue`, relations `possession`,
`field`. `PossessionType` and `PossessionField` gain `possessions` and `values`.

## Actions

| Action | Writes | Log |
| --- | --- | --- |
| `CreatePossession` | the row; appends to featured order when featured | `PossessionCreation` |
| `UpdatePossession` | attributes but the type; joins or leaves the featured order | `PossessionUpdate` |
| `DestroyPossession` | permanent delete, values deleted with it, featured gap closed | `PossessionDeletion` |
| `UpdatePossessionPosition` | moves a featured one; refuses one not featured | `PossessionUpdate` |
| `UpdatePossessionFieldValues` | upserts and removes values, all or nothing | `PossessionUpdate` |
| `DestroyPossessionType` (changed) | counts, deletes, closes featured orders, returns the count | count in the entry |
| `UpdatePossessionField` (changed) | deletes the field's values when the kind changes | |

Each action checks `account_id` first and throws `ModelNotFoundException` for another account's
record, as the 088 actions do.
