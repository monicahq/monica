# Feature Specification: Manage relationships

**Feature Branch**: `2026-09-13-manage-relationships`

**Created**: 2026-09-13

**Status**: Draft

**Input**: User description: "implement the *manage relationships* screen, following the `Relationships tab.dc.html` design in the Monica design project."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read every relationship somebody has (Priority: P1)

Somebody opens a contact, moves to its Relationships page, and sees every person that contact is linked to: grouped, counted, each row carrying an avatar, a name and an age. A row for somebody with a record leads there. A row for somebody who is only a name says so.

**Why this priority**: reading is what the page is for, and it delivers on its own even if no link can yet be changed.

**Independent Test**: write down several relationships through the existing form, open the page, confirm each appears under the right group with the right count.

**Acceptance Scenarios**:

1. **Given** relationships in more than one group, **When** the page opens, **Then** each group appears with its own heading and the number of people in it.
2. **Given** a link to somebody who has a record, **When** the row is followed, **Then** that person's page opens.
3. **Given** a link to somebody written down as a name only, **When** the page is read, **Then** the row says they have no record yet and leads nowhere.
4. **Given** a known date of birth, **When** the row is read, **Then** the age is shown; **and given** none, **Then** the row says the age is not known.
5. **Given** a contact with no relationships, **When** the page opens, **Then** it says so and offers to write down the first.
6. **Given** any contact page, **When** its tabs are read, **Then** a Relationships tab is among them, carrying the number of relationships that contact has.

---

### User Story 2 - Remove a relationship (Priority: P2)

Somebody wrote down the wrong link. They turn on Manage for its group and remove the row, warned first, because the link goes for good.

**Why this priority**: a relationship written down today can never be taken back, which is the sharpest gap on the record.

**Independent Test**: add a relationship, remove it from the page, confirm it is gone from both contacts.

**Acceptance Scenarios**:

1. **Given** a group, **When** Manage is turned on for it, **Then** each of its rows offers a way to change the link and a way to remove it; **and while** Manage is off, no row offers either.
2. **Given** a relationship, **When** removal is asked for, **Then** a warning appears first and nothing is removed until it is confirmed.
3. **Given** a confirmed removal, **When** it finishes, **Then** the row is gone, the group count drops by one, and the link is gone from the other person's page too.
4. **Given** the other person exists only because this link named them, **When** the link is removed, **Then** their record is left standing.
5. **Given** a relationship in another account or another vault, **When** its removal is asked for, **Then** it fails as though it did not exist.

---

### User Story 3 - Correct a relationship (Priority: P3)

Somebody wrote Sibling where they meant Half-sibling, or a Parent / Child link points the wrong way, or the link is simply over and they want to say so without losing that it happened.

**Why this priority**: correcting is rarer than removing and can be reached by removing and writing the link again.

**Independent Test**: change a relationship's type from the page, confirm both people read the new roles.

**Acceptance Scenarios**:

1. **Given** a relationship, **When** the change is asked for, **Then** a form opens showing both people, the nature as it stands, whether it is current or ended, and its dates.
2. **Given** that form, **When** a different nature is saved, **Then** both people read the new roles and the row follows the new nature's group.
3. **Given** a nature reading differently from each side, **When** the form is shown, **Then** which person carries which role can be seen and swapped.
4. **Given** a current relationship, **When** it is marked ended, **Then** both people's rows say so.
5. **Given** a romantic link shown on the Immediate family panel, **When** it is marked ended, **Then** that person stops counting there as a current partner.
6. **Given** an end date before the start date, **When** it is saved, **Then** it is refused with a message saying so and nothing changes.
7. **Given** a change making somebody their own ancestor, **When** it is saved, **Then** it is refused with a message saying so and nothing changes.
8. **Given** a nature no longer available, **When** the form opens, **Then** it is still shown as the link's current nature and saving without changing it does not fail.

---

### Edge Cases

- Everyone a contact is linked to is a name only: the page still groups and counts them, and no row links anywhere.
- The same two people linked twice: both rows appear, each in its nature's group and reading its own status.
- The other person's record is archived: the row still appears and still leads there.
- A nature moved to another group in settings: the row follows the nature to its new group.
- Ended with no end date recorded: the row says it ended and names no date, rather than inventing today.
- An end date recorded while the link is still current: the dates stand as written, and the status decides how the row reads.
- A removal confirmed twice, by going back: fails as though it did not exist, not as an error.

## Requirements *(mandatory)*

### Functional Requirements

**The page**

- **FR-001**: A contact MUST carry a Relationships page among its tabs, at its own address, showing the number of relationships it holds.
- **FR-002**: The page MUST be refused as though the contact did not exist when the contact is in another account, or in a vault other than the one the address names.

**Reading**

