# Feature Specification: Field Type Picker

**Feature Branch**: `2026-09-02-new-way-of-displaing-fields`

**Created**: 2026-09-02

**Status**: Draft

**Input**: Redesign the screen that picks the type of a new field in a section (`settings/account/templates/{template}/sections/{section}/fields/new`), following the `Field type picker.dc.html` mockup in the Monica design project, while keeping the design system the application already has.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See a type before committing to it (Priority: P1)

Somebody adding a field wants to know what a type produces on a record before choosing it, because the type is the one thing about a field that cannot be changed afterwards. A panel beside the catalogue names the type, describes it, draws the field as it will appear once filled, and gives an example.

**Why this priority**: the irreversible decision is the type, and the rest of the redesign exists so that decision is made with the answer in view.

**Independent Test**: open the screen, select each type in turn, and confirm the panel changes to a rendering that matches that type. Valuable on its own, with the catalogue left exactly as it is today.

**Acceptance Scenarios**:

1. **Given** the screen is open with no type chosen, **When** somebody selects "Recurring date", **Then** the panel names it, describes it, draws a date value and gives the example.
2. **Given** "Short text" is selected, **When** the pointer rests on "Yes or no", **Then** the panel previews "Yes or no" and the selection stays on "Short text".
3. **Given** the label "Weight" has been typed, **When** a type is selected, **Then** the preview shows "Weight" above the drawn value rather than the type name.

---

### User Story 2 - Find the right type among all of them (Priority: P2)

The catalogue names thirty one types across nine families, which is too many to read end to end. A filter box narrows the catalogue as somebody types, a count says how much of it is showing, and each type is a compact card so a whole family can be taken in at a glance.

**Why this priority**: it makes the catalogue usable at its current size, and it grows more valuable as types are added.

**Independent Test**: type into the filter and confirm that only matching types remain, that the count follows, and that clearing it restores everything.

**Acceptance Scenarios**:

1. **Given** the full catalogue, **When** somebody types "birthday", **Then** only the types whose name, key, description or example mention it remain, and the count says how many of the total are showing.
2. **Given** a filter that matches nothing, **When** the results are empty, **Then** the screen says so and offers to clear the filter.
3. **Given** a filter is active, **When** it is cleared, **Then** every family comes back in its original order.

---

### User Story 3 - Add a field that offers choices in one pass (Priority: P3)

Picking a dropdown, a multiple choice or a radio field still asks for the value list its options come from, on the same screen, so the field is created in one submission rather than refused and retried.

**Why this priority**: it is existing behaviour that the redesign must not lose.

**Independent Test**: select "Dropdown", pick a value list, submit, and confirm the field is created with that list.

**Acceptance Scenarios**:

1. **Given** no type is chosen, **When** "Multiple choice" is selected, **Then** the value list question appears and has to be answered before the field can be added.
2. **Given** the account has no value list, **When** a choice type is selected, **Then** the screen says where to make one instead of offering an empty menu.

---

### Edge Cases

- A filter that hides the selected type keeps the selection, the preview and the footer sentence intact.
- A type marked as not available yet can be read and previewed, is visibly marked, and cannot be selected or submitted.
- Submitting with no type chosen is refused with the current message, and the label already typed survives the round trip.
- On a narrow screen the preview sits below the catalogue rather than off the side of it.
- With reduced motion requested, the preview changes without animation.
- The label input, the filter, the catalogue and the preview are all reachable and operable with the keyboard alone.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST keep asking for the field label, required and at most 255 characters, before the type.
- **FR-002**: Types MUST stay grouped into the families the catalogue defines, each family keeping its name and its one line blurb.
- **FR-003**: Each type MUST be shown as a compact card carrying a visual mark and a text label, with no meaning carried by the mark or by colour alone.
- **FR-004**: The technical key of a type MUST be shown in the preview panel rather than on every card.
- **FR-005**: A filter box MUST narrow the catalogue as somebody types, matching against the name, key, description and example of a type, without reloading the screen.
- **FR-006**: The screen MUST say how many types are showing out of the total, worded as a counted phrase in every locale.
- **FR-007**: When the filter matches nothing, the screen MUST say so and tell somebody how to get back to the full catalogue.
- **FR-008**: The preview panel MUST show the name, the key, the description, an illustration of the filled field, and the example line of the type it is previewing.
- **FR-009**: The preview MUST follow the selected type, MUST preview a type that is hovered or focused without changing the selection, and MUST return to the selected type when that ends.
- **FR-010**: The preview MUST use the typed label as the field label as soon as one exists, and the type name until then.
- **FR-011**: Types the first version does not ship MUST stay listed, marked as coming later, unselectable, and refused on submission.
- **FR-012**: The footer MUST name the section and the chosen type, and MUST NOT offer to add the field until a selectable type is chosen.
- **FR-013**: The value list question MUST keep appearing when a choice type is selected, with the wording and the failure it has today.
- **FR-014**: The catalogue MUST behave as one radio group for a screen reader and for the keyboard, with a visible focus, and the preview MUST be announced politely when it changes.
- **FR-015**: A validation failure MUST return the label and the chosen type to the screen.
- **FR-016**: Every string the screen shows MUST be translatable and translated in every configured locale, following the typography rules of each.
- **FR-017**: The screen MUST stay usable on a phone, a tablet and a desktop, and readable zoomed to 200%.
- **FR-018**: What is submitted and what is stored MUST NOT change: the same route, the same payload and the same action create the field.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody can say what a type produces on a record without leaving the screen or opening anything else.
- **SC-002**: Any type in the catalogue can be reached by typing at most four characters into the filter, leaving five or fewer candidates.
- **SC-003**: The catalogue takes at most half the vertical space it takes today on a 1280 by 800 display.
- **SC-004**: The whole flow, from the label to the created field, can be completed with the keyboard alone.
- **SC-005**: Somebody who has not seen the screen before adds a field of the type they wanted in under a minute.
- **SC-006**: A screen reader announces the selected type, its description and the state of the value list question.

## Assumptions

- No type is selected when the screen opens, unlike the mockup, which preselects short text. The type cannot be changed later, so it is chosen deliberately, and the preview panel invites a choice until one is made.
- The keys come off the cards and stay in the preview panel, which is what the mockup does and what makes a compact card readable.
- The catalogue keeps every family it has today, including the calculated family the mockup omits, and keeps listing the types marked for later.
- The value list question is absent from the mockup and is kept, because a choice field cannot be created without one.
- The application keeps its own design system. Nothing from the design project is imported: no tokens, no stylesheet, no bundle, no shell of its own. The mockup is read for layout, wording and behaviour.
- The visual mark on a card comes from the existing icon set, extended where a type has none. The mockup draws its own glyphs, which the current design system has no equivalent for.
- The preview illustrates a filled field rather than rendering a real one, so it needs no value list, no vault and no record.
- Where the mockup and the existing copy disagree, the existing copy wins, since it is already translated into nine locales.
