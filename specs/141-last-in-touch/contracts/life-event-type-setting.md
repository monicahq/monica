# Contract: the Last in touch setting of a life event type

## Screen: `settings.lifeEventTypes.edit` and `.new`

- A switch beside "Offered when recording", named `updates_last_in_touch`, with the copy of research
  R7, the line under it tied by `aria-describedby`.
- Saving it with the form changes it; a change dispatches the recount of research R5.

## API

| Endpoint | Change |
| --- | --- |
| `GET /life-event-types`, `GET /life-event-types/{id}` | `attributes.updates_last_in_touch` (boolean) |
| `POST /life-event-types` | optional `updates_last_in_touch`, default `true` |
| `PUT /life-event-types/{id}` | optional `updates_last_in_touch`; left out keeps the stored value |
| `GET /vaults/{vault}/contacts/{contact}` | unchanged shape; `last_interaction_at` now holds a timestamp |

A non boolean `updates_last_in_touch` answers 422. Documented in `resources/docs/api` and in an API
changelog entry dated the day it ships.

## MCP

`create-life-event-type`, `update-life-event-type` gain the optional boolean `updates_last_in_touch`;
`get-life-event-type` and `list-life-event-types` return it, all through `answerThroughApi()`.
