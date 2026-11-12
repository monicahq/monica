# Feature Specification: Possession Conditions

**Feature Branch**: `2026-10-25-location-management`

**Created**: 2026-10-26

**Status**: Draft

**Input**: User description: "same branch. Add in the settings under possessions the notion of "conditions" for a possession. By default the account should come with a few of them (new, used, etc). A condition has a name and a color and an icon optional. Add model crud actions for a condition. Add screens in the UI to manage them in the settings. Be thorough."

A possession type says what a thing is, a location says where it is, and a tag says what somebody
wants to remember about it. None of them says what state the thing is in: that the Chrysler Sebring
is used, that the World’s Best Boss mug is like new, that the paper shredder is broken. A possession
condition is that state. Conditions belong to the account, so every vault uses the same list, and
they are managed from the account settings, under the Possessions group, beside Locations,
Possession types and Possession tags.

Unlike tags, conditions run from best to worst, so the account keeps them in an order of its own
rather than by name. And unlike tags, an account does not start empty: it comes with a short list
most people can use as it is.

This feature adds the conditions themselves. Giving a possession its condition is a later feature,
as it is for tags.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Start with a sensible list (Priority: P1)

A new account already holds six conditions, from best to worst: New, Like new, Used, Worn, Damaged
and Broken, each with its own colour, written in the language of the person who created the account.
An account that existed before this feature receives the same six, once.

**Why this priority**: The request names it, and a list somebody has to type before using it is a
list few people will build. **Independent Test**: Create an account in French; open the Possession
conditions screen and find the six conditions in French, in that order.

**Acceptance Scenarios**:

1. **Given** a new account, **When** the Possession conditions screen opens, **Then** it lists New, Like new, Used, Worn, Damaged and Broken, in that order, each with its colour.
2. **Given** an account created in another language, **When** the screen opens, **Then** the six names are in that language.
3. **Given** an account that existed before this feature and holds no condition, **When** the feature is deployed, **Then** it receives the six conditions once.
4. **Given** an account that already holds conditions of its own, **When** the defaults would be written, **Then** nothing is added.
5. **Given** the defaults written, **Then** they are ordinary conditions: each can be renamed, recoloured, given an icon, moved or deleted.

---

### User Story 2 - Add and list conditions (Priority: P1)

The screen opens on a short explanation, a panel to add a condition, and the list of the account's
conditions in their order. A condition has a name, a colour picked from the fixed palette, and,
optionally, an icon picked from the application's catalogue of glyphs. The panel shows how the
condition will look before it is added. Each row shows the colour, the icon when there is one, and
the name.

**Why this priority**: The defaults never fit everybody: a collector of comics grades them "Mint" or
"Near mint". **Independent Test**: Add "Mint" in teal with the star icon; it appears last in the list.

**Acceptance Scenarios**:

1. **Given** conditions in two accounts, **When** the screen opens, **Then** only this account's conditions appear, in the account's order, with a line saying how many there are.
2. **Given** a name typed, a colour picked and possibly an icon, **When** "Add condition" is pressed or Enter is typed in the name, **Then** the condition is added at the end of the list and the name field is emptied.
3. **Given** a name, a colour and an icon, **When** they change, **Then** the preview shows the condition with them before anything is saved.
4. **Given** no icon picked, **When** the condition is added, **Then** it has no icon and is shown with its colour and name alone.
5. **Given** an empty name, or a name the account already uses ignoring case ("used" beside "Used"), **When** the condition is added, **Then** it is refused and the screen says why.
6. **Given** no condition, **When** the screen opens, **Then** an empty state says what a condition is for.

---

### User Story 3 - Change a condition (Priority: P1)

"Edit" on a row turns it into a form with the name, the colour and the icon. Saving applies it;
"Cancel" or Escape leaves the condition as it was. The icon can be removed as well as changed.

**Why this priority**: A default that does not fit has to be adjustable without deleting it.
**Independent Test**: Rename "Worn" to "Well loved", make it amber, give it the heart icon, then
remove the icon.

**Acceptance Scenarios**:

1. **Given** a new name, colour or icon, **When** saved, **Then** the row shows them and keeps its place in the order.
2. **Given** a condition with an icon, **When** "No icon" is picked and saved, **Then** the condition has no icon.
3. **Given** an empty name, or the name of another condition of the account ignoring case, **When** saved, **Then** it is refused and the screen says why. Changing only the case of its own name is allowed.
4. **Given** the form open, **When** "Cancel" is pressed or Escape typed, **Then** nothing changes.

---

### User Story 4 - Reorder conditions (Priority: P2)

A condition can be moved up or down the list, by dragging its row or with buttons that work from the
keyboard. The new order is kept and used wherever conditions are listed.

**Why this priority**: The order is what makes a list of conditions read from best to worst; an
added "Mint" has to go above "New". **Independent Test**: Move "Mint" from last to first.

**Acceptance Scenarios**:

1. **Given** a condition, **When** it is moved up or down, **Then** the list shows it at its new place and keeps that order on the next visit.
2. **Given** the first condition, **Then** it offers no move up; the last offers no move down.
3. **Given** a move made from the keyboard, **Then** focus stays on the moved condition and a screen reader announces its new place.

---

### User Story 5 - Delete a condition (Priority: P1)

"Delete" on a row asks first, naming the condition and saying the deletion cannot be undone.

**Why this priority**: A default nobody uses should be removable. **Independent Test**: Delete
"Damaged"; it leaves the list and the conditions after it close the gap.

**Acceptance Scenarios**:

