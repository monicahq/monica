# Feature Specification: Relationship types in account settings

**Feature Branch**: `2026-09-07-relationships`

**Created**: 2026-09-07

**Status**: Draft

**Input**: User description: "implement the relationship type management feature in the account settings based on spec/models/025-relationship-type-not-yet.md, with the design at `Relationship types.dc.html` in the Claude Design project 760fa78b-56ec-471a-b447-a873048992af"

## Overview

An account holds a list of the links it can put between two people: Parent / Child, Sibling, Partner, Friend, Colleague. Each link is a relationship type. A type belongs to a category, which is only a way of grouping the list, and it says how the link reads from each side, either the same word for both people or a different word on each side.

The product supplies a starting list with every account. This feature gives the user a settings screen where that list can be read, reworded, reordered, extended with types of their own, and trimmed of the ones they never use.

Three of the supplied types, Partner, Fiancé and Spouse, feed romantic history, and Parent / Child feeds the family tree. That behaviour is attached to the supplied type itself, never to its name or its category, so renaming or moving one of them changes the words on screen and nothing else.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the list of relationship types (Priority: P1)

The user opens Relationship types in the account settings and sees every type the account holds, grouped under its category, in the order the account has chosen. Each row names the type, shows the two roles it reads with and the direction between them, marks the ones the product supplied, and marks the ones no longer offered for new relationships.

**Why this priority**: Nothing else on the screen can be reached without it, and it is the whole feature for a user who only wants to know what words the product will use about their family and friends. A new account arrives seeded, so this story alone is a working screen.

**Independent Test**: Sign in with a freshly created account, open Relationship types, and confirm the ten supplied types appear under their categories with their roles, their direction and the supplied marking, and that Other is listed and empty.

**Acceptance Scenarios**:

1. **Given** a newly created account, **When** the user opens Relationship types, **Then** the five supplied categories are listed, Family, Romantic, Friendship, Professional and Other, and the ten supplied types appear under the first four of them, each in its recorded position.
2. **Given** a symmetric type such as Sibling, **When** the user reads its row, **Then** the row shows the one role used on both sides and reads as symmetric.
3. **Given** a directed type such as Parent / Child, **When** the user reads its row, **Then** the row shows the first role, the opposite role, and which way round they go.
4. **Given** a type that is no longer offered, **When** the user reads its row, **Then** the row is marked as not offered and says that the relationships already using it still read as they did.
5. **Given** a type the product supplied, **When** the user reads its row, **Then** the row is marked as supplied.
6. **Given** an account belonging to somebody else, **When** its Relationship types screen is requested, **Then** nothing is found.

---

### User Story 2 - Add a relationship type of my own (Priority: P2)

The user wants a link the product did not supply, Godparent / Godchild for instance. They open the form, name it, put it in a category, say whether both people carry the same word or a different one on each side, write the roles, and see a plain sentence showing how the relationship will read before they save.

**Why this priority**: This is the reason the list is editable at all. It is what a user does the first time the supplied list falls short.

**Independent Test**: Add a directed type with two roles, save, and confirm it appears in its category on the list and reads with the roles that were written.

**Acceptance Scenarios**:

1. **Given** the list screen, **When** the user chooses to add a type, **Then** a form opens with a name, a category, a choice of direction, the roles and an availability setting.
2. **Given** the form with the symmetric direction chosen, **When** the user writes one role, **Then** one role field is asked for and both sides read with that word.
3. **Given** the form with the directed direction chosen, **When** the user writes a first role and an opposite role, **Then** a preview reads two sentences, one from each person's side.
4. **Given** a completed form, **When** the user saves, **Then** the type is added at the end of its category and the list shows it without a supplied marking.
5. **Given** a form with no name or no role, **When** the user saves, **Then** the type is not created and the form says what is missing.
6. **Given** a type the user created, **When** it is saved, **Then** it carries no product behaviour, whatever it is named and whichever category it is put in.

---

### User Story 3 - Change the words of an existing type (Priority: P2)

The user renames a type, rewords its roles, or moves it to another category. This includes supplied types: a French household may prefer Conjoint to Spouse, and a user may want Colleague under a category of their own.

**Why this priority**: Rewording the supplied list is the most common reason to open this screen, and it is what makes the product read in the user's own words.

