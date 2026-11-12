# Contract: life event MCP tools

One class per tool under `app/Mcp/Tools`, using `MirrorsAnApiCapability`, registered in
`MonicaServer`, calling the same action as the API and answering with `LifeEventResource` resolved
with no sensitive value (research R7). A record out of reach answers the trait's translated
"does not exist" refusal; a refusal from an action answers `Response::error($e->getMessage())`.

| Tool | Class | Mirrors | Arguments |
| --- | --- | --- | --- |
| `list-life-events` | `ListLifeEvents` | `api.v1.lifeEvents` | `vault`, `contact`?, `page`?, `per_page`? |
| `get-life-event` | `GetLifeEvent` | `api.v1.lifeEvents.show` | `vault`, `life_event` |
| `create-life-event` | `CreateLifeEvent` | `api.v1.lifeEvents.create` | `vault`, `life_event_type`, `happened_on`, `participants`, `title`?, `narrative`?, `location`?, `duration_in_minutes`?, `fields`? |
| `update-life-event` | `UpdateLifeEvent` | `api.v1.lifeEvents.update` | `vault`, `life_event`, then any create argument |
| `delete-life-event` | `DeleteLifeEvent` | `api.v1.lifeEvents.destroy` | `vault`, `life_event` |
| `restore-life-event` | `RestoreLifeEvent` | `api.v1.lifeEvents.restoration.update` | `vault`, `life_event` |

The action classes share these names, so each tool imports its action with an `Action` suffix, as
`CreateContactReminder` does. `list-life-events` and `get-life-event` carry `#[IsReadOnly]`, and
`delete-life-event` carries `#[IsDestructive]`, as their siblings do.

`update-life-event` keeps every argument left out, the stored value standing in for it; `fields` left
out keeps every value (research R5).

`MirrorsAnApiCapability` gains `lifeEvent(Vault $vault, string $address, bool $withTrashed = false)`
answering "That life event does not exist." when out of reach.

## Documentation

- `McpDocsViewModel::toolGroups()`: two groups, "Life events, reading" and "Life events, changing".
- `McpDocsViewModel::toolsSummary()`: drop "or life events" from the sentence about what contact responses leave out, and add one paragraph: a life event has a type that decides its custom fields, the people who were there with the first one the person it is recorded for, and is restorable after deletion.
- `MonicaServer` instructions: one paragraph telling the assistant to read the type's fields with `get-life-event-type-fields` before writing `fields`, and that ids, not keys, address a field.
