# Feature Specification: Tags on the Possession Page

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-27 | **Status**: Draft

**Input**: User description: "same branch. add the tag management on the possession detail page, as seen on this design: `Possession detail - tags.dc.html` (claude.ai design project 85e4275a). we should have a modal asking for permission to delete a tag (which the design don´t have yet)."

Only the form adding a possession sets its tags. Once Michael Scott has added the Dundie awards, putting
"Fragile" on them means editing the whole possession through the API. This feature lets the tags under
the possession's name be added, created and taken off, the last after a confirmation.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add an existing tag to a possession (Priority: P1)

Under the possession's name, after the tags it carries, a dashed "Add a tag" button opens a small
panel. The panel has a search field ("Find or create a tag") and lists every tag of the account,
sorted by name, each with a check box, its colour, its name, and how many other possessions carry it.
Ticking a tag puts it on the possession at once; the chip appears in the row under the name and the
panel closes, unless the server refused the change. Its foot says how many tags the possession
carries and links to the Possession tags screen ("Manage tags").

**Why this priority**: Putting a tag on a possession after it was added is the gap this feature
closes. **Independent Test**: On the Dundie awards, open the panel, tick "Fragile" and "Heirloom";
both chips appear and stay after the page is reloaded.

**Acceptance Scenarios**:

1. **Given** a possession carrying "Heirloom", **When** the panel opens, **Then** it lists all the account's tags sorted by name, "Heirloom" ticked, each with the number of other possessions carrying it ("Unused" when none).
2. **Given** the panel open, **When** "Fragile" is ticked, **Then** "Fragile" is on the possession, its chip appears under the name in its colour, the panel closes and focus returns to "Add a tag", and the foot reads "2 tags on this possession" when it is opened again.
3. **Given** text typed in the search field, **When** it matches tags ignoring case, **Then** only those tags are listed.
4. **Given** the panel open, **When** the keyboard is used, **Then** Up and Down move through the options, Enter ticks the highlighted one, and Escape closes the panel and returns focus to "Add a tag"; a click outside or Tab away also closes it.
5. **Given** a possession with no tag, **Then** the button reads "Add a tag to sort this possession" and the panel's foot reads "No tags on this possession yet".

---

### User Story 2 - Create a tag from the possession page (Priority: P1)

When the search text matches no tag of the account exactly (ignoring case), the list ends with
"Create “Swiss made” and add it", saying the colour the new tag will get and that it can be changed
in Possession tags. Choosing it creates the tag in the account and puts it on the possession, and a
message confirms it.

**Why this priority**: Somebody tagging a thing thinks of the word there, not in the settings.
**Independent Test**: Type "Swiss made", choose the create option; the chip appears and the tag is in
the Possession tags screen.

**Acceptance Scenarios**:

1. **Given** "Swiss made" typed and no such tag, **Then** the create option is offered with the colour the tag will take.
2. **Given** "fragile" typed and a tag "Fragile" exists, **Then** no create option is offered; "Fragile" is listed instead.
3. **Given** the create option chosen, **Then** the tag exists in the account with the announced colour, the possession carries it, the search field is emptied, and a message says "Created “Swiss made” and added it".
4. **Given** a name longer than 50 characters, **When** it is created, **Then** it is refused and the panel says why.

---

### User Story 3 - Take a tag off after confirming (Priority: P1)

Each chip under the name carries a small close button labelled "Remove “Heirloom” from this
possession". Pressing it, or unticking the tag in the panel, opens a confirmation naming the tag and
the possession and saying the tag stays in the account for the other possessions. Nothing changes
until it is confirmed.

**Why this priority**: The user asked that taking a tag off be confirmed first, and the project
requires a warning before every destructive action in the interface. **Independent Test**: Press the
close button on "To sell", confirm; the chip is gone and "To sell" still exists in Possession tags.

**Acceptance Scenarios**:

