# Feature Specification: Composite Email and Phone Fields

**Created**: 2026-09-18

**Status**: Draft

**Input**: User description: "Change Email and Phone type to Composite"

Email and phone move out of the Simple family, where a value is one line of text, and into the
Composite family, where several values belong together. A phone value becomes a number and what the
number is for. An email value becomes an address and what the address is for. The label lists are
already supplied to every account, under Phone labels and Email labels, and nothing reads them yet.

## Clarifications

### Session 2026-09-18

- Q: What parts should a composite email or phone value be made of? → A: the value plus one label picked from the account's Email labels or Phone labels list.
- Q: Do email and phone fields already sitting in a published structure version gain the label part? → A: yes, every one of them. Values already stored are not worth preserving, so no migration is owed.
- Q: On a synchronised card, does the value's label or the field's name appear on the number? → A: the value's label, as a standard type where one matches and as a custom label otherwise, with the field's name used only when no label was picked.
- Q: Where do a newly created email or phone field's labels come from? → A: pre-filled with the account's supplied Email labels or Phone labels list, changeable to another list, clearable to offer no label.
- Q: Does the label take part in the required and unique rules? → A: no. Required asks for the address or number, and unique compares addresses and numbers, ignoring labels.

### Session 2026-09-20

- Q: How should the control be laid out and can a label be typed? → A: the label sits left of the value, names itself on hover rather than taking a line, the field's own name takes the whole line above, and picking the open label turns the list into a box for a wording of your own.
- Q: Does the screen that adds a field have to follow? → A: yes. Its illustration of an email and a phone shows the label beside the value, in the same order, so the catalogue never promises a shape the control does not draw.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Say what a number or an address is for (Priority: P1)

Somebody filling in a contact records that one number is the mobile and another is the work line,
without inventing a separate field for each.

**Why this priority**: it is the whole feature. Without it the other stories have nothing to carry.

**Independent Test**: fill a phone field on a contact, pick a label, save, and read the record back.

1. **Given** a contact with a phone field, **When** somebody types a number and picks "Mobile", **Then** the record shows the number and the label together.
2. **Given** a contact with an email field, **When** somebody types an address and picks nothing, **Then** the record shows the address alone and the field is accepted.
3. **Given** a phone field whose label list holds a deactivated item, **When** somebody opens the record, **Then** the deactivated item is not offered, and a value already carrying it still reads correctly.
4. **Given** two contacts sharing an email address under different labels, **When** duplicate detection runs, **Then** they are still reported as likely the same person.

---

### User Story 2 - Carry the label in and out (Priority: P2)

A label recorded here survives a round trip through the API, a vCard export and a synchronising
device, and a label arriving from outside is kept rather than dropped.

**Why this priority**: every capability is reachable through the API, and contacts already
synchronise over CardDAV, so a part the outside world cannot see is a part that is lost on the first
round trip.

**Independent Test**: export a contact as a vCard, read the label on the property, import it back.

1. **Given** a contact whose phone value is labelled "Work", **When** the contact is exported, **Then** the card marks the number as a work number rather than naming the field it came from.
2. **Given** a phone value carrying no label, **When** the contact is exported, **Then** the field's own name travels instead, so the number is never unlabelled.
3. **Given** a card arriving with a labelled email address, **When** it is imported, **Then** the address and its label are both recorded.
4. **Given** the API, **When** a caller reads and writes a phone value, **Then** both parts are readable and writable in one call.

---

### Edge Cases

