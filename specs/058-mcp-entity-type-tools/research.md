# Research: MCP tools for entity types

## R1. Every capability already exists in the API

**Decision**: add no route and change no controller. Each tool mirrors one existing route and calls
the action its controller calls.

| Tool | Mirrors | Action |
| --- | --- | --- |
| `get-entity-type` | `api.v1.entityTypes.show` | none (lookup and resource) |
| `create-entity-type` | `api.v1.entityTypes.create` | `CreateEntityType` |
| `update-entity-type` | `api.v1.entityTypes.update` | `UpdateEntityType` |
| `delete-entity-type` | `api.v1.entityTypes.destroy` | `DestroyEntityType` |
| `set-entity-type-activation` | `api.v1.entityTypes.activation.update` | `ActivateEntityType` / `DeactivateEntityType` |
| `set-entity-type-position` | `api.v1.entityTypes.position.update` | `UpdateEntityTypePosition` |

**Rationale**: principle X. The web settings screen (`EntityTypeController`, `EntityTypePositionController`)
does nothing the API cannot.

**Alternatives considered**: none; there is no gap to close.

## R2. Update keeps what is left out

**Decision**: `update-entity-type` takes every attribute as optional, fills what is missing from the
stored type, and hands the action a complete set, as `update-template` and `update-template-field`
already do. `description` distinguishes "left out" (kept) from `null` (cleared) with
`array_key_exists`, as `UpdateTemplate` does.

**Rationale**: the API's `PUT` asks for every attribute. An assistant asked to change a colour should
not have to read the type first and resend its names. `/docs/mcp` already promises that the changing
tools "keep what you leave out"; this extends the promise rather than breaking it. The validation of
each value given is the API's own (same lengths, same icon and colour lists), so no rule is invented.

**Alternatives considered**: all attributes required, matching the API literally. Rejected because it
contradicts the sibling tools and the docs page.

## R3. Reaching an entity type

**Decision**: add `entityType(Request, string $address): EntityType` to `MirrorsAnApiCapability`,
looking up `account->entityTypes()->where('uuid', $address)`, the query the API controllers use, and
throwing `doesNotExist(__('That entity type does not exist.'))` otherwise.

**Rationale**: five tools need it, and the trait is where `template()`, `vault()` and the rest live.
The identifier is the resource's `id`, which is the uuid `list-entity-types` already returns.

## R4. The delete refusal

**Decision**: catch `AuthorizationException` from `DestroyEntityType` and answer
`Response::error($e->getMessage())`, as `delete-template` does for its own refusal.

**Rationale**: the policy's message (`The type still holds records and stays.`) is already translated
with `__()`, so the assistant relays the reader's language. The API turns the same exception into a
422 carrying that message; the tool carries the same sentence.

## R5. Activation to the state it already has

**Decision**: `set-entity-type-activation` does nothing, and journals nothing, when the type is
already in the asked state, as `EntityTypeActivationController::settle()` does.

**Rationale**: FR-002. Note that `set-template-activation` does not skip; it mirrors its own
endpoint, and this tool mirrors this one.

## R6. Allowed icons and colours

**Decision**: the `icon` and `colour` inputs list `EntityType::ICONS` and `EntityType::COLOURS` in
their descriptions with `implode()`, as `create-template` lists the values of `applies_to`, and
validate with `Rule::in()` on the same constants.

**Rationale**: FR-005. One constant feeds the API, the web, the tool's validation and its
description, so they cannot drift.

**Alternatives considered**: `->enum()` on the schema. Equally short, but no tool uses it today, and
the sibling idiom is the description.

## R7. Journal source

**Decision**: nothing to add. `SetMcpLogSource` marks every request on the MCP route, and
`LogUserAction` reads it. One case in `McpChangelogTest` proves an entity type change is recorded as
arriving over MCP.

## R8. Documentation

**Decision**: in `McpDocsViewModel::toolGroups()`, the existing "Entity types" group gains
`get-entity-type`, and a new "Entity types, changing" group (`changesData: true`) holds the five
changing tools. A new `ENTITY_TYPES` constant of one paragraph is rendered in the page and in the
Markdown, under its own heading after "Edit a template". The server instructions gain one sentence.

**Rationale**: FR-007, FR-008, and the grouping the page already uses for templates. The existing
`it_lists_exactly_the_tools_the_server_registers` test fails until every tool is listed;
`it_marks_only_the_groups_that_change_data` is updated with the new group name.
