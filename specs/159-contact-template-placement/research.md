# Research: Contact Template Placement

## R1. Where the fact is built

- **Decision**: append one entry to `ContactViewModel::headerFacts()`, after Reminders.
- **Rationale**: the row in `resources/views/app/vaults/contacts/_facts.blade.php` already draws
  every entry of that array, and already turns an entry carrying `url` into a link (Reminders does).
  The partial needs no change, and the fact reaches the page in the shape of its siblings.
- **Alternatives considered**: a separate `templateFact()` read by the partial (a second loop for one
  item); markup written directly in the partial (two shapes for one row).

## R2. Which name and which link

- **Decision**: value is `$contact->structureVersion->structure->owner->name`, the link is the
  existing `templateUrl()`.
- **Rationale**: `templateUrl()` is already the destination of the empty page state, the set aside
  screen and the edit screen, so the four links agree. The owner of the structure is the template,
  read at render time, so a rename shows at once (spec scenario 3).
- **Alternatives considered**: naming the version (`:template, version :number`, as the share link
  form does), rejected by FR-005.

## R3. Queries

- **Decision**: nothing to add.
- **Rationale**: `templateUpdate()` already reads `structureVersion->structure->owner` on every render
  of the page, so the relation is loaded before the facts ask for it.

## R4. The whole fact is the link, not the name alone

- **Decision**: keep the existing linked fact shape: label and value inside one `<a>`.
- **Rationale**: design 1a underlines only the name. The project's existing row links label and value
  together for Reminders, and the code next to the file wins over the design (CLAUDE.md, precedence
  2). It also gives the screen reader one link reading "Template Basic contact" (SC-003).

## R5. Icon size

- **Decision**: the row's existing 16 px, stroke 1.5, muted icon, with the glyph `layers`.
- **Rationale**: the design draws 15 px, the other facts draw 16 px; the row stays uniform.

## R5b. A long name wraps

- **Decision**: the fact's `<li>` takes `min-w-0` and `wrap-anywhere`, its icon `shrink-0`.
- **Rationale**: a template name is free text; one long word overflowed at 320 px (seen in a capture),
  which the spec's edge case and WCAG 1.4.10 forbid. It also covers highlighted field values.

## R6. Strings

- **Decision**: `__('Template')`, which every shipped locale already translates with its glossary word.
- **Rationale**: no `monica:localize` work beyond the check.

## R7. Shared page

- **Decision**: unchanged. `shared/contact.blade.php` reads `SharedContactViewModel::headerFacts()`,
  a different class, so FR-006 holds without code.