- What happens when somebody picks a label and leaves the value itself empty? The field is treated as unfilled and the label is not kept on its own.
- What happens when an account has renamed or emptied its label list, or the field names none? The field offers whatever the list holds, and offers no label at all when there is nothing to offer.
- What happens to a sensitive email or phone field? The value is masked as before, and the label is shown, since the label says nothing the value does not.
- What happens on a share link? The label follows the value it belongs to, and is excluded whenever the value is.
- What happens to a label whose list item is later deactivated? The value keeps reading as it did, following the rule that nothing disappears silently.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The catalogue MUST present email and phone in the Composite family rather than the Simple family, and MUST illustrate each of them as a label standing beside a value, in the order the control draws them.
- **FR-002**: A phone value MUST carry a number and, optionally, a label saying what the number is for.
- **FR-003**: An email value MUST carry an address and, optionally, a label saying what the address is for.
- **FR-004**: The label MUST be picked from a value list the field names, so an account can add, rename and deactivate labels without the product shipping a fixed vocabulary.
- **FR-005**: A newly created email or phone field MUST arrive naming the account's supplied Email labels or Phone labels list, and somebody MUST be able to point it at another list or at none.
- **FR-006**: An email or phone field naming no list MUST be complete and MUST offer no label, unlike a choice field, which cannot exist without one.
- **FR-007**: An email address MUST still be refused when it is not a valid address, and a phone number MUST still be accepted as written.
- **FR-008**: Every email and phone field MUST carry the label part whatever structure version holds it, published or draft.
- **FR-009**: Values recorded under the previous single value shape MUST NOT constrain the design, since the product is not in production and no migration is owed.
- **FR-010**: Duplicate detection MUST keep matching contacts on the address or the number, ignoring the label.
- **FR-011**: The record screen MUST show the label beside the value it belongs to, and MUST show the value alone when there is no label.
- **FR-012**: The control MUST be operable with the keyboard alone and announced so that a screen reader reports which label belongs to which value.
- **FR-013**: The API MUST read and write both parts of an email or phone value in one call.
- **FR-014**: An exported or synchronised contact MUST carry the value's label on the property it belongs to, written as a standard type where one matches the label and as a custom label otherwise.
- **FR-015**: The field's own name MUST be written to the card only when the value carries no label, and a label arriving on an incoming card MUST be kept.
- **FR-016**: A required email or phone field MUST be satisfied by the address or number alone, and the label MUST stay optional whatever the field asks for.
- **FR-017**: A unique email or phone field MUST compare addresses and numbers and MUST ignore labels, so the same number cannot be entered twice under two labels.
- **FR-018**: The label MUST sit before the value it belongs to, MUST name itself on hover rather than through a line of its own, and the field's own name MUST take the whole line above both.
- **FR-019**: Picking the open label, the one an account reaches for when none of the others fit, MUST offer a box for a wording of its own, and that wording MUST be what the record, the card and the reader all show.
- **FR-020**: A wording typed beside any other label MUST be dropped rather than stored, and somebody MUST be able to return to the list without reloading the screen.
- **FR-021**: Every string this feature adds MUST be translated in every configured locale.

### Key Entities

- **Field**: gains, for the email and phone types, the value list its labels are picked from, the same way a choice field names the list it offers.
- **Field value**: holds the address or number and the chosen label, as one value rather than two fields.
- **Value list**: Email labels and Phone labels, supplied to every account and editable by it, become the source of those labels.

## Success Criteria *(mandatory)*

- **SC-001**: Somebody can record a labelled phone number on a contact in one pass through the form, with no second field to create first.
- **SC-002**: A new account can label a phone number the moment it is created, with no field and no list to set up first.
- **SC-003**: A labelled value survives an export followed by an import with both parts intact.
- **SC-004**: Two contacts sharing a number or an address are reported as likely the same person whatever labels the two values carry.
- **SC-005**: The control passes the accessibility conditions the product applies to every screen, keyboard alone and screen reader included.

## Assumptions

- Composite means parts that belong together, not repetition. Recording several numbers for one person stays the repeater's job, and one email or phone field holds one value.
- A phone number is kept as written. Splitting it into a country code, a number and an extension is out of scope.
- Website and address fields keep their current shape, although Website labels and Address labels are supplied too. This feature covers the two types named.
- The type keys stay `email` and `phone`. No field changes type, so the rule that a field's type is settled once is untouched.
