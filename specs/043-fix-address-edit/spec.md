# Feature Specification: Editable Address Field

**Feature Branch**: `2026-09-22-fix-address-edit`

**Created**: 2026-09-22

**Status**: Draft

**Input**: User description: "Fix address Edit process"

An address field sits in the Addresses section of every new account and is stored and read correctly,
yet nobody can type one in: the block that edits a record shows the address as it stands and says this
kind of field cannot be filled in here yet. Only a synchronising device can write one.

## Clarifications

### Session 2026-09-22

- Q: Does an address also carry a label saying what it is for, picked from the account's Address labels list, the way an email or a phone number does? → A: yes, optionally, exactly as email and phone do: the label beside the value, the open label taking a typed wording, and the card carrying it as the property's type.
- Q: Is the country typed freely, as a card carries it, or picked from the account's supplied Countries list? → A: picked from the Countries list, and only from it.
- Q: Are all seven boxes of an address shown at once, or are the rarely used ones kept behind a control until somebody opens it? → A: all seven, always visible, in their fixed order.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Fill in and change an address on a record (Priority: P1)

Somebody opens the Addresses block of a contact, types the street, the city and the postcode, picks
the country, saves, and reads the address back on the record. Later they correct the postcode alone.

**Why this priority**: it is the defect. A field supplied to every account that nobody can fill in is a broken promise on the first contact somebody creates.

**Independent Test**: open the Addresses block of a contact, fill in an address, save, read the record.

**Acceptance Scenarios**:

1. **Given** a contact whose template holds an address field, **When** somebody opens the block that holds it, **Then** they are offered a box for each part of the address rather than a line saying it cannot be filled in here.
2. **Given** an empty address, **When** somebody fills in the street, the city and the postcode, picks the country and Home beside them, and saves, **Then** the record reads the address back as one line made of those parts, with Home beside it.
3. **Given** a recorded address, **When** somebody changes the postcode alone and saves, **Then** every other part is exactly as it was.
4. **Given** a recorded address, **When** somebody empties every box and saves, **Then** the address is cleared and the record says it is not recorded.
5. **Given** an address field the template marks as required, **When** somebody saves the block with every box empty, **Then** the save is refused and names the field, and one filled box is enough to satisfy it.

---

### User Story 2 - Keep every part a device wrote (Priority: P2)

An address that arrived from a contacts application, with a post office box or a second street line,
survives an edit made on the record and travels back to the device whole.

**Why this priority**: devices already write addresses, so a control offering fewer parts than a card
carries would silently drop what they sent on the first save, the loss the constitution forbids.

**Independent Test**: import a card carrying all seven address parts, change the city on the screen, export the card again.

**Acceptance Scenarios**:

1. **Given** an address a device wrote with a post office box and an extended street line, **When** somebody changes the city on the record and saves, **Then** both are still there, on the record and on the next card the device reads.
2. **Given** an address typed on the record and labelled Work, **When** a device reads the contact, **Then** the card carries each part in its own place and marks the address as a work address rather than naming the field it came from.

---

### User Story 3 - Record several addresses for one person (Priority: P3)

Somebody whose template puts an address field inside a repeater records a home and a holiday home.

**Why this priority**: several addresses for one person is what the product says the repeater is for, so the control has to hold up inside a repetition too.

**Independent Test**: fill two repetitions of a repeater holding an address field, save, remove one, save again.

**Acceptance Scenarios**:

1. **Given** a repeater holding an address field, **When** somebody fills in two repetitions and saves, **Then** both addresses read back in the order they were entered.
2. **Given** two recorded repetitions, **When** somebody removes the first and saves, **Then** the second is kept intact.

---

### Edge Cases

- What happens when somebody picks a label and leaves every box empty? The field is treated as unfilled and the label is not kept on its own.
- What happens to a sensitive address field? What happens to every sensitive field today: each part is typed hidden while it is filled in, the field is left out of share links by default, and the label is shown, since it says nothing the address does not.
- What happens when a stored address names a country or a label whose item is later deactivated? The address keeps reading as it did, and the picker no longer offers the item.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The block that edits a record MUST offer a control for an address field, in place of the line saying the field cannot be filled in here.
- **FR-002**: The control MUST offer one box for each of the seven parts an address holds, post office box, extended street line, street, city, region, postcode and country, all shown at once and none behind a control that has to be opened.
- **FR-003**: Every part MUST be optional, and the boxes MUST stand in one fixed order for every locale, street first and country last.
- **FR-004**: The control MUST stand under the field's own name with each box labelled by the name of its part, MUST be operable with the keyboard alone, and MUST meet the accessibility conditions the product applies to every screen.
- **FR-005**: Saving the block MUST record exactly the parts typed and MUST keep every stored part the person did not change, including one a device wrote.
- **FR-006**: An address with every box empty MUST be treated as unfilled: a stored address is cleared, and no address is created.
- **FR-007**: A required address field MUST be satisfied by any one part filled in, MUST refuse a save that leaves every part empty, naming the field, and the label MUST stay optional whatever the field asks for.
- **FR-008**: No format MUST be imposed on a postcode or a region, since they differ from one country to the next, and a typed part MUST be refused only when it exceeds the length of a line of an address.
- **FR-009**: The control MUST work inside a repetition of a repeater as well as outside one.
- **FR-010**: A stored address MUST read back on the record as it does today, the filled parts joined on one line in their fixed order, and MUST read as not recorded when empty.
- **FR-011**: An address typed on the record MUST reach a synchronising device with each part in its own place, and an address a device wrote MUST be shown and saved with every part intact.
- **FR-012**: The field catalogue MUST describe and illustrate the address as the control draws it, the label beside the parts, so it never promises a shape the control does not offer.
- **FR-013**: Every string this feature adds MUST be translated in every configured locale.
- **FR-014**: An address value MUST carry, optionally, a label saying what the address is for, picked from the value list the field names, and a newly created address field MUST arrive naming the account's supplied Address labels list, changeable to another list or to none, as an email or phone field does.
- **FR-015**: The label MUST stand beside the address under the field's own name, the open label MUST take a typed wording of its own, and the record MUST show the label beside the address and the address alone when there is none.
- **FR-016**: An exported or synchronised address MUST carry its label as the property's type, home and work as the standard types and any other as a wording of its own, no label MUST travel when none was picked, not even the field's name, and a label arriving on a card MUST be kept.
- **FR-017**: The country MUST be picked from the account's Countries list rather than typed, and a country a device wrote that names no item of the list MUST be kept and shown as it arrived until somebody picks one in its place.

### Key Entities

- **Field value**: holds the seven parts of an address as one value, in the shape a card writes them, the country as the name of the picked item so a card still reads it as text, and the chosen label beside them.

## Success Criteria *(mandatory)*

- **SC-001**: Somebody can record a complete postal address on a contact in one pass through the block, with no device and no import involved.
- **SC-002**: Every part of an address a device wrote survives an edit made on the screen, on the record and on the next card the device reads.
- **SC-003**: The line saying an address cannot be filled in here appears on no record screen.

## Assumptions

- The stored shape stays the seven parts a card carries, settled when a device was the first thing able to write an address. No migration is owed and nothing stored is reshaped.
- Culture specific envelope layouts, where the order of the parts changes with the country, are out of scope. One order serves every locale in this version.
- Uniqueness is not offered on an address, following the decision already taken that a field holding several values at once is not compared.
- Field values have no API surface yet, for any type. The address is written by the same action every surface shares, so the API gains it the day it reaches field values.
- Looking an address up, placing it on a map and geocoding are out of scope, and the website field is untouched.
