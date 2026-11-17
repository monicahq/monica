# Contracts: new endpoints and tools

Every list below answers `{data, meta: {page, per_page, total}}`, paged with `page` and `per_page`
as every API list is, and accepts `GET` and `QUERY`.

## `GET vaults/{vault}/reminders` (`api.v1.reminders`)

Every reminder of the vault, about a contact or about a group, in `ReminderResource` shape, ordered by
id. No date window. A group reminder is left out while the `groups` flag is off for the account.

| Parameter | Rule |
| --- | --- |
| `about` | optional, `contact` or `group`; both when left out |

MCP tool: `list-vault-reminders`, read only, mirrors this route.

## `GET value-lists` (`api.v1.valueLists`)

The value lists of the account with their items, in the order the account keeps them.

| Field | Type |
| --- | --- |
| `data[].id` | integer, the id `create-template-field` takes as `value_list_id` |
| `data[].name` | string |
| `data[].items[]` | `{id, label}` |

MCP tool: `list-value-lists`, read only, mirrors this route.

## Existing endpoint, new tool

`GET vaults/{vault}/contact-categories` (`api.v1.contactCategories`) gains the tool
`list-contact-categories`, read only, whose ids `list-contacts` takes as `categories`.