**Independent Test**: Rename a supplied type and reword one of its roles, then confirm the list and the sentences read with the new words while the type stays marked as supplied.

**Acceptance Scenarios**:

1. **Given** a supplied type, **When** the user changes its name, its roles or its category, **Then** the change is saved and the type keeps its supplied marking and its product behaviour.
2. **Given** a supplied type, **When** the user opens its form, **Then** the direction cannot be changed, and the form says the direction is kept so relationships written earlier still read the way they were written.
3. **Given** a user-created type that relationships already use, **When** the user opens its form, **Then** the wording can be changed but the direction cannot.
4. **Given** a user-created type nothing uses, **When** the user opens its form, **Then** the direction can be changed.
5. **Given** a directed type, **When** the user swaps the wording of the two roles, **Then** the two people are not swapped: the first role stays on the first person.
6. **Given** a type that feeds the family tree or romantic history, **When** the user opens its form, **Then** the form says what the product does with it and that renaming or moving it changes nothing.

**Note**: scenario 3 turns on the number of relationships using the type, which is zero for every type until the relationship record is built. See FR-032.

---

### User Story 4 - Stop offering a type without losing what is written (Priority: P3)

The user never records fiancés. They turn off the availability of that type. It stops being offered when a new relationship is created, and every relationship already using it stays readable exactly as before.

**Why this priority**: It is the answer for supplied types, which can never be deleted, and the safe answer for any type already in use. Without it the only way to tidy the list would destroy data.

**Independent Test**: Turn off a supplied type, confirm the list marks it as not offered, and confirm the relationships already using it still read with its roles.

**Acceptance Scenarios**:

1. **Given** an offered type, **When** the user turns off its availability and saves, **Then** the list marks it as not offered.
2. **Given** a type that is not offered, **When** a new relationship is created, **Then** the type is not among the choices.
3. **Given** a type that is not offered, **When** a relationship already using it is read, **Then** it reads with the type's roles exactly as before.
4. **Given** a type that is not offered, **When** the user turns its availability back on and saves, **Then** it is offered again.

---

### User Story 5 - Delete a type I created and never used (Priority: P3)

The user added a type by mistake, or tried one out and dropped it. When nothing uses it, they can remove it for good, after a confirmation that says plainly that nothing written will change.

**Why this priority**: It keeps the list honest, but it matters less than being able to retire a type, and it never applies to the supplied ones.

**Independent Test**: Add a type, delete it from its form, confirm, and check it is gone from the list.

**Acceptance Scenarios**:

1. **Given** a user-created type nothing uses, **When** the user opens its form, **Then** a section offers to delete it and says that no relationship uses it.
2. **Given** the delete confirmation, **When** the user confirms, **Then** the type is removed and the list no longer shows it.
3. **Given** the delete confirmation, **When** the user cancels, **Then** nothing is removed.
4. **Given** a supplied type, **When** the user opens its form, **Then** deletion is refused and the form points at the availability setting instead.
5. **Given** a user-created type that relationships use, **When** the user opens its form, **Then** deletion is refused, the form says how many relationships use it, and it points at the availability setting instead.

**Note**: scenario 5 turns on the number of relationships using the type, which is zero for every type until the relationship record is built. See FR-032.

---

### User Story 6 - Organise the categories (Priority: P4)

The user adds a category of their own, Neighbourhood for instance, renames it, moves it up or down among the others, and deletes it once it is empty. The five supplied categories can be moved but never renamed or deleted.

**Why this priority**: A category only groups the list. The screen works with the five supplied ones, so this is the last slice to build, and it is the one that makes a long list readable.

**Independent Test**: Add a category, rename it, move it above another, then delete it while it is empty, and confirm each step on the list.

**Acceptance Scenarios**:

1. **Given** the list screen, **When** the user adds a category, **Then** it appears at the end of the list, empty, and invites the user to name it.
2. **Given** a category the user created, **When** the user renames it, **Then** the list shows the new name and the types under it are unchanged.
3. **Given** any category, **When** the user moves it up or down, **Then** the list order changes and is kept for the next visit.
4. **Given** an empty category the user created, **When** the user deletes it and confirms, **Then** it is removed from the list.
5. **Given** a category the user created that still holds types, **When** the user tries to delete it, **Then** the deletion is refused, the message says how many types it holds, and it asks for them to be moved first.
6. **Given** a supplied category, **When** the user reads its heading, **Then** it offers no rename and no delete.

