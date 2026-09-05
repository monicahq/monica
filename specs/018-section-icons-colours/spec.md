# Feature Specification: Section Icons and Colours

**Feature Branch**: `2026-09-04--color-section`

**Created**: 2026-09-04

**Status**: Draft

**Input**: User description: "Pouvoir avoir des icons et des couleurs fg/bg par section d'un template." Plus the design at `Section icons.dc.html`, which settles the set and the palette, and a request to adjust how a section is created and to make one editable.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Mark a section as it is created (Priority: P1)

The block that adds a section to a template asks for three things instead of one: a name, a glyph, and
a colour. The record then draws that mark on the section header.

**Why this priority**: sections already draw an icon on a record and every one of them draws the same
fallback, so the marks are there and carry nothing. Choosing at creation is the smallest change that
makes drawing them worth anything, and it is the only story the design covers.

**Independent Test**: add a section with a glyph and a colour, open a contact on that template, and see
the mark on that block while the others keep the default.

**Acceptance Scenarios**:

1. **Given** the add block, **When** a name, a glyph and a colour are given, **Then** the section is created carrying all three and the mark shows in the builder and on every record whose template carries it.
2. **Given** the add block, **When** only a name is given, **Then** the section is created on the default glyph and the neutral colour.
3. **Given** the picker, **When** it is driven by the keyboard alone, **Then** every glyph and colour is reachable and the chosen one is announced.

---

### User Story 2 - Change the mark on a section that exists (Priority: P2)

Somebody who named a section badly, or picked the wrong glyph, changes it later without taking the
section off the template and putting it back.

**Why this priority**: nothing can edit a section today. The action exists and has no caller, so a
mark chosen at creation is a mark for life, which makes the first story a trap.

**Independent Test**: change the glyph and colour on an existing section, reload a record carrying it,
and read the new mark.

**Acceptance Scenarios**:

1. **Given** a section with a mark, **When** the mark is changed and saved, **Then** every template carrying that section shows the new mark.
2. **Given** a section carried by more than one template, **When** somebody opens its mark to change it, **Then** the screen says how many other templates it will reach before the change is saved.
3. **Given** a section on a published template version, **When** somebody tries to change its mark, **Then** the change is refused and the screen says a draft is the way to do it.
4. **Given** a mark is changed, **When** a record carrying that section is read, **Then** no stored value has changed and no new section version exists.

---

### User Story 3 - Carry the mark when the section travels (Priority: P3)

A section handed to somebody else, through a file or through the marketplace, arrives wearing the mark
it was given.

**Why this priority**: it makes a section worth sharing as a finished thing rather than a list of
fields, but the first two stories deliver inside one account without it.

**Independent Test**: export a marked section, import it into another account, and read its mark.

**Acceptance Scenarios**:

1. **Given** a marked section, **When** it is exported, **Then** the file names its glyph and its colour.
2. **Given** a file naming a glyph or colour outside the set, **When** it is imported, **Then** the import is refused naming the offending value.

---

### Edge Cases

- A section the product supplied is marked. The mark is display only, so it is editable and does not make the section the account's own.
- A stored glyph name the product does not draw. The default glyph is drawn and the screen is otherwise unaffected.
- A record is read through a revocable share link, where the mark is display only and carries nothing sensitive.
- The eight hues are also a vault's and an entity type's. Rehueing one is a data migration, not a marking change, and this feature MUST NOT rehue them.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST offer exactly 35 glyphs for a section: the 22 the icon set already draws, plus 13 new ones (`ball`, `book`, `briefcase`, `car`, `food`, `globe`, `graduation`, `health`, `home`, `music`, `phone`, `plane`, `target`) drawn on the same 16px grid with the same 1.4 stroke.
- **FR-002**: The product MUST offer exactly 9 colours: `neutral`, plus the 8 hues already stored for vaults, under the names those already carry, so somebody using a screen reader hears a word rather than a code.
- **FR-003**: One colour choice MUST supply both the foreground and the background, so the two cannot be paired wrongly.
- **FR-004**: Every one of the 9 colours MUST meet WCAG 2.2 AA contrast for its glyph on its own background, in the light theme and in the dark theme.
- **FR-005**: Users MUST be able to choose a glyph and a colour while creating a section, and to leave both alone.
- **FR-006**: Users MUST be able to change a section's name, glyph and colour after it exists, and to clear the glyph and the colour.
- **FR-007**: A section with no glyph MUST draw the default glyph, and one with no colour MUST draw the neutral pair.
- **FR-008**: The product MUST refuse a glyph or colour outside the set, wherever the value arrives from, and MUST NOT store it.
- **FR-009**: The mark MUST be drawn identically wherever a section appears: the template builder, a record, and a shared record.
- **FR-010**: The mark MUST NOT be the only thing telling two sections apart.
- **FR-011**: Changing a mark MUST NOT create a section version and MUST NOT alter any stored value.
- **FR-012**: The product MUST refuse to change a mark reached through a published template version, and MUST say a draft is the way to do it.
- **FR-013**: Before a mark is changed, the screen MUST say how many other templates carry that section.
- **FR-014**: The mark MUST be written into an exported section and read back from an imported one.
- **FR-015**: Creating and changing a mark MUST be recorded in the account's journal.
- **FR-016**: The picker MUST be operable with the keyboard alone and MUST announce which glyph and colour are chosen.

### Key Entities

- **Section**: the reusable block, carrying the name, the description, the glyph and the colour. The mark lives here, so it is the same on every template carrying the section.
- **Section version**: one frozen state, owning the fields. The mark is display only and sits outside the freeze.
- **Template version section**: the placement, carrying the position and the title override. It carries no mark, so a rename stays local while a mark is shared.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody can create a marked section and see the mark on a record in under 30 seconds, without leaving the template builder.
- **SC-002**: All 9 colours pass the contrast check in both themes, measured rather than asserted.
- **SC-003**: Every screen drawing a section stays fully usable with the keyboard alone and with a screen reader.
- **SC-004**: A marked section exported and imported into another account arrives with the same mark in 100% of cases.
- **SC-005**: No stored value changes as a result of any marking, verified across the suite.

## Assumptions

- The mark belongs to the section, not to its placement, which is where the existing icon column already sits and what the design shows by asking for it beside the section's own name.
- The 8 hues are reused rather than invented, so the picker adds shapes and reuses colour. `neutral` is the ninth and the default.
- The design's background formula mixes the hue with literal white, which is light theme only. The product already solves this for the same 8 hues in the generated avatar, with a per-theme foreground and the hue at 12% behind it, and that is the pair table to reuse.
- Sections are reachable only through the template builder, so the add block and the edit control both go there, and the block sits inline under a list of sections, which is the case the design's second arrangement was drawn for.
- There is no sections API yet, so API coverage means the logic sits in an action a future controller reuses without rework, and the existing icon column with its path through export and import is reused rather than replaced.
