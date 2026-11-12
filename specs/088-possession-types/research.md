# Research: Possession Types

## R1. Principle VII is amended in the same pull request

Decision: constitution 2.6.0 → 2.7.0. Possession type joins the fixed concepts of VII, carrying its own
groups, fields and options; `spec/spec.md` names it beside Location; `spec/models` gains
`059-possession-type`, `060-possession-field-group`, `061-possession-field`. MINOR, as 086 did:
existing practice is not invalidated.
Rationale: the user chose dedicated tables (spec Clarifications); VII forbids them unamended.
Rejected: a structure owned by the type, as `LifeEventType` does (declined by the user).

## R2. Names are always present

Decision: "New type", "Add group" and "Add field" store a translated default ("Untitled type",
"Untitled group", "Untitled field") in the user's language; an empty name is refused afterwards.
Rationale: an empty name would be refused by the import, breaking spec SC-002.

## R3. Colour from `VaultColourEnum`

Decision: the colour is one of `VaultColourEnum` (eight named colours), chosen as life event types and
templates choose theirs. The spec assumption on the palette is amended; an import without a
colour takes `Slate`.
Rationale: one palette in the product, and each swatch has a name to announce (FR-015).

## R4. Kinds in `PossessionFieldKindEnum`

Decision: `Text`, `Number`, `Date`, `Boolean`, `Select`, `Rating`; options in a JSON column, cleared
when the kind leaves `Select`. Rating holds one to five, in a constant on the enum.
Rejected: `FieldTypeEnum` (the template engine's 21 kinds, and "type" already names the possession type).

## R5. Account on every row, uuid route keys

Decision: `possession_types`, `possession_field_groups` and `possession_fields` each carry
`account_id` and `uuid`, `#[RouteKey('uuid')]` and `ResolvesModelInAccount`, as `Location` and
`ValueListItem` do. The controller checks with `abort_unless(..., 404)` that a group or field belongs
to the type in the URL.
Rationale: `RouteProtectionTest` requires every bound model to resolve inside the account.

## R6. Positions are absolute, per sibling set

Decision: `UpdatePossessionFieldPosition` and `UpdatePossessionFieldGroupPosition` take a target
position and renumber the siblings from one, as `UpdateValueListItemPosition` does. Fields are siblings
within one group, or among the standalone fields. The screen's up and down buttons send the position
plus or minus one, as the life event type rows do; the API and MCP send any position.
Rejected: swapping with a neighbour by direction (a second reordering idiom in the product).

## R7. A field stays in the group it was created in
`group_id` is set on creation only (simplest, and not asked for). Removing a group sets its fields' `group_id` to
null and appends them after the standalone fields, in one transaction.

## R8. Edited in place through Alpine Ajax

Decision: each field, group and the type's name are their own form, submitted on `change` with
`x-target` replacing that row only; a polite live region says "Saved". Options are sent as the whole
list with the field. No save button.
Rationale: spec FR-008; Alpine Ajax is the project's way to update a part of a page.

## R9. File format and reader

Decision: `App\Helpers\PossessionTypeFile` encodes `{format: "possession_type", format_version: 1,
possession_type: {name, colour, groups: [{name, fields}], fields}}`, fields as `{name, kind, options?}`,
snake_case like `StructureFile`. `App\Helpers\PossessionTypeFileReader` collects every problem before
throwing `PossessionTypeFileIsNotReadable` holding the list. Limits: 100 KB, 50 groups,
300 fields, 200 options per field, 255 characters per name.
The screen follows the template import: `new`, a throttled `check` route rendering the problems
before submission, `create`. Export follows the template export: a screen with the JSON and
`x-copy-button`, and a `file` route downloading it.
Rejected: `StructureFileReader` (its format describes structures, versions and value lists).

## R10. No policy, no flag, no webhook
Nothing is refused beyond validation, so no policy (`policies.md`); no flag, as 086; no webhook exists.

## R11. API, MCP and documentation as 087

Decision: account level API controllers taking a `string` uuid scoped through the account, inline
validation, `PossessionTypeResource` nesting groups and fields in order. API `PUT` replaces, MCP
update keeps what is left out (087 R6). Tools use `MirrorsAnApiCapability`, are registered in
`MonicaServer`, and grouped as "Possession types" in `McpDocsViewModel`. API reference in
`resources/docs/api/26-possession-types.php` (api-docs-writer); guide page in
`docs/portal/en/9-templates-and-customisation/` (writer); `docs/FEATURES.md` (feature-documentator).
