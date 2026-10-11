# Contract: life events API

Every route sits in the `auth:sanctum` group of `routes/api.php` (names prefixed `api.v1.`). A vault,
contact, type or life event outside the account or the vault answers 404.

| Verb | Path | Name | Controller | Answer |
| --- | --- | --- | --- | --- |
| GET | `vaults/{vault}/life-events` | `lifeEvents` | `LifeEventController@index` | 200 |
| GET | `vaults/{vault}/life-events/{lifeEvent}` | `lifeEvents.show` | `LifeEventController@show` | 200 |
| POST | `vaults/{vault}/life-events` | `lifeEvents.create` | `LifeEventController@create` | 201 |
| PUT | `vaults/{vault}/life-events/{lifeEvent}` | `lifeEvents.update` | `LifeEventController@update` | 200 |
| DELETE | `vaults/{vault}/life-events/{lifeEvent}` | `lifeEvents.destroy` | `LifeEventController@destroy` | 204 |
| PUT | `vaults/{vault}/life-events/{lifeEvent}/restoration` | `lifeEvents.restoration.update` | `LifeEventRestorationController@update` | 200 |

## Index query

`contact` (address, optional), `per_page` (clamped to `monica.api_maximum_items_per_page`), `page`.
Order: `happened_on` descending, then `id` descending. Deleted life events are left out.
Body: `{data: [LifeEventResource], meta: {page, per_page, total}}`, as `ContactReminderController@index`.

## Create and update body

| Key | Rule | Note |
| --- | --- | --- |
| `life_event_type` | required, uuid of a type of the account | create refuses an inactive type (422) |
| `happened_on` | required, `Y-m-d` | |
| `participants` | required array, min 1, distinct contact addresses of the vault | first = recorded for; on update it must be the current one (research R2) |
| `title`, `location` | nullable string, max 255 | |
| `narrative` | nullable string, max 65535 | |
| `duration_in_minutes` | nullable integer, 1 to 100000 | |
| `fields` | optional, `FieldValueRules::for()` of the target version, file fields `prohibited` | omitted on update keeps every value (R5) |

Refusals from the actions (`LifeEventTypeIsNotAvailable`, `FieldIsRequired`, `FieldValueIsNotUnique`,
`NotEnoughRepetitions`, `TooManyRepetitions`) become 422 on `life_event_type` or `fields`.

## LifeEventResource

```json
{
  "type": "life_event",
  "id": "uuid",
  "attributes": {
    "name": "Lunch at Cugino's", "title": "Lunch at Cugino's",
    "life_event_type": {"id": "uuid", "name": "Meal"},
    "happened_on": "2026-10-09", "duration_in_minutes": 60,
    "narrative": "…", "location": "Scranton",
    "participants": [{"id": "contact uuid", "name": "Pam Beesly", "position": 0}],
    "fields": [{"field_id": "12", "key": "dish", "label": "Dish", "type": "text", "row": null, "value": "Pasta", "is_hidden": false}],
    "created_from": "api", "deleted_at": null,
    "created_at": 1760000000, "updated_at": 1760000000
  },
  "links": {"self": "https://…/api/v1/vaults/{vault}/life-events/{uuid}"}
}
```

`deleted_at` is a Unix integer on the restoration answer and null elsewhere. A sensitive field's `value`
is null and `is_hidden` true unless the key holds `sensitive-fields` (research R7).