---

### User Story 7 - Reorder the types inside a category (Priority: P4)

The user drags a type above another inside its category so the ones they use most sit at the top of the picker.

**Why this priority**: It is a comfort on a list of ten, and it is the smallest slice on the screen.

**Independent Test**: Drag a type to the top of its category, reload the screen, and confirm the order held.

**Acceptance Scenarios**:

1. **Given** a category holding several types, **When** the user drags one to another position, **Then** the new order is kept and shown on the next visit.
2. **Given** a type moved to a new position, **When** the relationship picker is opened, **Then** the types read in the same order as in the settings.

---

### Edge Cases

- A name or a role is saved with only spaces in it. The form refuses it the way an empty one is refused.
- Two types in the same account are given the same name. The second is refused, so the picker never offers two identical choices.
- A directed type is switched to symmetric before it is ever used. The opposite role follows the first one, so both sides read the same word.
- Every type in the account is turned off. The list is still readable, and the relationship picker says there is nothing to choose.
- A category is emptied by moving its last type elsewhere. It stays on the list, shows that it holds nothing, and offers to add a type. Other is supplied empty, so a new account meets that state on its first visit.
- A type is moved to a category that is then deleted. The deletion is refused while the type is there.
- A supplied type is renamed to the name of another supplied type. Refused, as any duplicate is.
- A type or a category belonging to another account is addressed directly. Nothing is found.
- A type that feeds the family tree is turned off. The family tree keeps reading the relationships that already exist.

## Requirements *(mandatory)*

### Functional Requirements

**The list**

- **FR-001**: The account settings MUST offer a Relationship types screen, reachable from the settings navigation under the account's own settings.
- **FR-002**: The screen MUST show every relationship type of the account, grouped under its category, categories in their recorded order and types in their recorded order inside each category.
- **FR-003**: Each row MUST show the type's name, its two roles, and whether the two sides read the same word or a different one.
- **FR-004**: Each row MUST mark a type the product supplied, and mark a type that is no longer offered for new relationships.
- **FR-005**: A row for a type that feeds the family tree or romantic history MUST say so.
- **FR-006**: A category holding no type MUST say so and offer to add one.
- **FR-007**: The screen MUST explain, once, that a few supplied types are used by the family tree and by romantic history, that renaming them changes nothing else, and that the supplied categories cannot be renamed or deleted.

**Creating and editing a type**

- **FR-008**: Users MUST be able to create a relationship type by giving it a name, a category, a direction, a role for each side, and whether it is offered for new relationships.
- **FR-009**: Users MUST be able to change the name, the category, the roles and the availability of any type, supplied or not.
- **FR-010**: The system MUST require a name and, for a directed type, a role on each side; for a symmetric type it MUST require the single role and record it on both sides.
- **FR-011**: The system MUST refuse a name already carried by another type of the same account.
- **FR-012**: The form MUST show, as the user types, two plain sentences reading the relationship from each person's side, so the wording can be judged before it is saved.
- **FR-013**: The system MUST refuse to change the direction of a supplied type, and MUST refuse to change the direction of a type that relationships already use. In both cases the form MUST say why.
- **FR-014**: Rewording the roles of a directed type MUST NOT swap the two people: the first role stays on the first person of every relationship already recorded.
- **FR-015**: A type created by a user MUST NOT acquire product behaviour from its name or its category.
- **FR-016**: A supplied type MUST keep its stable identity, its direction and its product behaviour whatever the user changes.
- **FR-017**: The form MUST name the product behaviour of a supplied type that has one, and say that renaming, moving or retiring it leaves that behaviour alone.

**Retiring and deleting**

- **FR-018**: Users MUST be able to stop a type being offered for new relationships, and to offer it again.
- **FR-019**: A type that is not offered MUST NOT appear among the choices when a relationship is created, and MUST keep reading normally in every relationship that already uses it.
- **FR-020**: Users MUST be able to delete a type they created when no relationship uses it, after a confirmation that says what will and will not change.
- **FR-021**: The system MUST refuse to delete a type the product supplied, and MUST refuse to delete a type that relationships use, saying in each case why and pointing at the availability setting instead.