1. **Given** the close button on "To sell" pressed, **Then** a modal asks to confirm removing “To sell” from this possession and says the tag stays available for other possessions.
2. **Given** the removal confirmed, **Then** the chip is gone, the tag is no longer on the possession, the tag still exists in the account, and a message confirms the removal.
3. **Given** the removal cancelled, or the modal dismissed with Escape, **Then** the possession keeps the tag and focus returns to the control that opened the modal.
4. **Given** the panel open and a ticked tag unticked, **Then** the same confirmation is asked before it is taken off.

---

### Edge Cases

- An account with no tag at all opens the panel on the search field and an empty list; typing a name offers to create it.
- With two tabs open, ticking a tag already carried leaves one link, and removing one already removed answers as done.
- A tag deleted from the account while the panel is open is refused when ticked, and the page reloads its list.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The possession page MUST show the possession's tags under its name as chips, each with the tag's colour and name, sorted by name.
- **FR-002**: Each chip MUST carry a remove control whose accessible name names the tag.
- **FR-003**: The page MUST offer an "Add a tag" control opening a panel listing every tag of the account, sorted by name, each showing whether the possession carries it, its colour, its name and how many other possessions carry it.
- **FR-004**: The panel MUST filter the list by the text typed, ignoring case.
- **FR-005**: Ticking a tag in the panel MUST put it on the possession immediately, without leaving the page and without closing the panel.
- **FR-006**: When the typed text, trimmed, matches no tag of the account ignoring case, the panel MUST offer to create a tag with that name and put it on the possession, naming the colour it will get.
- **FR-007**: A tag created from the panel MUST follow the rules of every tag (name of 1 to 50 characters, unique in the account ignoring case) and take the palette colour after the one given to the account's most recent tag, cycling through the palette.
- **FR-008**: Taking a tag off a possession, from a chip or by unticking it in the panel, MUST ask for confirmation in a modal naming the tag, and MUST change nothing until confirmed.
- **FR-009**: Taking a tag off a possession MUST NOT delete the tag from the account.
- **FR-010**: The panel's foot MUST say how many tags the possession carries and link to the Possession tags screen.
- **FR-011**: Adding, creating and removing a tag MUST each confirm the outcome in a message, and a refusal MUST say why.
- **FR-012**: Every change to a possession's tags MUST be written to the account journal; creating a tag from the panel MUST also be written as a tag creation.
- **FR-013**: Only possessions of the vault in the address, in the signed in account, and only tags of that account, MUST be reachable; anything else answers as not found.
- **FR-014**: The panel MUST be operable with the keyboard alone and announced correctly by a screen reader: a labelled search field controlling a list of selectable options, the highlighted option announced, Escape closing it and returning focus. The confirmation modal MUST trap focus and return it on close. Colour MUST NOT be the only thing telling two tags apart.
- **FR-015**: Every wording MUST be translated in every configured locale, and every count MUST read correctly at zero, one and more.

### Key Entities

- **A possession's tags**: Exists (specifications 092, 094). The link between a possession and the tags it carries, each at most once; this feature changes it one tag at a time.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody puts an existing tag on a possession in under 5 seconds from the possession page, without leaving it.
- **SC-002**: Somebody creates a new tag and puts it on a possession in under 10 seconds, without visiting the settings.
- **SC-003**: No tag is taken off a possession without a confirmation naming it.
- **SC-004**: The panel and the modal pass the WCAG 2.2 AA checks the project applies to every screen.

## Assumptions

- "Delete a tag" in the request means taking a tag off this possession, which is what the close button on a chip does in the design. Deleting a tag from the account stays on the Possession tags screen, which already asks first.
- The design shows an Undo link in the message after a removal. With a confirmation before the removal, Undo is redundant and is left out; the message only confirms.
- Unticking in the panel asks the same confirmation as the chip's close button, so there is one rule for taking a tag off. Ticking needs none.
- In the design each chip links to "every possession tagged" with that name. The possession list has no filter by tag yet, so the chips are not links; the link comes with that filter.
