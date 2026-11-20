# Research: Empty list of an invented type

## R1. The sentence under the title

- **Decision**: the type's `description` when it is set (trimmed, not empty), otherwise
  `__('The :name you add to this vault will appear here, one row each.')` with the plural name.
- **Rationale**: the design's sentence names owners and dates, true only of animals. The description is
  written by whoever invented the type, and the fallback is the one design 1a proposes.
- **Alternatives**: the design's sentence verbatim (wrong for most types); no sentence (the title alone
  does not say what a row is).

## R2. How the type is named in the copy

- **Decision**: the names as stored, unchanged in case: `Your :name will line up here` with the plural,
  `Add your first :name` with the singular.
- **Rationale**: lowercasing breaks German nouns and any proper name a user chose. The existing strings of
  this screen (`No :name in this vault yet`, `Add :name`) already insert the stored name unchanged.
- **Alternatives**: `mb_strtolower()` (wrong in German); a third stored name (schema change for copy).

## R3. Markup and reuse

- **Decision**: the three rows are written inline in the `empty` slot of `x-list-panel`, above an
  `x-empty-state` that receives the title, the sentence as its slot, and an `x-button variant="secondary"`
  in its `action` slot. The text link (`href` and `link-label`) is no longer passed.
- **Rationale**: one file uses the rows, below the three-file bar of `.claude/rules/components.md`, and
  they carry no behavioural contract beyond `aria-hidden`. `x-empty-state` already draws the centred block.
- **Alternatives**: a new `x-placeholder-rows` component (one call site); a bespoke centred block
  (duplicates `x-empty-state`).

## R4. Tint and icon

- **Decision**: the mark is `x-entity-icon` coloured with the type's colour on
  `color-mix(in srgb, <colour> 14%, var(--canvas))`, the recipe `possession-tags/_row.blade.php` uses.
  Icon falls back to `EntityType::DEFAULT_ICON`, as the sidebar does.
- **Rationale**: same mark the sidebar and settings draw for the type; mixing with `--canvas` adapts to
  the dark theme. The colour comes from the closed list `EntityType::COLOURS`, so it is safe in `style`.
- **Alternatives**: the design's fixed amber tint (ignores the type's colour, which the brief requires).

## R5. Fade and bars

- **Decision**: row opacity 90%, 55%, 25%; bars are `bg-hairline` rounded blocks of fixed widths as in
  the design. No animation, so reduced motion needs nothing.

## R6. Refused add

- **Decision**: the panel button copies the header button's refusal: no `href`, `aria-disabled="true"`,
  `aria-describedby="entity-add-refusal"`, which already names the notice or the refusal sentence.
- **Rationale**: `.claude/rules/policies.md`: a refused control stays on the screen and points at its reason.
