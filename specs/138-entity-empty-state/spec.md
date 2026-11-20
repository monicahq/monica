# Feature Specification: Empty list of an invented type

**Feature Branch**: `2026-11-19-changelog-panda-redesign`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Improve the empty screen of the types other than the contact type, which is supplied by default. The contact list can never be empty, so this only concerns the other types. Attached is the default screen for a type such as Animals. Follow design 1B."

## Design reference

Design 1b, "Aperçu de la liste à venir", of the Claude Design project `e80a62cb-60bb-4e94-a26f-ed7cac97c519`, file `Entity Empty State.dc.html`:

- The page header is unchanged: the plural name of the type as the title ("Animals") and the primary add button ("Add an animal") on the right.
- The panel under the header no longer holds a centred sentence and a link. It holds, from top to bottom:
  - Three placeholder rows drawn the way a filled list draws its rows: a round mark in the type's colour holding the type's icon, a bar where the name will be, and a shorter bar on the right. The three rows fade out from top to bottom (strong, medium, faint). They hold no text and no invented data.
  - Under them, centred: a title ("Your animals will line up here"), one muted sentence under it, and a secondary button to add the first record ("Add your first animal").
- The placeholder rows are decorative and are hidden from assistive technology.
- The icon and the colour come from the type, as chosen by whoever created it.

## User Scenarios & Testing *(mandatory)*

### User Story 1: Understand an empty list and add the first record (Priority: P1)

Somebody created the type Animals and opens it in a vault for the first time. Instead of a bare "No Animals in this vault yet" and a text link, they see the shape the list will take, drawn with the icon and the colour of the type, a sentence saying what will appear there, and a button to add the first animal.

**Why this priority**: This is the whole feature. Every invented type starts empty in every vault, so this is the first screen of every new type.

**Independent Test**: Create a type Animals with the icon `pet` and a colour, open its list in a vault holding no animal. The panel shows three fading placeholder rows marked with the paw in that colour, the title, the sentence and the button "Add your first animal", and the button opens the form to add an animal.

**Acceptance Scenarios**:

1. **Given** a type Animals with no record in the current vault, **When** somebody opens its list, **Then** the panel shows three placeholder rows, the title, a sentence and a secondary add button, and no text link.
2. **Given** that type is marked with the icon `pet` and a colour, **When** the list is empty, **Then** each placeholder row shows that icon on a tint of that colour.
3. **Given** the empty list, **When** somebody activates the secondary add button, **Then** the form to add a record of that type opens, the same one the header button opens.
4. **Given** a type with a description written by whoever created it, **When** the list is empty, **Then** the sentence under the title is that description.
5. **Given** a type with no description, **When** the list is empty, **Then** the sentence under the title is a generic one naming the type, and it reads correctly for any type.
6. **Given** a vault holding at least one record of that type, **When** somebody opens its list, **Then** the list shows the records and no placeholder.

---

### User Story 2: An empty list where nothing may be added (Priority: P2)

Somebody opens the empty list of a type to which they may not add a record, for instance because its template requires something that cannot be filled in yet. The screen still shows the shape of the list, but it does not offer an action that will be refused.

**Why this priority**: The refused case already exists for the header button. The new button must follow the same rule, or the screen offers two different answers to one question.

**Independent Test**: Give the template of Animals a required part that cannot be filled in, open its empty list. The reason is shown, the placeholder rows and the title are shown, and the add button inside the panel is shown as refused, pointing at the reason.

**Acceptance Scenarios**:

1. **Given** a type to which somebody may not add a record, **When** its list is empty, **Then** the placeholder rows and the title are shown, and the add button in the panel is rendered refused (not activable, announced as unavailable) and points at the same reason the header button points at.
2. **Given** a turned off type, **When** the vault holds none of its records, **Then** the type is hidden from the vault as it is today, so its empty list is never shown.

---

### Edge Cases

- **The contact type.** The list of contacts always holds at least the person's own contact, so it never shows this empty state and is not changed by this feature.
- **A type with no icon.** The placeholder rows use the default icon of a type, the same one the navigation shows for it.
- **A type in the darkest colour of the list.** The tint and the icon stay legible in both the light and the dark theme.
- **A very long description.** It wraps inside the same reading width the design gives the sentence, and the panel grows to hold it. It is never cut.
- **A long type name.** The title and the button wrap rather than overflow, down to a screen 320px wide.
- **The list became empty because every record was deleted or moved.** The empty state is the same as for a type never used in the vault.
- **Archived records.** A vault whose only records of the type are archived is not empty; the list shows them as it does today.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The list of an invented type in a vault MUST, when it holds no record, show a preview of three placeholder rows above a title, a sentence and an add button, as design 1b draws it.
- **FR-002**: Each placeholder row MUST show the icon of the type on a tint of the colour of the type, and MUST show no text and no invented data.
- **FR-003**: The three placeholder rows MUST fade from the first to the last.
- **FR-004**: The placeholder rows MUST be hidden from assistive technology; the title, the sentence and the button MUST be announced.
- **FR-005**: The title MUST name the type by its plural name, so it reads correctly for any type and in every shipped language.
- **FR-006**: The sentence under the title MUST be the description of the type when it has one, and a generic sentence naming the type otherwise.
- **FR-007**: The add button in the panel MUST name the type by its singular name and MUST open the same form as the header add button.
- **FR-008**: When adding is refused, the add button in the panel MUST stay on the screen, be refused (not activable, announced as unavailable), and point at the same reason the header button points at.
- **FR-009**: The text link the empty list shows today MUST be removed; the header add button MUST stay.
- **FR-010**: The list of contacts MUST NOT change.
- **FR-011**: A list holding records, archived or not, MUST NOT change.
- **FR-012**: Every string the feature adds MUST be translated into every shipped locale.
- **FR-013**: The screen MUST show no horizontal scroll at a width of 320px, and the empty state MUST read in both the light and the dark theme.

### Key Entities

- **Type**: A kind of record invented by somebody in the account (Animals), with a singular and a plural name, an optional description, an icon and a colour. The contact is the one type supplied by the product.
- **Record**: One thing of a type, kept in one vault (an animal called Biscuit).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For 100% of invented types, the first visit to an empty list shows the icon and the colour of that type without any configuration beyond what the type already holds.
- **SC-002**: Somebody adds the first record of a type from its empty list in one activation, from either the header or the panel.
- **SC-003**: A screen reader user on the empty list hears the title, the sentence and the button, and nothing from the placeholder rows.
- **SC-004**: The empty list shows no horizontal scroll at a width of 320px in either theme.

## Assumptions

- The contact list is out of scope because it is never empty: the account holder's own contact always stands in it.
- The sentence in the design ("One row each, with the people they belong to and the next date worth remembering") is written for animals and cannot be said of every type. The type's own description replaces it, and a generic sentence stands in when there is none.
- The header add button stays as it is. Design 1b keeps it, unlike design 1c, so the screen shows two buttons leading to the same form while the list is empty. This follows the design.
- The placeholder rows are a fixed count of three, whatever the size of the screen.
- No setting, migration or new field is needed: the type already stores its names, description, icon and colour.
- The same empty state is not added to other empty lists of the application (groups, notes, knowledge). They keep theirs.
