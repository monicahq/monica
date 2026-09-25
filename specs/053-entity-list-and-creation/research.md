# Research: Entity list and creation form

## R1. One action, `CreateEntity`, modelled on `CreateLifeEvent`

- **Decision**: `CreateEntity(user, vault, entityType, name, structureVersion = null, values, labels,
  labelWordings, yearIsUnknown, monthIsUnknown)`. In one transaction: create the entity (colour from
  `Entity::AVATAR_COLOURS`), increment the version's `record_count` and the type's `entity_count`,
  then run `SetFieldValues` once per section of `$version->assembly`. Log after the transaction.
- **Rationale**: `CreateLifeEvent` already writes a record and its template values this way, and
  `SetFieldValues` accepts an `Entity`. Nested transactions become savepoints, so a refusal in any
  section rolls back the record (FR-004). `entity_count` is read by `EntityTypePolicy::delete` and
  nothing increments it yet.
- **Alternatives**: create, then save sections from the controller (not atomic, logic outside the
  action).

## R2. The ability is a policy: `EntityTypePolicy::addEntity(User, EntityType, Vault)`

- **Decision**: the list asks it with `inspect()` to enable the control; the action asks it with
  `authorize()`. It denies a turned-off type, with the notice wording 052 already ships, and a type
  whose vault default is missing, inactive or not published, with "Nothing can be added until this
  vault has a published template for :name.", and a template requiring a field no screen can fill
  (a required repeater whose required child is a relation), which `SetFieldValues` would refuse.
- **Rationale**: the rule is stated on the screen and in the action, which is what `policies.md`
  says earns a policy. The refused control stays rendered with `aria-describedby`.
- **Alternatives**: a bespoke exception (forbidden by `policies.md`).

## R3. The version check stays in the action

- **Decision**: the web form posts `structure_version_id`. The action refuses with
  `EntityTemplateChanged` when it is no longer the vault default's published version (US2.4). The
  API sends none and always gets the current default.
- **Rationale**: this is a state found while the work runs, which `policies.md` keeps out of a
  policy.

## R4. Which fields the form draws

- **Decision**: `StructureVersion::inputFields()`, which already drops relation, avatar and display
  types, minus a repeater with a required child whose type has no input. Headings, dividers and help
  lines are left out: no screen draws them, the record page's editor included.
- **Rationale**: `SetFieldValues` never enforces a field without an input, so a required relation is
  not a refusal anywhere else. A repeater row is refused for a missing required child, which is why
  such a group cannot be drawn.

## R5. Validation inline in the controllers, as `ContactLifeEventController::valuesFrom()` does

- **Decision**: `FieldValueRules::for($fields)` plus the name (`required`, `string`, `max:255`) and
  the arrays `labels`, `label_wordings`, `year_is_unknown`, `month_is_unknown`. The action's own
  refusals (`FieldIsRequired`, `FieldValueIsNotUnique`, repetition counts, storage) come back as
  errors on `fields`.
- **Alternatives**: reuse `SaveFieldValuesRequest` (bound to one section through its route).

## R6. The form reuses the record page's controls

- **Decision**: `x-field-input` for every field with `:reminder="null"`, which hides the reminder
  controls; `x-field-repetitions` comes through it. The form is `multipart/form-data` for files.
  Pages are titled groups (`x-page-section`), sections headed by their title.
- **Label**: "Add :name" with the type's singular name as written ("Add Animal"). An article or a
  lowercased noun cannot be generated for every language.

## R7. API

- **Decision**: `Api\EntityController` with `index` and `create`, both under
  `vaults/{vault}/entity-types/{entityType}/entities`; an `EntityResource`; two sections added to
  `resources/docs/api/07-entity-types.php`. See [contracts/api.md](contracts/api.md).
- **Not done**: no MCP tool. The MCP server mirrors a handful of endpoints, not all of them.

## R8. Journal

- **Decision**: the existing `UserActionEnum::EntityCreation` ("Created the record :name").
