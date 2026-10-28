# Contract: API

Under `api/v1`, `auth:sanctum` and `flag:account-transfer`. Resources follow `{type, id, attributes, links}`. A refusal from an action answers 422 with the action's wording.

## Exports

| Method and path | Body | Answer |
| --- | --- | --- |
| `POST exports` | `vaults`: array of vault slugs or uuids, or omitted for every vault | 202, an `account_export`. 403 without the `sensitive-fields` ability. 422 on `export` while another export runs, on `vaults` for an empty list. 404 for a vault of another account |
| `GET exports` | | 200, the account's exports, newest first, paginated |
| `GET exports/{export}` | | 200, an `account_export` |
| `GET exports/{export}/download` | | 200, `application/json` stream. 403 without the `sensitive-fields` ability, since the file holds every sensitive value. 404 when not ready or expired |

`account_export` attributes: `status` (`preparing`, `ready`, `failed`, `expired`), `vaults` (uuids), `size_in_bytes`, `ready_at`, `expires_at`, `created_at`.

## Imports

| Method and path | Body | Answer |
| --- | --- | --- |
| `POST imports` | multipart `file` | 202, an `account_import` in `reading`. 422 on `file` for a file over the size cap or while another import runs |
| `GET imports/{import}` | | 200, an `account_import`, with its summary once `read` and its report once ended |
| `GET imports/{import}/contacts` | `search` | 200, the file's contacts by name, paginated: `account_import_contact`, id the source uuid, `name` |
| `PUT imports/{import}` | `vault` slug or uuid, or `vault_name` for a new vault; `me_contact` source uuid or null. A file of several vaults takes none of them | 202, `writing`. 422 on `import` for a cap or an import not `read`. 404 for a vault of another account |
| `DELETE imports/{import}` | | 202 and `rolling_back` while writing; 204 and gone when not started; 422 on `import` once ended |
| `GET imports/{import}/report` | | 200, the items left out, paginated: `account_import_item`, `{kind, source_uuid, reason}`, the reason translated |

`account_import` attributes:

- `status`: `reading`, `refused`, `read`, `writing`, `rolling_back`, `rolled_back`, `rollback_failed`, `completed`.
- `source_format`: `monica_4` or `monica`. `refusal`: the reason when `refused`.
- `summary`: a list of `{kind, found, left_out}`, null while reading. A list, not a map keyed by kind, so every field has a fixed path the docs can check.
- `already_imported_into`: the uuids of the vaults this file was completely imported into before, known before a vault is chosen.
- `progress`: `{done, total}`. `vault`, `me_contact`, `created_at`, `ended_at`.
- `report`: a list of `{kind, imported, left_out}` once ended, null before.

## Console

`php artisan monica:import-account {file} --email= [--vault=] [--vault-name=] [--me=]` runs the same actions with no size cap, and prints each kind as it is written and a summary at the end.
