# Research: Relationships empty state

## Where the categories come from

- **Decision**: `RelationshipTypeChoices::groups()`, which the add form's picker already reads.
- **Rationale**: it returns the account's active categories in position order, each with its active
  types in position order, and drops a category left with no type. That is exactly FR-001 and the
  edge case of a category with nothing to add. Reading the same source as the picker guarantees the
  first type a row offers is one the form accepts.
- **Alternatives considered**: a new query in `RelationshipsBlock` (a second statement of the same
  rule, which could drift from the picker); `RelationshipCategoryEnum` (only the five supplied
  categories, ignoring what the account added or switched off, which the user ruled out).

## How a category is recognised for its icon

- **Decision**: add `key` to each group `RelationshipTypeChoices` returns. `family` → `family`,
  `romantic` → `heart`, `friendship` → `people`, `professional` → `organization`, anything else,
  including `other` and a category the account added (key `null`), → `relationship`.
- **Rationale**: the supplied categories keep their `key` for as long as they exist and cannot be
  renamed, so the key is stable. The icons are the ones design 1c uses, and all exist in `x-icon`.
- **Alternatives considered**: matching on the name (breaks on rename and in every other language).

## The line under a category

- **Decision**: the names of its first four types, joined with `__(', ')`. A symmetric type reads as
  its role (`Friend`); an asymmetric one as the picker labels it (`Parent ↔ Child`) would be noisy
  here, so the line uses the type's `name`, which is how the settings screen lists it.
- **Rationale**: the account decides what a category holds, so written copy would lie for any
  account that changed it. `__(', ')` is the separator `ReminderDelivery` already uses, so each
  locale can supply its own (`、`, `،`).
- **Alternatives considered**: the design's written hints (fixed English copy, wrong after any
  customisation); "and N more" (a counted phrase for a line that only needs to suggest).
- **Consequence**: `RelationshipTypeChoices` groups carry each type's `name` next to its `label`.

## How Add chooses the type

- **Decision**: the Add link is `contacts.relationships.new` with `?type=<id>`, the id of the
  category's first type. The controller reads `$request->string('type')` and passes it to
  `RelationshipFormViewModel`, whose `values()` uses it as the default of `typeId` only when it is a
  key of `typeMap()`. `old()` still wins after a failed submission.
- **Rationale**: the form already chooses its type through `values()['typeId']`, which both the
  `<select>` and the Alpine projection read, so a default there reaches both with no script change.
  Validating against `typeMap()` refuses another account's type and a withdrawn one with no error
  (FR-005), because the map only holds what this account offers.
- **Alternatives considered**: `?category=<uuid>` (the form would have to pick the type itself, the
  same work one step later, and would need a way to resolve a category); a route segment (a second
  URL shape for one optional preset); validating in the controller (a refusal the reader cannot act
  on; ignoring is what FR-005 asks for).

## When nothing can be added

- **Decision**: keep today's panel for the case where `RelationshipTypeChoices::any()` is false, with
  its sentence and a secondary link to `settings.relationshipTypes.index` in place of the Add button.
- **Rationale**: today's Add button leads to a form that says there is no type to choose. Pointing to
  where types are set up saves the reader that step.

## Markup

- **Decision**: `x-panel :flush="true"` holding one `x-row` per category, `icon`, `title` and
  `description` set, and an `x-button.ghost size="small"` with the plus icon in the `end` slot. The
  button's visible label is "Add"; `aria-label` reads "Add to :category".
- **Rationale**: `x-row` is the design's `mn-row` (tile, title, description, trailing control), so no
  new component is needed. `.claude/rules/components.md` forbids hand writing what a component draws.
