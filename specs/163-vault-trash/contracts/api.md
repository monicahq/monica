# Contracts: Vault trash

`{kind}` is a value of `TrashItemKindEnum` ([data-model.md](../data-model.md)); `{item}` is the record's uuid. In a path, an unknown kind or a kind behind a flag that is off answers 404. A uuid not in this vault's trash answers 404 through the API, with the message "This record is not in the trash of this vault.", and on the screen redirects to the Trash tab with an error saying the item is no longer in the trash (it was restored or deleted for good in another tab). The listing refuses an unknown `kind` with 422 and lists nothing for a kind that is off.

## JSON API (`routes/api.php`, prefix `api/v1`, name prefix `api.v1.`)

| Method | Path | Name | Answer |
| --- | --- | --- | --- |
| GET, QUERY | `vaults/{vault}/trash` | `trash` | 200 `{data: [item], meta}`; `kind`, `page`, `per_page` |
| PUT | `vaults/{vault}/trash/{kind}/{item}/restoration` | `trash.restoration.update` | 200 `{data: {type, id, attributes: {kind, title, restored_with: [{kind, count}]}}}`; 422 on `item` with the obstacle |
| DELETE | `vaults/{vault}/trash/{kind}/{item}` | `trash.destroy` | 204 |
| DELETE | `vaults/{vault}/trash` | `trash.empty` | 204 |
| DELETE | `vaults/{vault}/entities/{entity}` | `entities.destroy` | 204 |

`item` (`TrashItemResource`): `{type: "trash_item", id, attributes: {kind, title, type_label, context, deleted_at, deleted_for_good_at, deleted_with: {kind, id, title} | null, can_be_restored, obstacle}}`, timestamps as Unix integers. The full example is in `resources/docs/api/43-trash.php`.

`deleted_with` is null when the item was deleted on its own or its owner is back. The existing `groups.restoration.update` and `lifeEvents.restoration.update` stay as they are.

## MCP tools (`app/Mcp/Tools`, each mirroring the route named)

| Tool | Mirrors | Parameters |
| --- | --- | --- |
| `ListTrash` | `api.v1.trash` | vault, kind?, page?, per_page? |
| `RestoreTrashItem` | `api.v1.trash.restoration.update` | vault, kind, item |
| `DeleteTrashItem` | `api.v1.trash.destroy` | vault, kind, item |
| `EmptyTrash` | `api.v1.trash.empty` | vault |
| `DeleteEntity` | `api.v1.entities.destroy` | vault, entity |

`DeleteTrashItem`, `EmptyTrash` and `DeleteEntity` are `IsDestructive`, following the `Delete…` naming of the existing tools.

## Web (`routes/web.php`)

| Method | Path | Name |
| --- | --- | --- |
| GET | `vaults/{vault}/settings/trash/{kind?}` | `vaults.trash.index` |
| PUT | `vaults/{vault}/settings/trash/{kind}/{item}/restoration` | `vaults.trash.restoration.update` |
| DELETE | `vaults/{vault}/settings/trash/{kind}/{item}` | `vaults.trash.destroy` |
| DELETE | `vaults/{vault}/settings/trash` | `vaults.trash.empty` |
| DELETE | `vaults/{vault}/entities/{entityType}/{entity}` | `entities.destroy` |

`GET vaults/{vault}/settings` stays the General tab. The Show menu's kind is a path segment rather than a query string, as `.claude/rules/code-style.md` asks; the page number stays in the query, as everywhere else.