- **FR-003**: The page MUST list every relationship the contact has, from either side of the link, read from this contact's point of view.
- **FR-004**: Relationships MUST be grouped by their nature's category, in the order the account has put the categories in, and each group MUST show how many people are in it.
- **FR-005**: A group holding no relationships MUST NOT appear.
- **FR-006**: Each row MUST show the other person's name, their avatar when they have a record, and their age when their date of birth is known, saying so when it is not.
- **FR-006a**: Each row MUST name how the link reads from this contact's side, so that two people in one group can be told apart. The mock omits this; see [research.md](research.md) D4.
- **FR-007**: A row MUST lead to the other person's record, unless they are a name only, in which case it MUST say so and lead nowhere.
- **FR-008**: People inside a group MUST be listed in a stable order that does not change between readings.
- **FR-009**: A contact with no relationships MUST be told so and offered a way to write down the first; every reading of the page MUST offer a way to add one.

**Managing**

- **FR-010**: Each group MUST carry its own way to turn managing on and off, starting off, and turning one on MUST NOT turn on any other.
- **FR-011**: A way to change a relationship and a way to remove it MUST be offered while managing is on for its group, and MUST NOT be offered while it is off.

**Removing**

- **FR-012**: Users MUST be able to remove a relationship, warned first that it goes for good, with nothing removed until it is confirmed.
- **FR-013**: Removing a relationship MUST leave both people's records standing, including a record that exists only because this link named it.
- **FR-014**: Removing a relationship MUST take it off both people's pages, since one link is read from both sides.
- **FR-015**: Removal MUST be refused as though the relationship did not exist when it belongs to another account or another vault.
- **FR-016**: Removal MUST be written to the account's journal, naming both people and the nature of the link.

**Correcting**

- **FR-017**: Users MUST be able to change the nature of a relationship, and the form MUST open showing both people, the nature it carries, its status and its dates.
- **FR-018**: For a nature reading differently from each side, the form MUST show which person carries which role and MUST let the two be swapped.
- **FR-019**: Users MUST be able to mark a relationship ended and current again, and to record when it started and ended, leaving either unrecorded.
- **FR-020**: A change MUST be refused, with a message saying so and nothing changed, when the end date falls before the start date or when it would make somebody their own ancestor.
- **FR-021**: A row for an ended relationship MUST say so and show the dates it holds.
- **FR-022**: A relationship marked ended MUST stop counting as current everywhere the application asks whether a link is current, including the Immediate family panel.
- **FR-023**: A change MUST be refused as though the relationship did not exist when it, or the chosen nature, belongs to another account.
- **FR-024**: A deactivated nature MUST still be shown as a link's current nature and MUST NOT fail on save, but MUST NOT be offered as a new choice.
- **FR-025**: A change MUST be written to the account's journal.
- **FR-026**: Which two people a relationship joins MUST NOT be changeable; the wrong person means removing the link and writing it again.

**Names waiting for a record**

- **FR-027**: When the contact is linked to people who are names only, the page MUST say how many, explain that a name is enough to record a relationship, and offer a way to see the people in this vault who are names only. It MUST say nothing when every person linked has a record.

**Copy**

- **FR-028**: Every phrase MUST be translatable, and every phrase carrying a number MUST vary its wording by that number.

### Key Entities

All four exist; this feature adds no entity and no column.

- **Relationship**: one row holding both sides, read from either. Carries a nature, a status, optional start and end dates, and notes. This feature is the first thing to write the status and the dates.
- **Relationship type**: the nature of a link, saying how it reads from each side and which category it belongs to.
- **Relationship category**: the group a nature belongs to, ordered by the account, and what the page groups by.
- **Contact**: a person in a vault, either a full record or a name only.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every person a contact is linked to can be seen on one page, without opening anything else.
- **SC-002**: A relationship written down by mistake is taken back in three actions from the contact's page: open Relationships, turn on Manage, confirm.
- **SC-003**: No relationship is ever removed without a warning shown first.
- **SC-004**: Correcting a link changes how it reads on both people's pages, with no second edit on the other person.
- **SC-005**: A relationship that is over is recorded as over without being destroyed, and stops counting as current everywhere.
- **SC-006**: A relationship in another account or another vault cannot be read, changed or removed, and is refused as though it did not exist.
- **SC-007**: Every behaviour above is covered by a test, and no test asserts how the page looks.

## Assumptions

- **The design's group names are illustrative.** The mock shows Immediate family, Extended family, Friends and Colleagues; the application has Family, Romantic, Friendship, Professional and Other, and an account may add more. The page groups by the account's own categories so it stays right when somebody rearranges them.
- **The contact header is left alone.** The mock replaces it; it already carries Share, Edit contact and Merge, and that stays. The page carries its own way to add.
- **Out of scope**: the family tree link (the mock gates it behind a flag and no tree exists), relationship notes (nothing reads them), and acting on several rows at once (the mock offers none).
- **Ended means ended, not hidden.** An ended relationship stays on the page, in its group and in its count. Somebody who wants it gone removes it.
- **A date is a day**, with no time, and either date may be left unrecorded.
- **The people who are names only** are reached through the existing people list, given one more way of filtering it beside Everyone, Favourites and Archived.
- **Existing behaviour is reused**: the form from [029](../029-add-relationship-form/spec.md), and the refusal to make somebody their own ancestor, which applies to a correction as it does to a new link.
- **Untouched**: the Immediate family panel from [030](../030-immediate-family-widget/spec.md), which already reads status when it asks who somebody is with now.
