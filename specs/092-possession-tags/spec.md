# Feature Specification: Possession Tags

**Feature Branch**: `2026-10-25-location-management`

**Created**: 2026-10-26

**Status**: Draft

**Input**: User description: "ajoute la notion de "tags" pour les possessions. probablement possessiontag. ajoute le modele, les actions pour crud. le design est le suivant et à mettre dans la bonne sous section dans les settings." The design is `Possession tags.dc.html` in the claude.ai design project 85e4275a.

A possession type says what a thing is. A location says where it is. Neither says what somebody wants
to remember about it across types and places: that the Chrysler Sebring and the Dundie awards are
insured, that the paper shredder is to sell, that the World’s Best Boss mug is fragile. A possession
tag is that word. Tags belong to the account, so every vault uses the same list, and they are managed
from the account settings, under the Possessions group, beside Locations and Possession types.

This feature adds the tags themselves. Putting them on possessions is a later feature, so everything
the design says about how many possessions carry a tag waits for it.

## Clarifications

### Session 2026-10-26

- Q: Is putting tags on a possession part of this feature? → A: No. Tags only: the list and its
  management, in the settings, the API and the assistant tools. The link to possessions, and every
  count of possessions the design shows, come with a later feature.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add and list tags (Priority: P1)

The Possession tags screen opens on a short explanation, a panel to add a tag, and the list of the
account's tags. A tag has a name and a colour picked from the fixed palette, and the panel shows how
the tag will look before it is added. Each row of the list shows the tag's colour and its name.

**Why this priority**: Nothing else exists until a tag does. **Independent Test**: Add "Insured" in
blue and "To sell" in green; open the list.

**Acceptance Scenarios**:

1. **Given** tags in two accounts, **When** the screen opens, **Then** only this account's tags appear, sorted by name, with a line saying how many tags there are.
2. **Given** a name typed and a colour picked, **When** "Add tag" is pressed or Enter is typed in the name, **Then** the tag is added to the list and the name field is emptied.
3. **Given** a name, **When** it is typed, **Then** the preview shows the tag with that name and the picked colour before anything is saved.
4. **Given** an empty name, or a name the account already uses ignoring case ("insured" beside "Insured"), **When** the tag is added, **Then** it is refused and the screen says why.
5. **Given** no tag, **When** the screen opens, **Then** an empty state says what a tag is for.

---

### User Story 2 - Rename and recolour a tag (Priority: P1)

"Edit" on a row turns it into a form with the name and the colour. Saving applies it; "Cancel" or
Escape leaves the tag as it was.

**Why this priority**: A tag named wrongly is no use, and fixing it must not mean deleting it and
adding another. **Independent Test**: Rename "Paperwork filed" to "Paperwork done" and make it teal.

**Acceptance Scenarios**:

1. **Given** a new name and colour, **When** saved, **Then** the row shows them, at its new place in the order by name.
2. **Given** an empty name, or the name of another tag of the account ignoring case, **When** saved, **Then** it is refused and the screen says why. Changing only the case of its own name is allowed.
3. **Given** the form open, **When** "Cancel" is pressed or Escape typed, **Then** nothing changes.

---

### User Story 3 - Delete a tag (Priority: P1)

"Delete" on a row asks first, naming the tag and saying the deletion cannot be undone.

**Why this priority**: A list nobody can trim grows useless. **Independent Test**: Delete "Needs
repair"; it leaves the list.

**Acceptance Scenarios**:

1. **Given** delete chosen, **Then** the confirmation names the tag; nothing happens until confirmed.
2. **Given** the deletion confirmed, **Then** the tag is gone from the list and the account journal records it.
3. **Given** the confirmation cancelled, **Then** nothing changes.

---

### User Story 4 - Through the API and an assistant (Priority: P2)

Everything the screen does can be done through the API and the assistant tools: list the tags, read
one, add, rename, recolour and delete one. The documentation of both describes them.

**Why this priority**: The constitution makes a capability incomplete until the API covers it.
**Independent Test**: Through the API, add "Heirloom", rename it, list the tags, delete it; then do the
same through the assistant tools.

**Acceptance Scenarios**:

1. **Given** a valid key, **When** the tags are listed, **Then** each one carries its name and its colour, sorted by name.
2. **Given** a duplicate or empty name, **When** a tag is added or renamed, **Then** the answer is a validation error saying why.
3. **Given** a tag of another account, **When** it is read, changed or deleted, **Then** the answer is not found.

### Edge Cases

- A name with surrounding blank space or markup is stored without them; a name left empty is refused.
- An account with no vault still has its tags, since they belong to the account.
- A very long list stays sorted by name and usable with the keyboard alone.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The account settings MUST offer a Possession tags screen in the Possessions group, after Possession types.
- **FR-002**: A tag MUST belong to one account and be shared by every vault of that account.
- **FR-003**: A tag MUST have a name of 1 to 50 characters, unique in the account ignoring case, and a colour from the same fixed palette as possession types.
- **FR-004**: A tag added without a colour MUST take the first colour of the palette.
- **FR-005**: The screen MUST list the account's tags sorted by name, each with its colour and name, under a line giving the number of tags.
- **FR-006**: The screen MUST preview the tag being added with its name and colour before it is saved.
- **FR-007**: Users MUST be able to rename and recolour a tag.
- **FR-008**: Users MUST be able to delete a tag after a confirmation naming it.
- **FR-009**: Adding, changing and deleting a tag MUST each be written to the account journal.
- **FR-010**: Every capability of FR-005 to FR-008 MUST be available through the API and the assistant tools, and described in their documentation.
- **FR-011**: The screen MUST be operable with the keyboard alone and announced correctly by a screen reader: the colour choice is a labelled group of options naming each colour, and a refused name is announced with its reason. Colour MUST NOT be the only thing telling two tags apart.
- **FR-012**: Every wording MUST be translated in every configured locale, and every count MUST read correctly at zero, one and more.

### Key Entities

- **Possession tag**: A word the account will put on possessions to sort them across types and locations. It has a name unique in the account ignoring case, and a colour from the fixed palette. It belongs to the account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody adds a tag in under 15 seconds from opening the screen.
- **SC-002**: A rename or a recolour shows in the list at once, with the tag at its place in the order by name.
- **SC-003**: No tag is deleted without a confirmation naming it.
- **SC-004**: Every action of the screen can be reproduced through the API and through the assistant tools with the same result.
- **SC-005**: The screen passes the WCAG 2.2 AA checks the project applies to every settings screen.

## Assumptions

- The tag is a new fixed concept of the account, beside Location, Possession type and Possession. Constitution principle VII lists the fixed concepts, so the plan amends it, as it did for those three.
- The palette is the one possession types and vaults use (Blue, Green, Amber, Rust, Violet, Teal, Plum, Slate). The design names four of these colours differently (Ochre, Brick, Rose, Graphite); the existing names are kept, so one colour has one name.
- The design's sidebar puts the screen under a "Personalise" heading; the application already has a Possessions group holding Locations and Possession types, and the screen goes there.
- Deleting a tag is permanent. Nothing else goes with it while possessions cannot carry tags; the later feature that links them decides what a deletion says about the possessions, as principle VIII asks.
- The design's per row usage ("On 6 possessions"), the "in use" count, the note in the edit form and the delete consequence are left out until that feature, since they would always read zero.
- Tags are a flat list: no tag inside another, no manual order, no description.
- The 50 character limit keeps a tag short enough to read as a label; the design shows names of up to 15 characters.
- Putting tags on possessions, and filtering possessions by tag, are a later feature.
