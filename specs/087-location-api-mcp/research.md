# Research: Location API and MCP Tools

## R1. One API controller over the actions of 086

Decision: `App\Http\Controllers\Api\LocationController` with `index`, `show`, `create`, `update`,
`destroy`, calling `CreateLocation`, `UpdateLocation` and `DestroyLocation` unchanged. The account
level placement and the shape follow `Api\RelationshipTypeController`: a `string` uuid parameter looked
up through `$user->account->locations()`, inline validation, refusals turned into
`ValidationException` (422).
Rationale: principle X wants the API over the same actions; the closest sibling is an account level
resource that already does this.
Rejected: route model binding with `ResolvesModelInAccount` (no API controller here binds a model; they
take a string and scope the query, and `testing.md` names that pattern).

## R2. Path, inherited address and count are built once, in `App\Helpers\LocationTree`

Decision: move the tree logic out of `LocationsViewModel` into `LocationTree`, built from one query of
the account's locations: the depth first order sorted by `LocaleCollation` at each level, the path
joined by " › ", the nearest location with an address, and the count of locations directly inside.
`LocationsViewModel`, `LocationResource` and the MCP tools read from it.
Rationale: three callers need the same four answers. `CLAUDE.md` allows an extraction for logic that is
non trivial and reused in several places, and a path computed twice would drift.
Rejected: a recursive query (086 R7: no SQL recursion, same on the three databases); computing the
path per location with `parent` lazy loads (one query per level per location).

## R3. The list is the tree order, paged in memory

Decision: `index` returns every location in the screen's order, sliced by `page` and `per_page` with
the API defaults (25, at most 100), and the same `meta` as other lists. The MCP `list-locations`
returns all of them, as `list-relationship-types` does.
Rationale: an order by parent then name cannot be written in SQL without recursion, and a household
holds tens of locations. Paging keeps the shape every other list endpoint has.
Rejected: ordering by name in SQL (a child would appear before its parent, contradicting spec FR-004).

## R4. The answer's fields

Decision: `type: location`, `id` (uuid), and `attributes`: `parent_id` (uuid or null), `name`, `emoji`,
`address`, `path`, `effective_address` (null, or `{address, location_id, location_name}` naming the
location itself or its nearest ancestor holding one), `location_count`, `created_at`, `updated_at` as
timestamps. `links.self` is the API address, `links.web` the edit screen
(`settings.locations.edit`), as vaults and contacts carry both.
Rationale: spec FR-003; `effective_address` keeps the address the location falls back on beside the
one it holds, never copied into it (086 FR-010).

## R5. Field names and validation

Decision: `name` `['required', 'string', 'max:255']`, `parent_id` `['nullable', 'string',
Rule::exists('locations', 'uuid')->where('account_id', ...)]`, `emoji` `['nullable', 'string',
'max:32', new SingleEmoji]`, `address` `['nullable', 'string', 'max:2000']`. The web form's rules,
with `parent` renamed `parent_id` like `relationship_category_id`.
The cycle refusal (`InvalidArgumentException`) becomes an error on `parent_id`; the policy refusal
(`AuthorizationException`) an error on `location`, as `RelationshipTypeController::destroy` does on
`relationshipType`; the empty name refusal already arrives as a `ValidationException` on `name`.

## R6. API update replaces, MCP update keeps what is left out

Decision: `PUT` requires `name` and clears `parent_id`, `emoji` and `address` when they are absent, as
the API's other `PUT` endpoints do. The MCP `update-location` keeps every value that is left out and
clears one only when it is sent as null, as `update-relationship-type` does ("What you leave out keeps
its value", `McpDocsViewModel`).
Rationale: each interface keeps its own existing convention; an assistant that renames a location must
not move it to the top level by omission.
Consequence: spec US2.5 is reworded to say this, and SC-003 holds for the same complete input.

## R7. The changelog source of an API change

Finding: `LogUserAction` reads `log_source` from the request, and only `SetMcpLogSource` (MCP) and
`AuthenticateWithApiKeyOnBasicAuth` (DAV) set it. No middleware sets `LogSourceEnum::Api`, so every
JSON API change in the application is recorded as `web` today.
Decision: out of scope. MCP changes are recorded as `mcp` already; API changes are recorded as every
other API endpoint's are. Spec FR-008 is reworded to say so, and the gap is reported as its own
follow-up, since fixing it touches every endpoint's changelog.

## R8. MCP tools

Decision: `ListLocations`, `GetLocation`, `CreateLocation`, `UpdateLocation`, `DeleteLocation` in
`app/Mcp/Tools`, each `use MirrorsAnApiCapability` naming its route, reading tools `#[IsReadOnly]`,
the deleting one `#[IsDestructive]`. A `location()` lookup joins the trait next to
`relationshipType()`, answering "That location does not exist." for another account's. Registered in
`MonicaServer`, with one paragraph in its instructions, and one "Locations" group in
`McpDocsViewModel::toolGroups()`.
`McpCatalogueTest` already fails when a tool mirrors no route or is missing from the catalogue.

## R9. Documentation

Decision: `resources/docs/api/25-locations.php`, through the `api-docs-writer` skill.
`BuildOpenApiDocument` assembles the OpenAPI document from that folder, and `ApiDocsViewModelTest`
fails when a route is undocumented, so both stay in step without another change.

## R10. No new key scope, no flag, no webhook

Decision: any key of the account reaches locations (`ApiKeyScopeEnum` has one scope, for sensitive
fields, and a location is not one). No flag, as in 086 R2. No webhook: the application sends none yet.
