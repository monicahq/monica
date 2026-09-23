# Data model: Contact list columns

## `contact_list_columns` (new table, new migration `2026_09_22_000000`)

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint | primary key |
| `account_id` | bigint | the account the vault belongs to, for scoping |
| `vault_id` | bigint | the vault whose People list this column belongs to. Cascades on delete |
| `source` | string(32) | a `ContactListColumnSourceEnum` value |
| `section_id` | bigint, nullable | set only when `source` is `field`. Cascades on delete |
| `field_key` | string(64), nullable | set only when `source` is `field` |
| `heading` | string(255), nullable | what the person typed. Null means the default, translated on display |
| `position` | unsigned int | 1 is first after Name |
| `is_visible` | bool | a hidden column keeps its place and heading |
| timestamps | | |

Unique on `vault_id, position`. There is no uniqueness on the source: a vault never saves the same
source twice, and the action refuses it (see Validation).

Model `ContactListColumn` casts `source` to the enum and `is_visible` to bool. It belongs to a
`Vault` and to a `Section`. `Vault` has many `contactListColumns`, ordered by position.

## `ContactListColumnSourceEnum`

| Case | Value | Default heading | Sortable (FR-006) |
| --- | --- | --- | --- |
| Nickname | `nickname` | Nickname | yes, `ContactSortEnum::Nickname` |
| Email | `email` | Email | no |
| Phone | `phone` | Phone number | no |
| Birthday | `birthday` | Birthday | no |
| Template | `template` | Template | no |
| LastSpoke | `last-spoke` | Last spoke | yes, `ContactSortEnum::LastSpoke` |
| Added | `added` | Date added | yes, `ContactSortEnum::Added` |
| Field | `field` | the field's own label | no |

Name is not a case. It is always first, always shown, and always sorts by `ContactSortEnum::Name`.

## Defaults (no rows for the vault)

`email` shown, `phone` shown, `last-spoke` shown, `template` hidden. They live in one method on the
enum, `defaults()`. They are also what Reset to defaults puts back.

## Validation (in `UpdateContactListColumns`)

- The vault belongs to the user's account. Otherwise `ModelNotFoundException`.
- No source is repeated, and no `section_id` and `field_key` pair is repeated.
- A `field` column names a section of the account whose published version, in some active contact
  template, carries that key with an allowed type (research R6). A column already saved that no longer
  qualifies may be saved again unchanged (R9), and may not be added anew.
- A heading is at most 255 characters. Empty becomes null.
- The action writes one `LogUserAction`, as `SetVaultDefaultTemplate` does.
