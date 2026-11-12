# Research: Possession Tags

Every decision below copies a sibling already in the codebase, named in each entry. Nothing here
needed an outside source.

## R1. A new fixed concept, so principle VII is amended

- **Decision**: Possession tag joins the fixed concepts of principle VII, which go from twelve to
  thirteen. Constitution 2.8.0 becomes 2.9.0 (MINOR, a new fixed concept), with a Sync Impact Report.
  `spec/spec.md` names it, and `spec/models/064-possession-tag-in-progress.md` describes it.
- **Rationale**: A tag is not user defined data attached to a record through a template: it is a
  record of the account with a fixed shape (a name and a colour). VII forbids a second way to attach
  user defined data, and admitting the concept is how 088 and 091 handled Possession type and
  Possession.
- **Alternatives considered**: A value list of tags. Rejected: a value list is the option set of a
  template field, and possessions do not use templates.

## R2. The colour is a vault colour, stored as its hexadecimal value

- **Decision**: `App\Enums\VaultColourEnum`, stored in a `colour` string of 7 characters, as
  `possession_types.colour` is. Default `Blue`, as `CreatePossessionType` does. Labels are the enum's
  (Blue, Green, Amber, Rust, Violet, Teal, Plum, Slate).
- **Rationale**: Possession types use this palette; one colour keeps one name (Naming rule).
- **Alternatives considered**: The design's names (Ochre, Brick, Rose, Graphite) and its default
  (Teal). Rejected: two names for one colour, and a default that differs from the type's.

## R3. A name unique in the account ignoring case, checked in the action

- **Decision**: `CreatePossessionTag` and `UpdatePossessionTag` load the account's tags and compare
  with `mb_strtolower()`, the way `CreateStructureVersionPage` compares page labels. Updating
  excludes the tag itself, so changing only the case of its own name passes. The refusal is a
  `ValidationException` on `name`: "You already have a tag with this name." No unique index.
- **Rationale**: A case-insensitive index behaves differently on SQLite, MySQL and PostgreSQL, and
  an account holds exactly one user (testing rules), so two simultaneous requests for one account
  are not a case worth a schema device. The list is short, so loading it costs nothing.
- **Alternatives considered**: A lowercased key column with a unique index. Rejected as a second
  copy of the name kept in step for a race that cannot realistically happen.
- **Spec amendment**: the edge case "two people adding the same name at once" is removed, since an
  account has one person.

## R4. The name is 1 to 50 characters

- **Decision**: `max:50` in the controller, the API controller and the tool, written inline as every
  sibling does with `max:255`. The action strips markup and blank space with
  `TextSanitizer::plainText()` and refuses an empty result with "Write a name for this tag."
- **Rationale**: Validation stays inline (project rule); the emptiness check has to follow
  sanitising, so it belongs to the action, as in `UpdatePossessionType`.

## R5. One screen, edited in place

- **Decision**: `settings/account/possession-tags`, one `index` view. No edit page: the row turns
  into its form, as `relationship-types/_category.blade.php` renames a category in place
  (`x-data="{ renaming: … }"`, reopened when the server returns an error on `name`). Every form
  targets the list with Alpine Ajax (`x-target="possession-tags"`), so the list re-renders in
  place and keeps its order by name.
- **Rationale**: The design edits in place; the project already has the pattern.

## R6. The colour is chosen with real radio inputs

- **Decision**: A `<fieldset>` with a `<legend>` "Colour" and eight `<input type="radio"
  name="colour">`, each labelled with the colour's name, drawn as a swatch. The preview of the add
  form reads the chosen radio through Alpine, as `vaults/form.blade.php` previews its colour.
  The swatches stand in one partial, `_colours.blade.php`, included by the add form and by the row.
- **Rationale**: Native radios give the arrow keys, the announcement of the checked option and the
  form value for free (principle IX), which the design's `role="radio"` buttons would rebuild by
  hand. The existing swatches submit at once (`aria-pressed` buttons on the possession type screen),
  which does not fit a form that is filled in and then sent.
- **Alternatives considered**: Extracting a shared component for every colour picker. Rejected for
  this feature: the existing four differ (submit buttons, Alpine buttons, the mark picker), and
  `components.md` says differing sites are not one markup.

## R7. Deletion asks through `x-modal.warning`

- **Decision**: The Delete control on a row is a ghost `x-button` in the danger tone, labelled "Delete
  :name" for screen readers, opening `x-modal.warning` titled "Delete the tag “:name”?", which says
  the deletion cannot be undone. This is what `x-delete-action` draws, minus the label.
- **Rationale**: `x-delete-action` passes its attributes to the card, not to its button, so its
  button cannot carry an `aria-label`, and fifty rows of "Delete" tell a screen reader nothing. The
  relationship category rows open `x-modal.warning` the same way. The design draws an inline red band
  instead; the card is the project's way to ask first.
- **Spec note**: the confirmation still names the tag, as FR-008 asks.

## R8. The navigation entry

- **Decision**: `x-settings.sidebar` gains `possession-tags` in the Possessions group, after
  Possession types, labelled "Possession tags", with the `hash` icon.
- **Rationale**: The design gives the screen the `tag` icon, which Possession types already uses in
  the same group; two identical icons side by side tell nobody anything. `hash` is in the catalogue
  and reads as a tag.

## R9. Journal, API, tools and documentation copy the possession type ones

- **Decision**: Three `UserActionEnum` cases (`PossessionTagCreation`, `PossessionTagUpdate`,
  `PossessionTagDeletion`). API under `api/v1/possession-tags`, sorted with `LocaleCollation`,
  paginated in memory like `PossessionTypeController::index`. A `PossessionTagResource`. Five MCP
  tools with a `possessionTag()` lookup in `MirrorsAnApiCapability`, a "Possession tags" group in
  `McpDocsViewModel`, `resources/docs/api/28-possession-tags.php`, a guide page
  `docs/portal/en/9-templates-and-customisation/14-possession-tags.md`, and a line in
  `docs/FEATURES.md`.
- **Rationale**: Principle X; the siblings define every shape.

## R10. No policy, no flag

- **Decision**: No policy: every rule is stated once, in the action, and no screen offers a control
  conditionally. No flag: possession types and possessions ship without one.
- **Rationale**: `policies.md` adds a policy only for a rule written twice; `flags.md` is for a
  capability being built out of sight, which this branch does not do for its siblings.
