# Contract: relationship type endpoints

Same group, same throttle, same uuid rule as
[relationship-categories.md](relationship-categories.md). A type is account scoped.

## Endpoints

| Method | Path | Route name | Answers |
| --- | --- | --- | --- |
| GET | `relationship-types` | `relationshipTypes` | 200, `data` and `meta` |
| GET | `relationship-types/{relationshipType}` | `relationshipTypes.show` | 200 |
| POST | `relationship-types` | `relationshipTypes.create` | 201 |
| PUT | `relationship-types/{relationshipType}` | `relationshipTypes.update` | 200 |
| DELETE | `relationship-types/{relationshipType}` | `relationshipTypes.destroy` | 204 |
| PUT | `relationship-types/{relationshipType}/activation` | `relationshipTypes.activation.update` | 200 |
| PUT | `relationship-types/{relationshipType}/position` | `relationshipTypes.position.update` | 200 |

The flat list answers the whole account, ordered by the category's position, then the type's, then
`id`. The nested list on a category, in the other contract, answers that category alone.

## The relationship type object

```json
{ "type": "relationship_type", "id": "<uuid>",
  "attributes": {
    "relationship_category_id": "<uuid>", "name": "Parent / Child",
    "role_a": "Parent", "role_b": "Child", "is_symmetric": false,
    "system_behavior": "genealogy_parentage", "position": 1,
    "is_system": true, "is_active": true, "relationship_count": 12,
    "created_at": 1756252800, "updated_at": null },
  "links": { "self": "<base>/relationship-types/<uuid>" } }
```

`name`, `role_a` and `role_b` read the user's words when they wrote them, the supplied wording
otherwise, translated into the language asked for. `role_a` is how A relates to B, `role_b` how B
relates to A, and a symmetric type carries the same word in both. `system_behavior` is
`genealogy_parentage`, `romantic` or null, attached to the supplied type rather than to its wording
or category, and never changes. `relationship_count` decides whether the type may be deleted and
whether its direction may still move.

## Bodies

| Endpoint | Field | Rule |
| --- | --- | --- |
| create | `relationship_category_id` | required, uuid of a category of the account |
| create, update | `name` | required, string, max 255 |
| create, update | `role_a` | required, string, max 255 |
| create, update | `role_b` | required unless `is_symmetric` is true |
| create | `is_symmetric` | optional, boolean, default true |
| update | `is_symmetric` | optional; absent leaves the direction as it is |
| update | `relationship_category_id` | required, uuid of a category of the account |
| activation | `is_active` | required, boolean |
| position | `position` | required, integer, min 1 |

`create` places the type last in its category, offered, with no `system_behavior`. `update` never
changes `is_active`, `position`, `is_system` or `system_behavior`.

## Refusals

| Attempt | Answer |
| --- | --- |
| A name another type of the account carries, or a name or role that is empty or only spaces | 422 |
| A category belonging to another account | 404 |
| Change the direction of a supplied type or one in use | 422 |
| Delete a supplied type, or one relationships use | 422 |