**Categories**

- **FR-022**: A new account MUST start with five supplied categories, Family, Romantic, Friendship, Professional and Other, and with the ten supplied types placed in the first four of them, each in a defined order. Other is supplied empty and holds what belongs nowhere else.
- **FR-023**: Users MUST be able to add a category, and to rename and delete the ones they added.
- **FR-024**: Users MUST be able to move any category, supplied or not, up and down the list, and the order MUST hold for the next visit.
- **FR-025**: The system MUST refuse to rename or delete a supplied category.
- **FR-026**: The system MUST refuse to delete a category that still holds a type, saying how many it holds and asking for them to be moved first.
- **FR-027**: A category MUST have no effect beyond grouping the list and the picker. Moving a type between categories MUST NOT change what the product does with it.

**Ordering**

- **FR-028**: Users MUST be able to reorder the types inside a category by dragging, and the order MUST hold for the next visit.
- **FR-029**: The order shown in the settings MUST be the order used wherever a relationship type is chosen.

**Scope of an account**

- **FR-030**: Every type and every category MUST belong to one account, and a request for one belonging to another account MUST find nothing.
- **FR-031**: Every string the screen shows MUST be translatable, and MUST be translated in every configured locale.

**Counting what uses a type**

- **FR-032**: Where a rule turns on how many relationships use a type, that number MUST be read from the relationship record. This feature does not build that record, so the number is zero for every type until it is built. The halves of FR-013 and FR-021 that turn on a type being supplied bite from the first day; the halves that turn on a type being in use refuse nothing yet, and MUST be written so that they start refusing as soon as the relationship record exists, with no change to this screen.
- **FR-033**: The copy on the screen MUST NOT state how many categories the account was supplied with, so the sentence stays true whenever that list changes.

### Key Entities

- **Relationship category**: An account-owned group for relationship types, carrying a name, a position in the list, whether the product supplied it, and a stable identity for the supplied ones. It groups and nothing more.
- **Relationship type**: An account-owned kind of link between two people, carrying a name, the category it sits in, a role for each side, whether both sides read the same word, whether it is offered for new relationships, its position inside its category, whether the product supplied it, a stable identity for the supplied ones, and the product behaviour attached to it, which is either the family tree, romantic history, or nothing at all.
- **Account**: Owns its categories and its types, and is given the supplied ones when it is created.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new account opens Relationship types and finds a usable list of ten types under its five supplied categories, with no setup step of any kind.
- **SC-002**: A user renames a supplied type and sees the new wording everywhere a relationship of that type reads, with no other visible change to the product.
- **SC-003**: A user adds a relationship type the product did not supply, from opening the settings to seeing it on the list, in under a minute and without leaving the two screens.
- **SC-004**: No action on this screen can destroy a written relationship. Every path that would do so is refused and names the alternative.
- **SC-005**: The wording a type will read with can be judged before saving, on the form itself, in every case.
- **SC-006**: The list holds its order, of categories and of types, across visits and across sessions.
- **SC-007**: All copy on both screens reads in each of the configured locales.

## Assumptions

- The relationship picker, the family tree and romantic history are named here as the places affected by these settings, but they are not built by this feature. Only the settings screens and the records behind them are.
- The public API for relationship types is a separate feature, as it was for entity types and templates. This feature is the logged-in screens.
- A category can be deactivated according to the model note, but the design offers no way to do it and none is added here. The record keeps room for it.
- The screen follows the shape of the existing settings screens: a list screen and a separate form screen for adding and editing, with dragging to reorder, as Entity types and Default values already do.
- Roles are ordinary words, so a role is a short free-text value rather than a choice from a list.
- The supplied types and categories are given to an account when it is created. The product is not in production, so there is no older account to backfill.
- Deleting a type or a category is soft, in keeping with the rest of the application, so nothing is truly lost by an accident.
- The supplied categories follow the model note 024: Family, Romantic, Friendship, Professional and Other. The design ships four and its notice reads "The four categories that came with your account". That copy is wrong for this account and is rewritten to name no number, per FR-033.
- The relationship record itself, note 026, is not built here. Every rule that counts how many relationships use a type is written now and reads zero until that record exists, so a user-created type can be deleted and any type that is not supplied can change direction. Building relationships later turns those refusals on without touching this screen.
