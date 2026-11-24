# Contracts: Details of a life event on the vault timeline

## Web

| Method | Path | Name | Answer |
| --- | --- | --- | --- |
| `GET` | `vaults/{vault}/life-events/{lifeEvent}` | `vaults.lifeEvents.show` | `200` with the `life-event-details-{uuid}` fragment; `404` for a trashed event or one outside the vault |

Each dashboard entry gains `detailsUrl` (that route) and these hooks:

| Element | Contract |
| --- | --- |
| **Details** button | `aria-expanded`, `aria-controls="life-event-details-{uuid}"`, label **Details** / **Hide details** |
| Target | `id="life-event-details-{uuid}"`, holds the placeholder with `aria-busy="true"` until the fragment arrives |
| **Close** | sets `open = false` and returns focus to **Details** |

## API

`GET /api/v1/vaults/{vault}/life-events/{lifeEvent}` (and every answer built from `LifeEventResource`) gains:

```json
{
  "date_precision": "day",
  "ended_on": null,
  "happened_at_time": "09:00",
  "timezone": "Europe/Paris",
  "participants": [{ "id": "…", "name": "Jim Halpert", "position": 0, "role": "Best man", "notes": null }],
  "entity_participants": [{ "id": "…", "name": "Schrute Farms", "position": 1, "role": null, "notes": null }]
}
```

Additions only: no key changes what it held. Documented in `resources/docs/api/22-life-events.php` and the 2026-11-24 API changelog.
