# Research: Possessions Empty State

Every decision copies a sibling in the codebase, named in the entry.

## R1. The icon column goes into the branch migration

- **Decision**: `2026_10_26_000000_create_possession_types_table.php` gains `icon` `string(30)`,
  nullable, after `colour`, as `possession_conditions.icon`. The backfill migration is not edited: it
  calls `CreateDefaultPossessionTypes`, which now writes the icon.
- **Rationale**: Both files are on this branch only (`git diff main...HEAD`), and the user asked for
  branch migrations to be edited in place. A local database is rebuilt with `migrate:fresh`.
- **Rejected**: an add column migration (only for a table already in production).

## R2. The icon copies the possession condition, catalogue and fallback included

- **Decision**: nullable, one of `Section::ICONS`, `''` sanitised to null, refused with "Pick an icon
  from the list." on the `icon` key (`CreatePossessionCondition`). Drawn as `icon ?? Section::DEFAULT_ICON`.
- Principle VII describes what a Possession type carries: the sentence gains "a name, a vault colour,
  an optional icon". Constitution 2.11.0 becomes 2.12.0 (MINOR), with a Sync Impact Report;
  `spec/models/059` gains the column.
- **Rejected**: a separate type glyph list (two catalogues for one picker), a non nullable column
  (custom and imported types have no natural icon).

## R3. Default icons live on `PossessionTypeEnum`

- **Decision**: `PossessionTypeEnum::icon(): string`, a `match` beside `colour()`; the mapping is in
  data-model. `CreateDefaultPossessionTypes` writes it. No default uses `note`, the stand-in glyph.
- **Rejected**: a lookup array in the action (the enum already owns the per default look).

## R4. Settings: a glyph group on the edit screen, as the colour group

- **Decision**: the "Name and colour" section of `possession-types/edit.blade.php` becomes "Name,
  colour and icon" and gains a third `x-form`: a `role="group"` of submit buttons `name="icon"`, one
  per glyph plus "No icon", `aria-pressed` on the current one. Each form carries the two other values
  hidden, as the name and colour forms do today. Web creation stays a blank type, then edit.
- **Rejected**: `x-mark-picker` (built for a create form with a Save button; the screen saves per click).

## R5. Update semantics per surface

| Surface | Icon left out | Sent `null` or `''` |
| --- | --- | --- |
| Web update | not possible, every form sends it | cleared |
| API `PUT` (full replace, as colour) | cleared | cleared |
| MCP update ("left out keeps its value") | kept (`array_key_exists`) | cleared |
| API and MCP create | none | none |

`UpdatePossessionType` takes `?string $icon` as a required argument: every caller says what it wants.

## R6. Import reads the icon as it reads the colour

- **Decision**: `PossessionTypeFileReader` reads `icon`: absent or null gives none; a value outside the
  catalogue is a problem, "The icon is not one of the glyphs of the catalogue.", like an unknown
  colour. `FORMAT_VERSION` unchanged: an optional key keeps old files valid.

## R7. The tile opens the form with the type chosen, through the path

- **Decision**: `vaults/{vault}/possessions/new/{possessionType?}`, the same route and name, optional
  bound parameter (`ResolvesModelInAccount` scopes it). `PossessionFormViewModel` takes
  `?PossessionType $chosenType`; `chosenType()` prefers `old('type')`, then it.
- **Rejected**: `?type=` (code style: no query string without a reason; a path binds and scopes the
  model for free); a new controller (it is the same screen).

## R8. One view model for the screen, empty or not

- **Decision**: `PossessionsViewModel` (`app/ViewModels/Vaults`), with `isEmpty()`; the controller
  passes it either way and the view branches. The blocks it computes only run when empty.
- Types: by name, eight; first two fields in the order the form shows them (grouped fields in group
  order, then standalone ones, as `PossessionTypeFieldsBlock`). Locations: top level by name, three; total count; "and :count more" through
  `trans_choice`, names joined with `:names and :last` (`LifeEvent`).
- **Rejected**: a separate block object (one screen, a handful of methods).

## R9. The illustrations are markup, not components

- **Decision**: the three cards are static markup in `index.blade.php`, `aria-hidden="true"` on the
  illustration half, texts translated. Each appears once in the codebase (three file bar, components rule).
- Every link inside an illustration is dropped; the third card's text keeps its "Tags" link.
