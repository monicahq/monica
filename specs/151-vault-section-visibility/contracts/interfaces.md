# Contracts: Hide the main sections of a vault

## Web

| Method | Path | Name | Answer |
| --- | --- | --- | --- |
| `PUT` | `vaults/{vault}/settings/sections` | `vaults.sectionVisibility.update` | Redirect to `vaults.settings.index`, flash `status` "The vault is saved." |

Form fields: `shows_people`, `shows_things`, `shows_knowledge`, each `1` when switched on, absent when off.

## API

`VaultResource.attributes` gains, wherever a vault is returned (list, show, create, update):

```json
{ "shows_people": true, "shows_things": true, "shows_knowledge": false }
```

`PUT /api/vaults/{vault}` accepts any of the three as booleans. An absent one is kept. A non boolean gives 422 with the field named. Records of a hidden section are returned by every other endpoint exactly as before.

## MCP

Tool `update-vault`, mirroring `api.v1.vaults.update`.

| Parameter | Type | Required |
| --- | --- | --- |
| `vault` | string, the id or slug as `list-vaults` gives it | yes |
| `name`, `description`, `colour` | as the endpoint | no |
| `is_default` | boolean | no |
| `structure_version_id` | integer | no |
| `shows_people`, `shows_things`, `shows_knowledge` | boolean | no |

Answer: the vault as the API update returns it, without the `data` wrapper. Another account's vault: "does not exist".
