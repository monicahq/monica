# Research: MCP tools for life event types

## R1. Every capability already exists in the API

**Decision**: add no route. Each tool mirrors one existing route and calls the action its controller
calls.

| Tool | Mirrors | Action |
| --- | --- | --- |
| `list-life-event-types` | `api.v1.lifeEventTypes` | none |
| `get-life-event-type` | `api.v1.lifeEventTypes.show` | none |
| `create-life-event-type` | `api.v1.lifeEventTypes.create` | `CreateLifeEventType` |
| `update-life-event-type` | `api.v1.lifeEventTypes.update` | `UpdateLifeEventType` |
| `delete-life-event-type` | `api.v1.lifeEventTypes.destroy` | `DestroyLifeEventType` |
| `set-life-event-type-activation` | `api.v1.lifeEventTypes.activation.update` | `ActivateLifeEventType` / `DeactivateLifeEventType` |
| `set-life-event-type-position` | `api.v1.lifeEventTypes.position.update` | `UpdateLifeEventTypePosition` |
| `get-life-event-type-fields` | `api.v1.lifeEventTypes.fields` | none |
| `replace-life-event-type-fields` | `api.v1.lifeEventTypes.fields.update` | `ReplaceLifeEventTypeFields` |
| `get-life-event-category` | `api.v1.lifeEventCategories.show` | none |
| `list-life-event-category-types` | `api.v1.lifeEventCategories.types` | none |
| `create-life-event-category` | `api.v1.lifeEventCategories.create` | `CreateLifeEventCategory` |
| `update-life-event-category` | `api.v1.lifeEventCategories.update` | `UpdateLifeEventCategory` |
| `delete-life-event-category` | `api.v1.lifeEventCategories.destroy` | `DestroyLifeEventCategory` |
| `set-life-event-category-activation` | `api.v1.lifeEventCategories.activation.update` | `ActivateLifeEventCategory` / `DeactivateLifeEventCategory` |
| `set-life-event-category-position` | `api.v1.lifeEventCategories.position.update` | `UpdateLifeEventCategoryPosition` |

**Rationale**: principle X. The list tools return everything unpaginated, as every existing MCP list
tool does, in the API's order (category position, then type position, then id; types of one category
by position then id).

## R2. Refusals

**Decision**:

- Delete type and delete category: catch `AuthorizationException` and answer
  `Response::error($e->getMessage())`. Both policies (`LifeEventTypePolicy`,
  `LifeEventCategoryPolicy`) already word their refusal with `__()`, and the web screen shows the same
  sentence.
- Replace fields: catch `ModelNotFoundException|AcceptedFileKindIsNotValid` and answer
  `Response::error($e->getMessage())`, exactly the pair the API controller catches. These messages are
  untranslated English; the web settings screen relays `AcceptedFileKindIsNotValid` the same way, and
  `update-template-field` relays it too. Translating them belongs to the `Field` model and the action,
  not to this feature.

**Rationale**: unlike 059 (R2), every refusal a reader can trigger through the type and category tools
is already translated, so no substitute wording is needed. The fields case keeps the application's
current behaviour everywhere.

## R3. Update keeps what is left out

**Decision**: `update-life-event-type` takes every attribute as optional: `life_event_category_id`
and `name` keep the stored value when left out; `description`, `icon` and `colour` keep it when left
out and are cleared by `null`, with `array_key_exists`, as `update-entity-type` does. The action
receives a complete set.

**Rationale**: the API's `PUT` requires the category and name and clears the three optional attributes
when they are left out. `/docs/mcp` promises the changing tools keep what is left out, and 058 and 059
made the same choice. The validation of each value given is the API's own.

`update-life-event-category` has one attribute, `name`, so it is required, as in the API. A category
that came with the account can be renamed: `UpdateLifeEventCategory` only checks the account.

## R4. Reaching a type and a category

**Decision**: add `lifeEventType()` and `lifeEventCategory()` to `MirrorsAnApiCapability`, using
`account->lifeEventTypes()` and `account->lifeEventCategories()` with `where('uuid', ...)`, throwing
`doesNotExist()` with `That life event type does not exist.` and `That life event category does not
exist.` The category named by `life_event_category_id` goes through the same lookup.

## R5. The fields input is shared with the API controller

**Decision**: move the API controller's field rules and its mapping of groups and fields into
`App\Helpers\LifeEventTypeFieldLayout`, with three static methods taking plain arrays: `rules()`,
`groups(array $groups)` and `fields(array $groups, array $fields)`. `LifeEventTypeFieldController`
and the `replace-life-event-type-fields` tool both use it. The controller's behaviour does not change;
its existing tests prove it.

**Rationale**: about 80 lines of rules and shape conversion. FR-003 asks the tool to refuse in the same
cases as the API; two copies of 16 wildcard rules and 7 counted settings would drift. `CLAUDE.md`
allows extraction when the logic is non-trivial and reused, which this is. The web screen has its own
input shape (`LifeEventTypeController::layout()`) and stays as it is.

**Alternatives considered**: copy the rules and mapping into the tool, as the template field tools
copy their smaller rule sets. Rejected for the size and the drift risk.

## R6. Allowed icons, colours and field kinds

**Decision**: `icon` and `colour` descriptions list `Section::ICONS` and `VaultColourEnum::values()`
with `implode()`, validated with `Rule::in()` on the same values, as the API does.
`replace-life-event-type-fields` describes `groups` and `fields` in prose and lists
`FieldTypeEnum` values for `type`.

## R7. Destructive marking

**Decision**: `delete-life-event-type`, `delete-life-event-category` and
`replace-life-event-type-fields` carry `#[IsDestructive]`. `McpCatalogueTest` enforces the attribute
on the `delete-` prefix; the replace tool carries it because a field left out stops being asked for.

## R8. Tool classes that share a name with an action

**Decision**: `CreateLifeEventType`, `UpdateLifeEventType`, `CreateLifeEventCategory`,
`UpdateLifeEventCategory` and `ReplaceLifeEventTypeFields` import the action with an `...Action`
alias.

## R9. Activation to the state it already has

**Decision**: both activation tools do nothing, and journal nothing, when the record is already in the
asked state, as the API activation controllers do.

## R10. Documentation

**Decision**: the "Life event categories" group becomes "Life event vocabulary", matching
"Relationship vocabulary", and gains the six new reading tools. A new "Life event vocabulary,
changing" group (`changesData: true`) holds the ten changing tools. A `LIFE_EVENT_TYPES` constant of
two paragraphs is rendered under "Manage life event types" after the relationship types section, in
the page and the Markdown. `EricaServer` instructions gain one paragraph.

## R11. Branch

**Decision**: the branch starts from `2026-09-25-mcp-relationship-types` (PR #246), as asked. Both
features append to `EricaServer`, `McpDocsViewModel`, the docs view and the same tests; building on
top avoids conflicts. Once #246 is merged, this branch is rebased onto `main`.
