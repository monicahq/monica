# Contract: activity category endpoints

Base `/api/v1`, inside the `auth:sanctum` group, so every route inherits `throttle:60,1`. The
identifier is a uuid and the router refuses any other shape before a query runs. A category is
account scoped, so no vault appears in the address.

## Endpoints

| Method | Path | Route name | Answers |
| --- | --- | --- | --- |
| GET | `activity-categories` | `activityCategories` | 200, `data` and `meta` |
| GET | `activity-categories/{activityCategory}` | `activityCategories.show` | 200 |
| POST | `activity-categories` | `activityCategories.create` | 201 |
| PUT | `activity-categories/{activityCategory}` | `activityCategories.update` | 200 |
| DELETE | `activity-categories/{activityCategory}` | `activityCategories.destroy` | 204 |
| PUT | `activity-categories/{activityCategory}/activation` | `activityCategories.activation.update` | 200 |
| PUT | `activity-categories/{activityCategory}/position` | `activityCategories.position.update` | 200 |

A category of another account, and one already deleted, answer 404 everywhere, never 403. An invalid
body answers 422 naming every field, and an unauthenticated caller answers 401.

## The activity category object

```json
{ "type": "activity_category", "id": "<uuid>",
  "attributes": {
    "name": "Relationships and family", "position": 1, "is_system": true, "is_active": true,
    "activity_type_count": 0, "created_at": 1789333764, "updated_at": null },
  "links": { "self": "<base>/activity-categories/<uuid>" } }
```

`name` reads the words somebody wrote when there are any, and the supplied wording otherwise,
translated into the language the request asks for. `is_system` marks a category the product supplied
with the account: it may be renamed, moved and retired, and only deletion is closed off.
`activity_type_count` counts the activity natures filed under it and decides whether it may be
deleted. Activity natures do not exist yet, so it is `0` on every category until they do.

## Bodies

| Endpoint | Field | Rule |
| --- | --- | --- |
| create | `name` | required, string, max 255 |
| update | `name` | required, string, max 255 |
| activation | `is_active` | required, boolean |
| position | `position` | required, integer, min 1 |

`create` and `update` reduce the name to plain text, so no markup survives and a name left with
nothing in it is refused. `create` places the category last, offered, not supplied.

## Refusals

| Attempt | Answer |
| --- | --- |
| A name that is empty or only spaces | 422 |
| Delete a supplied category | 422, naming that reason |
| Delete a category that still holds natures | 422, naming how many |
| Rename a supplied category | 200, allowed on purpose |
| Retire a supplied category | 200, allowed on purpose |
| Position past the end | 200, placed last |
| Same `is_active` sent twice | 200, unchanged and not inverted |