1. **Given** delete chosen, **Then** the confirmation names the condition; nothing happens until confirmed.
2. **Given** the deletion confirmed, **Then** the condition is gone, the others keep their relative order, and the account journal records it.
3. **Given** the confirmation cancelled, **Then** nothing changes.
4. **Given** every condition deleted, **Then** the empty state shows, and the defaults are not written again.

---

### User Story 6 - Through the API and an assistant (Priority: P2)

Everything the screen does can be done through the API and the assistant tools: list the conditions
in their order, read one, add, change, move and delete one. The documentation of both describes them.

**Why this priority**: The constitution makes a capability incomplete until the API covers it.
**Independent Test**: Through the API, add "Mint", move it first, give it an icon, list the
conditions, delete it; then do the same through the assistant tools.

**Acceptance Scenarios**:

1. **Given** a valid key, **When** the conditions are listed, **Then** each carries its name, colour, icon (or none) and position, in the account's order.
2. **Given** a duplicate or empty name, a colour outside the palette, or an icon outside the catalogue, **When** a condition is added or changed, **Then** the answer is a validation error saying why.
3. **Given** a condition of another account, **When** it is read, changed, moved or deleted, **Then** the answer is not found.

### Edge Cases

- A name with surrounding blank space or markup is stored without them; a name left empty is refused.
- An icon name outside the catalogue is refused rather than stored and drawn as nothing.
- Moving a condition to a position beyond the end puts it last; before the start puts it first.
- Deleting a condition closes the gap in the order, so positions stay a run of 1, 2, 3 without holes.
- An account with no vault still has its conditions, since they belong to the account.
- Two conditions with the same colour stay distinguishable by name, and by icon when they have one.
- The defaults are written in the language of the account's person at the time; changing language later does not rename them, since they are then the account's own data.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The account settings MUST offer a Possession conditions screen in the Possessions group, after Possession tags.
- **FR-002**: A condition MUST belong to one account and be shared by every vault of that account.
- **FR-003**: A condition MUST have a name of 1 to 50 characters, unique in the account ignoring case, a colour from the same fixed palette as possession types and tags, and MAY have one icon from the application's catalogue of glyphs.
- **FR-004**: A condition added without a colour MUST take the first colour of the palette; without an icon it MUST have none.
- **FR-005**: Every new account MUST receive six conditions, in this order and in its person's language: New, Like new, Used, Worn, Damaged, Broken, each with a colour of its own.
- **FR-006**: Every existing account holding no condition MUST receive the same six conditions once, when the feature is deployed. An account holding any condition MUST NOT receive them.
- **FR-007**: The screen MUST list the account's conditions in the account's order, each with its colour, its icon when it has one, and its name, under a line giving the number of conditions.
- **FR-008**: The screen MUST preview the condition being added with its name, colour and icon before it is saved.
- **FR-009**: A new condition MUST be placed at the end of the order.
- **FR-010**: Users MUST be able to rename a condition, change its colour, and set, change or remove its icon.
- **FR-011**: Users MUST be able to move a condition to another place in the order, by dragging and by keyboard operable controls.
- **FR-012**: Users MUST be able to delete a condition after a confirmation naming it; the remaining conditions MUST keep their relative order without gaps.
- **FR-013**: Adding, changing, moving and deleting a condition MUST each be written to the account journal, as MUST writing the defaults.
- **FR-014**: Every capability of FR-007 to FR-012 MUST be available through the API and the assistant tools, and described in their documentation, along with a guide page for people.
- **FR-015**: The screen MUST be operable with the keyboard alone and announced correctly by a screen reader: the colour and the icon choices are each a labelled group of options naming each option, "No icon" is one of the icon options, a refused name is announced with its reason, and a move announces the new place. Colour MUST NOT be the only thing telling two conditions apart.
- **FR-016**: Every wording, including the six default names, MUST be translated in every configured locale, and every count MUST read correctly at zero, one and more.

### Key Entities

- **Possession condition**: The state a possession is in, such as New or Broken. It has a name unique in the account ignoring case, a colour from the fixed palette, an optional icon from the glyph catalogue, and a position in the account's order. It belongs to the account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new account has six usable conditions without anybody typing anything.
- **SC-002**: Somebody adds a condition in under 20 seconds from opening the screen.
- **SC-003**: A change or a move shows in the list at once and is still there on the next visit.
- **SC-004**: No condition is deleted without a confirmation naming it.
- **SC-005**: Every action of the screen can be reproduced through the API and through the assistant tools with the same result.
- **SC-006**: The screen passes the WCAG 2.2 AA checks the project applies to every settings screen.

## Assumptions

- The condition is a new fixed concept of the account, beside Location, Possession type, Possession and Possession tag. Constitution principle VII lists the fixed concepts, so the plan amends it, as it did for those four.
- Giving a possession its condition, and filtering possessions by condition, are a later feature, as for tags. Until then nothing refers to a condition, so deleting one is permanent and takes nothing with it; the later feature decides what a deletion says about the possessions, as principle VIII asks.
- A possession will have at most one condition; that is why the list is ordered from best to worst rather than sorted by name. The order is the account's to change.
- The palette is the one possession types, tags and vaults use (Blue, Green, Amber, Rust, Violet, Teal, Plum, Slate). The six defaults take Green, Teal, Blue, Amber, Rust and Slate, in that order, so the list reads from fresh to grey.
- The icon catalogue is the one template sections and life event types already offer. The defaults carry no icon, since none of the existing glyphs says "new" or "worn" without misleading; people add one when they want.
- No design was supplied. The screen follows the possession tags screen: one page, rows edited in place, the same preview and confirmation, plus the icon choice and the reorder controls.
- There is no "restore the defaults" control. Somebody who deletes them all adds what they want by hand, or through the API.
- The 50 character limit keeps a condition short enough to read as a label.
