# Contract: relationship category endpoints

Base `/api/v1`, inside the `auth:sanctum` group, so every route inherits `throttle:60,1`. The
identifier is a uuid and the router refuses any other shape before a query runs. A category is
account scoped, so no vault appears in the address.

## Endpoints

| Method | Path | Route name | Answers |
| --- | --- | --- | --- |
| GET | `relationship-categories` | `relationshipCategories` | 200, `data` and `meta` |
| GET | `relationship-categories/{relationshipCategory}` | `relationshipCategories.show` | 200 |
| POST | `relationship-categories` | `relationshipCategories.create` | 201 |
| PUT | `relationship-categories/{relationshipCategory}` | `relationshipCategories.update` | 200 |
| DELETE | `relationship-categories/{relationshipCategory}` | `relationshipCategories.destroy` | 204 |
| PUT | `relationship-categories/{relationshipCategory}/activation` | `relationshipCategories.activation.update` | 200 |
| PUT | `relationship-categories/{relationshipCategory}/position` | `relationshipCategories.position.update` | 200 |
| GET | `relationship-categories/{relationshipCategory}/types` | `relationshipCategories.types` | 200, `data` and `meta`, of relationship types |

A category of another account, and one already deleted, answer 404 everywhere, never 403. An invalid
body answers 422 naming every field, and an unauthenticated caller answers 401.

## The relationship category object

```json
{ "type": "relationship_category", "id": "<uuid>",
  "attributes": {
    "name": "Family", "position": 1, "is_system": true, "is_active": true,
    "type_count": 5, "created_at": 1756252800, "updated_at": null },
  "links": { "self": "<base>/relationship-categories/<uuid>" } }
```

`name` reads the user's word when they wrote one and the supplied wording otherwise, translated into
the language the request asks for. `is_system` marks a category the product supplied with the
account: it may be moved and retired, never renamed or deleted. `type_count` counts the types
sitting under it, retired ones included, and decides whether it may be deleted.

## Bodies

| Endpoint | Field | Rule |
| --- | --- | --- |
| create | `name` | required, string, max 255 |
| update | `name` | required, string, max 255 |
| activation | `is_active` | required, boolean |
| position | `position` | required, integer, min 1 |

`create` and `update` reduce the name to plain text, so no markup survives. `update` answers 422 for
a supplied category, carrying the sentence `CannotChangeASuppliedRelationshipCategory` gives.

## Refusals

| Attempt | Answer |
| --- | --- |
| Rename a supplied category | 422 |
| Delete a supplied category | 422 |
| Delete a category that still holds a type | 422, naming how many |
| Position past the end | 200, placed last |
| Same `is_active` sent twice | 200, unchanged and not inverted |
