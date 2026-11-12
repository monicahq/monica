# Data Model: Possession Types

Three tables in one migration, `2026_10_26_000000_create_possession_types_table.php`. Each row carries
`account_id` and a `uuid` route key (research R5). Deleting the account cascades; deleting a type
cascades to its groups and fields.

## possession_types

| Column | Type | Rule |
| --- | --- | --- |
| `id`, `uuid` | int, uuid unique | |
| `account_id` | foreign id, cascade | |
| `name` | string 255 | required, plain text |
| `colour` | string 7 | one of `VaultColourEnum` (R3) |
| timestamps | | |

## possession_field_groups

| Column | Type | Rule |
| --- | --- | --- |
| `id`, `uuid`, `account_id` | | as above |
| `possession_type_id` | foreign id, cascade | |
| `name` | string 255 | required |
| `position` | unsigned int | from 1 within the type |

## possession_fields

| Column | Type | Rule |
| --- | --- | --- |
| `id`, `uuid`, `account_id` | | as above |
| `possession_type_id` | foreign id, cascade | |
| `possession_field_group_id` | foreign id, null on delete, nullable | set on creation only (R7) |
| `name` | string 255 | required |
| `kind` | string | `PossessionFieldKindEnum` (R4) |
| `options` | json, nullable | distinct non-empty strings, only for `Select` |
| `position` | unsigned int | from 1 within its group, or among the standalone fields |

Indexes on `(possession_type_id, position)`. Relationships: `Account::possessionTypes()`;
`PossessionType::groups()`, `fields()`, `standaloneFields()`, ordered by position; `PossessionFieldGroup::fields()`.

## Actions

| Action | Writes | Changelog (`UserActionEnum`) |
| --- | --- | --- |
| `CreatePossessionType` | type, default name and first colour | `PossessionTypeCreation` |
| `UpdatePossessionType` | name, colour | `PossessionTypeUpdate` |
| `DestroyPossessionType` | type, groups and fields | `PossessionTypeDeletion` |
| `ExportPossessionType` | nothing; returns the file array | none |
| `ImportPossessionType` | a new type, groups and fields, in one transaction | `PossessionTypeImport` |
| `CreatePossessionFieldGroup` | group, last position | `PossessionFieldGroupCreation` |
| `UpdatePossessionFieldGroup` | name | `PossessionFieldGroupUpdate` |
| `UpdatePossessionFieldGroupPosition` | positions of the type's groups | `PossessionFieldGroupUpdate` |
| `DestroyPossessionFieldGroup` | group; its fields become standalone, appended | `PossessionFieldGroupDeletion` |
| `CreatePossessionField` | field, text kind, last position in its set | `PossessionFieldCreation` |
| `UpdatePossessionField` | name, kind, options (cleared off `Select`) | `PossessionFieldUpdate` |
| `UpdatePossessionFieldPosition` | positions in the field's set | `PossessionFieldUpdate` |
| `DestroyPossessionField` | field | `PossessionFieldDeletion` |

Every action first refuses a record of another account with `ModelNotFoundException`, then sanitizes
names with `TextSanitizer::plainText()` and refuses one left empty. The changelog stores the name.
