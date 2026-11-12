# Research: MCP tools for relationship types

## R1. Every capability already exists in the API

**Decision**: add no route and change no controller. Each tool mirrors one existing route and calls
the action its controller calls.

| Tool | Mirrors | Action |
| --- | --- | --- |
| `get-relationship-type` | `api.v1.relationshipTypes.show` | none (lookup and resource) |
| `create-relationship-type` | `api.v1.relationshipTypes.create` | `CreateRelationshipType` |
| `update-relationship-type` | `api.v1.relationshipTypes.update` | `UpdateRelationshipType` |
| `delete-relationship-type` | `api.v1.relationshipTypes.destroy` | `DestroyRelationshipType` |
| `set-relationship-type-activation` | `api.v1.relationshipTypes.activation.update` | `ActivateRelationshipType` / `DeactivateRelationshipType` |
| `set-relationship-type-position` | `api.v1.relationshipTypes.position.update` | `UpdateRelationshipTypePosition` |
| `get-relationship-category` | `api.v1.relationshipCategories.show` | none |
| `list-relationship-category-types` | `api.v1.relationshipCategories.types` | none |
| `create-relationship-category` | `api.v1.relationshipCategories.create` | `CreateRelationshipCategory` |
| `update-relationship-category` | `api.v1.relationshipCategories.update` | `UpdateRelationshipCategory` |
| `delete-relationship-category` | `api.v1.relationshipCategories.destroy` | `DestroyRelationshipCategory` |
| `set-relationship-category-activation` | `api.v1.relationshipCategories.activation.update` | `ActivateRelationshipCategory` / `DeactivateRelationshipCategory` |
| `set-relationship-category-position` | `api.v1.relationshipCategories.position.update` | `UpdateRelationshipCategoryPosition` |

**Rationale**: principle X. The web settings screen (`Settings\Account\RelationshipTypes\*`) does
nothing the API cannot.

**Alternatives considered**: types only, six tools. Rejected in the spec: a new type could then only go
into a category that already exists.

## R2. Refusals carry the web screen's translated sentence

**Decision**: where an action throws one of its own exceptions, the tool catches it and answers
`Response::error()` with the translated sentence the web settings controller already shows for it,
not `$e->getMessage()`.

| Exception | Where | Sentence the tool answers |
| --- | --- | --- |
| `RelationshipTypeNameIsAlreadyUsed` | create and update a type | `Another type in this account already goes by that name.` |
| `CannotChangeARelationshipTypeDirection` | update a type | `This type keeps the direction it was written with.` |
| `CannotChangeASuppliedRelationshipCategory` | update a category | `The category came with your account and keeps its name.` |
| `CannotChangeASuppliedRelationshipCategory` | delete a category | `The category came with your account and stays.` |
| `CannotDeleteARelationshipCategoryInUse` | delete a category | `The category still holds types and stays.` |
| `AuthorizationException` (policy) | delete a type | `$e->getMessage()`, already translated by `RelationshipTypePolicy` |

**Rationale**: the actions throw untranslated English (`"The name {$this->name} is already used in
this account."`, `"This category still holds {$held} types."`), which the API relays as it stands.
Principle V requires every user facing string to be translatable, and `McpRefusalLanguageTest` shows
MCP refuses in the language the caller announces. The web sentences are already in every
`lang/*.json`, so no new translation is needed, and `policies.md` says the reader's wording is the
controller's copy, not the exception's. The tool refuses in exactly the cases the API refuses; only
the sentence differs. This is a deliberate difference from the spec's "API's wording", recorded
there.

**Alternatives considered**:

- Relay `$e->getMessage()`, as `delete-template` does. Rejected: it ships English to a French
  assistant, and the in-use message interpolates a count without `trans_choice()`.
- Translate the exception messages inside the actions. Better long term (the API would benefit), but
  it changes the API's answers and five action tests, which is outside this feature. Listed as a
  follow up.

## R3. Update keeps what is left out

**Decision**: `update-relationship-type` takes every attribute as optional and fills what is missing
from the stored type, as `update-entity-type` does (058 R2). `is_active` is not an input; the action
receives the stored value, as the API controller passes it.

`role_b` is resolved in this order: the value given; else, when the type ends up symmetric, `role_a`
(the action enforces that anyway in `sanitize()`); else the stored `role_b`, but only when the type was
already asymmetric. A symmetric type switched to asymmetric without a `role_b` is refused with the
validation rule the API uses (`required`), because its stored `role_b` is only a copy of `role_a`.

`update-relationship-category` has one attribute, `name`, so it is required, as in the API.

**Rationale**: `/docs/mcp` promises the changing tools keep what is left out. The `role_b` rule avoids
silently writing a meaningless second role.

## R4. Reaching a type and a category

**Decision**: add `relationshipType()` and `relationshipCategory()` to `MirrorsAnApiCapability`,
using `account->relationshipTypes()` and `account->relationshipCategories()` with
`where('uuid', ...)`, the queries the API controllers use, throwing `doesNotExist()` with
`That relationship type does not exist.` and `That relationship category does not exist.`

`relationship_category_id` on create and update goes through `relationshipCategory()`, so a category
of another account answers the same not found sentence. The API answers the `exists` rule's message
there; both refuse and write nothing.

**Rationale**: every tool needs one or both, and the trait is where the lookups live.

## R5. Reading tools and their shape

**Decision**: `get-*` return the resource with the counts the API loads (`withCount('relationships')`
and the category for a type, `withCount('types')` for a category). `list-relationship-category-types`
returns every type of the category, unpaginated, ordered by `position` then `id`, as the existing list
tools return everything with `get()`.

**Note**: the API endpoint orders by `id` only, while the web screen and `list-relationship-types`
order by position. The tool orders by position, which is what an assistant moving types needs. This
is the one ordering difference; the API endpoint's ordering is a separate defect, listed as a follow
up.

## R6. Activation to the state it already has

**Decision**: both activation tools do nothing, and journal nothing, when the record is already in
the asked state, as both API activation controllers' `settle()` do.

## R7. Tool classes that share a name with an action

**Decision**: `CreateRelationshipType`, `UpdateRelationshipType`, `CreateRelationshipCategory` and
`UpdateRelationshipCategory` import the action with an `...Action` alias, as `UpdateEntityType` does.

## R8. Journal source

**Decision**: nothing to add. `SetMcpLogSource` marks every request on the MCP route. One case each
for a type and a category in `McpChangelogTest`.

## R9. Documentation

**Decision**: in `McpDocsViewModel::toolGroups()`, the "Relationship vocabulary" group gains
`get-relationship-type`, `get-relationship-category` and `list-relationship-category-types`. A new
"Relationship vocabulary, changing" group (`changesData: true`) holds the ten changing tools. A new
`RELATIONSHIP_TYPES` constant of two paragraphs is rendered in the page and the Markdown under its own
heading after the entity types one. `MonicaServer` instructions gain one sentence.

**Rationale**: FR-008, FR-009 and the grouping used for templates and entity types.
`it_lists_exactly_the_tools_the_server_registers` fails until every tool is listed.

## Follow ups (out of scope)

1. Translate the exception messages in the relationship actions, so the API refuses in the reader's
   language too.
2. `api.v1.relationshipCategories.types` orders by `id` rather than position.
