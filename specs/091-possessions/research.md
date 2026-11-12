# Research: Possessions

## R1. Principle VII gains Possession

- **Decision**: amend VII in the same pull request: twelve fixed concepts, Possession added, carrying
  a vault, a type, an optional location and one value per field of its type. Version 2.8.0.
- **Rationale**: 088 made Possession type a fixed concept with its own fields; the record those fields
  describe is the same exception. Values are rows, never columns, so the second half of VII holds.

## R2. One value table, one text column

- **Decision**: `possession_field_values (possession_id, possession_field_id, value text)`, unique on
  the pair. The value is stored normalised: text and choice as written, number as a decimal string,
  date `Y-m-d`, yes or no `1`/`0`, rating `1`..`5`. The resource casts it back by the field's kind.
- **Rejected**: one column per kind (five nullable columns for one value); JSON on the possession (no
  cascade when a field goes).
- A choice keeps its wording when the option is later renamed or removed (spec edge case); only a
  write checks the current options.

## R3. Cascades are the database's, with three counts kept by actions

| Event | Mechanism |
| --- | --- |
| Type deleted | `possessions.possession_type_id` cascades; values cascade from the possession |
| Field deleted | `possession_field_values.possession_field_id` cascades |
| Field kind changed | `UpdatePossessionField` deletes the field's values when the kind differs |
| Location deleted | `possessions.current_location_id` nulls on delete |
| Possession deleted | permanent delete; its values go with it through the foreign key, and `DestroyPossession` closes the featured gap |

`DestroyPossessionType` counts the type's possessions first, puts the count in its log entry, closes
the featured order of every vault it touched, and returns the count, which the assistant's answer
states. The API keeps answering 204: changing a shipped answer breaks principle VI, so the spec's
"say how many" is met by the log and the tool (spec amended).

## R4. Featured order lives in the actions, not in a separate endpoint for the flag

- **Decision**: `is_featured` is an attribute of create and update. Turning it on appends at
  `max(position) + 1` in the vault; off sets `position` null and decrements those after. A separate
  `UpdatePossessionPosition` moves one, as `UpdatePossessionFieldPosition` moves a field.
- **Rationale**: one flag, one place that keeps the order gapless.

## R5. Values are written on their own endpoint

- **Decision**: `PUT …/possessions/{possession}/values` with `values: {field_id: value|null}`. Only
  the fields named change; `null` or `""` removes the value. The whole map is validated before any
  write, in one transaction, and every bad field is named.
- **Rationale**: create and update stay the possession's own attributes; a tool writing ten values
  does not resend the name. PUT of the possession replaces its attributes as 088 does.

## R6. Vault scoping follows the contacts API

Routes under `vaults/{vault}/possessions`, controllers taking strings and resolving through
`$request->user()->account->vaults()->addressedBy()`, as `Api\EntityController::vault()`. Tools use
`MirrorsAnApiCapability::vault()`. A key reaches every vault of its account; there is no per vault key. A type, a
location or a field named in a body is looked up inside the account (and the field inside the
possession's type) and answers 422 on that attribute, not 404, because the possession is found.

## R7. Status is an enum

`PossessionStatusEnum`: Owned, Ordered, Lent, Sold, GivenAway, Lost, Stolen, ThrownAway; values in
snake case; `Translatable` with labels for the docs and the assistant. Stored as a string column.

## R8. Lists are paginated like contacts

`index` pages by `per_page` (default 25, max 100) with the contacts `meta`, ordered by name, filters
`type` (uuid), `status`, `featured` (boolean). Featured ones can also be asked ordered by position
with `sort=position`.

## R9. Docs

`resources/docs/api/27-possessions.php` in the shape of `26-possession-types.php`; a "Possessions"
group in `McpDocsViewModel`; `spec/models/062-possession-in-progress.md`,
`063-possession-field-value-in-progress.md`; the possession types guide page says deleting a type
deletes its possessions. No user guide page: there is no screen.
