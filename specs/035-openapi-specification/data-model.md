# Data Model: the reference format mapped onto OpenAPI

No database entity, no migration, no model. The model here is one array shape transformed into another.

## Source

Each of the 19 files under `resources/docs/api` returns `['name' => string, 'guide' => bool, 'sections' => list]`. The builder reads them with `require` and works on the raw arrays rather than through `ApiDocsSection`, because that class flattens `pathParams`, `queryParams` and `bodyParams` into one list and loses which kind each was, which is precisely the distinction OpenAPI needs.

Groups whose `guide` is true are skipped whole. Their 10 sections borrow a real endpoint's method and path so the page can show a code sample, so including them would emit `GET /api-keys` six times over. The 109 sections that remain match the 109 routed endpoints exactly, which is the equality the existing parity test already holds.

## Document level

| OpenAPI | Source |
| --- | --- |
| `openapi` | the constant `3.2.0` |
| `info.title`, `info.description`, `info.version`, `info.license`, `info.contact` | constants in the action, present because a validator warns when they are not |
| `servers` | `ApiDocsViewModel::baseUrl()`, the instance's own address, per [research D8](research.md) |
| `tags` | the `name` of each non guide group, in file order |
| `components.securitySchemes.bearerAuth` | HTTP bearer, the one scheme the API has |
| `security` | `bearerAuth` globally, lifted with `security: []` on the three endpoints whose `auth` is false |

## Operation level

| OpenAPI | Source | Note |
| --- | --- | --- |
| path key | `path` | Laravel's `{vault}` placeholders already read as OpenAPI's |
| method | `method`, lowercased | |
| `operationId` | `id` | kebab-case, and unique across all 119 sections, which an existing test asserts |
| `tags` | the group's `name` | |
| `summary` | `title` | |
| `description` | `description`, then `body` joined, then `rateLimit` | the rate limit is prose and stays prose |
| `parameters` | `pathParams` as `in: path`, `queryParams` as `in: query` | every path parameter is required |
| `requestBody` | `bodyParams` | `application/json`, except the one `file` parameter, which is `multipart/form-data` |
| request example | each parameter's `example` | a parameter whose example is null is absent, which is what the portal already shows |
| `responses.<status>` | `responseStatus`, defaulting to 200, with `returnFields` and `response` | |
| refusals | derived, never declared | below |

## Types

`string` and `text` become `string`. `integer`, `boolean`, `object` and `array` keep their names. `uuid` becomes a `string` with `format: uuid`, `timestamp` an `integer`, being a count of seconds since 1970, and `file` a `string` with `format: binary`.

`default` is held as a string even on an integer parameter, so it is cast to the parameter's own type on the way out. A return field marked `opaque` becomes a free form object, which is the two `logs` parameters fields and nothing else.

## Response schemas

`returnFields` is a flat list of dotted paths (`data.attributes.avatar.url`, `data[]`), which the builder folds back into a nested schema, `[]` marking a list.

Only a shape that genuinely repeats goes to `components.schemas`: the pagination `meta`, and each refusal. A record type does not, because the same type carries different fields on different endpoints, a freshly created contact having no avatar where a read one does. A component keyed on the type constant would therefore be wrong on some endpoints, so FR-011 is met where a shape repeats and not where it only looks as though it does.

An endpoint answering 204 carries no content, and a test already holds that to `returnFields` being empty in both directions.

## Refusals and what is left out

Refusals are derived from what a section already says, so the page format gains no key: 401 when `auth` is true, 404 when the section has any `pathParams`, 422 when it has any `bodyParams`, and 429 always.

`enum`, `maxLength` and numeric bounds live today only inside prose descriptions ("Exactly one of #3f5fd0, ...", "between 1 and 100"). FR-004 asks for name, type, requiredness, default and example and does not ask for these, so the prose keeps them. Lifting them into structured keys is additive, and `ApiDocsSection` ignores unknown keys, so a later feature can do it without paying anything twice.
