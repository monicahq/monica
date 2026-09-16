# Contract: the capability catalogue

Thirteen capabilities, ten reading and three writing, at `POST /api/mcp`. Every row names the API
capability it mirrors, which is what FR-003 requires and what SC-003's test reads back.

A tool answers `Response::json()` carrying the same resource class the API answers with, so a payload
reaching an assistant is the payload the API would have sent. A refusal answers `Response::error()`
carrying a translated string.

## Reading

| Tool | Mirrors | Serves it with |
| --- | --- | --- |
| `list-vaults` | `api.v1.vaults` | `VaultResource` over the account's vaults |
| `list-contacts` | `api.v1.contacts` | `ContactResource`, the `inGrouping`, `matchingSearch` and `sortedBy` scopes |
| `get-contact` | `api.v1.contacts.show` | `ContactResource`, the `addressedBy` scope |
| `get-me-contact` | `api.v1.contacts.me` | `ContactResource`, the vault's `is_me` contact. **The route is new, see FR-013** |
| `list-templates` | `api.v1.templates` | `TemplateResource` |
| `list-template-versions` | `api.v1.templates.versions` | `TemplateVersionResource` |
| `list-entity-types` | `api.v1.entityTypes` | `EntityTypeResource` |
| `list-relationship-types` | `api.v1.relationshipTypes` | `RelationshipTypeResource` |
| `list-relationship-categories` | `api.v1.relationshipCategories` | `RelationshipCategoryResource` |
| `list-activity-categories` | `api.v1.activityCategories` | `ActivityCategoryResource` |

## Writing

| Tool | Mirrors | Runs |
| --- | --- | --- |
| `create-contact` | `api.v1.contacts.create` | `CreateContact` |
| `update-contact-name` | `api.v1.contacts.name.update` | `UpdateContactName` |
| `set-contact-favourite` | `api.v1.contacts.favourite.update` | `FavouriteContact`, `UnfavouriteContact` |

## What every tool does the same way

- **Reaching a vault**: `$request->user()->account->vaults()->addressedBy($address)->firstOrFail()`,
  the line the `Api` controllers already repeat. A vault outside the account raises
  `ModelNotFoundException` and the tool answers not found, never forbidden.
- **Declaring its mirror**: `protected string $mirrors` holds the route name. A tool without one, or
  naming a route `Route::has()` does not know, fails `McpCatalogueTest`.
- **Appearing in the catalogue**: `shouldRegister(Request $request)` reads the presented key. Every
  tool registers for every live key today, because `ApiKeyScopeEnum` holds one case and no read and
  write split exists yet. This is the single method scoping arrives in.

## What the transport does, so no tool repeats it

| Concern | Where |
| --- | --- |
| Authentication | `auth:sanctum` on the route |
| Locale for refusals | `SetApiLocale`, already prepended to the api group |
| Rate limit | `throttle:mcp`, `Limit::none()` when `monica.self_hosted` |
| Changelog source | `SetMcpLogSource`, writing `log_source` and `log_source_detail` for `LogUserAction` |

## Not offered

Notes, activities, field values, and the rest of the API surface. Spec Assumptions say why.
