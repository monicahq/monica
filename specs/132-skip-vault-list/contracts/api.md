# API contract: Skip the list of vaults

## `GET api/v1/me`

`data.attributes` gains `shows_vault_list` (boolean).

## `PUT api/v1/me/vault-list`

| Field | Rule |
| --- | --- |
| `shows_vault_list` | required, boolean |

- **200**: the `UserResource`, with the new value.
- **422**: `shows_vault_list` missing or not a boolean.

## `DELETE api/v1/vaults/{vault}`

Unchanged, except one refusal:

- **422** when the vault is the default vault and the caller's `shows_vault_list` is `false`. `errors.vault[0]` carries the policy's sentence (research R4). Nothing is deleted.
