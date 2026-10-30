# Feature Specification: Tag contacts with categories

**Feature Branch**: `085-contact-tags`
**Created**: 2026-10-29
**Status**: Draft
**Input**: "Add tags to contacts"

## Context

A contact already carries tags in all but name: the default Category field lets several values be picked from the account's "Contact categories" value list (Family, Close friend, Colleague...). What is missing is everything that makes tags useful.
A new category can only be added from the value lists settings screen, away from the contact being edited. The list of people cannot be narrowed by category. A vCard's `CATEGORIES` stays on the stored card, invisible. A Monica 4 import turns each contact's tags into a note reading "Tags: ...". Constitution VII forbids a second way to attach user data, so tags are the values of that field and of that list, and nothing parallel to them.

**Out of scope**: renaming the Category field or its list (the account can already do both); colours or icons on categories; groups, which are a separate core concept; showing categories through share links.

## Clarifications

### Session 2026-10-29

- Q: What are tags? → A: The values of the existing Category field, drawn from the "Contact categories" value list. No new concept.
- Q: What does this first spec cover? → A: Inline creation, filtering the list of people, vCard `CATEGORIES`, and Monica 4 tags.
- Q: Does a card naming an unknown category create it? → A: Yes, from every source: file import, CardDAV synchronisation and Monica 4 import.
- Q: When is a category typed on a contact added to the list? → A: Only when the contact is saved, as part of that save.
- Q: Can the people of a vault be narrowed to several categories at once? → A: Yes; a person shows when they hold any of them.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create a category while editing a contact (Priority: P1)

Pam edits Roy's page, types "Art class" in the Category field, and picks the offer to add it; Roy carries it, and the value is offered on every other contact of the account.
**Why this priority**: tagging that requires a detour through settings is tagging nobody does.
**Independent Test**: on a contact, add a category that does not exist, save, and check the contact holds it and the value lists screen lists it.

1. **Given** a wording no category has, **When** it is typed and added, **Then** the list gains it and the contact holds it once saved.
2. **Given** a wording an active category has, ignoring case, **When** it is typed, **Then** the existing category is offered and no second one is made.
3. **Given** a wording a stopped category has, **When** it is typed, **Then** nothing is made and the field says the category was stopped and where to offer it again.
4. **Given** a list that does not accept new values, **When** the field is shown, **Then** no offer to add appears.
5. **Given** a new wording typed and the form then abandoned, **When** the list is read, **Then** it has not gained the wording.

### User Story 2 - Show only the people of some categories (Priority: P1)

Michael opens the people of his vault and narrows them to "Colleague" and "Friend"; only the people holding either show, with how many there are.
**Why this priority**: a label nobody can search by organises nothing.
**Independent Test**: give two of three contacts a category, narrow to it, and check the two show and the third does not.

1. **Given** a vault, **When** its people are shown, **Then** every category held by at least one of its contacts is offered as a filter with its count.
2. **Given** two categories picked, **When** the people are shown, **Then** a person holding either one shows once, and a person holding neither does not.
3. **Given** a filter, **When** the account switches between Everyone and Favourites, or reloads the page from its address, **Then** the filter stays and both narrow together.
4. **Given** a stopped category that contacts still hold, **When** the filters are offered, **Then** it is offered too.

### User Story 3 - Keep categories through vCard and CardDAV (Priority: P2)

Jim's phone shows "Family" on Pam's card, and a category he adds on the phone appears on her page.
**Why this priority**: categories that stop at the browser make the address book on a phone disagree with the product.
**Independent Test**: export a contact holding two categories, read `CATEGORIES`; import a card with a new category and check the contact holds it.

1. **Given** a contact holding categories, **When** its card is exported or synchronised, **Then** `CATEGORIES` lists each of them by its label.
2. **Given** a card naming a known category, **When** it is imported or synchronised, **Then** the contact holds that category.
3. **Given** a card naming an unknown category and a list accepting new values, **When** it is imported or synchronised, **Then** the list gains it and the contact holds it.
4. **Given** a card naming categories the contact cannot hold, **When** it is imported, **Then** they stay on the stored card as today.

### User Story 4 - Import Monica 4 tags as categories (Priority: P2)

Angela imports her Monica 4 account; Oscar's tags "accounting" and "senator" become his categories rather than a note.
**Why this priority**: the note leaves the tags unsearchable and unfilterable for everyone coming from Monica 4.
**Independent Test**: import a Monica 4 file with tagged contacts and check each tag became a category held by the right contacts, with no tag note.

1. **Given** a Monica 4 contact with tags, **When** the file is imported, **Then** each tag is a category the contact holds, and no "Tags" note is written.
2. **Given** a tag matching an existing category, ignoring case, **When** it is imported, **Then** the existing category is reused.
3. **Given** a contact whose template has no Category field, **When** it is imported, **Then** its tags are written as a note, as today.
4. **Given** an import rolled back, **When** the list is read, **Then** categories the import created and no other record holds are gone.

### User Story 5 - Do all of it through the API (Priority: P3)

Dwight's native application adds a new category to a contact and lists the people of some categories, with the same outcome as the screens.
**Why this priority**: constitution X makes API coverage part of done.
**Independent Test**: repeat stories 1 and 2 through the API and compare with the screens.

1. **Given** a wording no category has, **When** the API writes it to a contact's Category field, **Then** the outcome matches story 1.
2. **Given** one or more categories, **When** the API lists a vault's contacts filtered by them, **Then** the outcome matches story 2.

### Edge Cases

- A contact whose structure version has no field drawing on "Contact categories" holds no category and is never in a category filter.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Users MUST be able to add a new category to the "Contact categories" list from a contact's Category field, without leaving the contact; the category is created by saving the contact, and only then.
- **FR-002**: The system MUST match a typed or imported wording to an existing category ignoring case and surrounding spaces, and MUST NOT create a duplicate.
- **FR-003**: The system MUST NOT create or reactivate a category from a wording matching a stopped one, and MUST say so where the wording was typed.
- **FR-004**: The system MUST offer inline creation only when the list accepts new values, as the value lists screen already decides.
- **FR-005**: Users MUST be able to narrow the people of a vault to one or more categories, showing a person who holds any of them, combined with the current grouping and kept in the address of the page.
- **FR-006**: The system MUST offer as filters exactly the categories held by at least one contact of the vault, each with its count.
- **FR-007**: The system MUST write a contact's categories to `CATEGORIES` on vCard export and CardDAV, and read `CATEGORIES` into them on import and synchronisation.
- **FR-008**: The system MUST import Monica 4 tags as categories when the contact can hold them, and as the existing note otherwise.
- **FR-009**: Rolling back an import MUST remove the categories it created that no other record holds.
- **FR-010**: Every category created by any path MUST be recorded in the account's journal, as one created from settings is.
- **FR-011**: The API MUST offer inline creation and the category filter with the same outcomes as the screens.
- **FR-012**: The filter controls and the inline creation offer MUST be operable with the keyboard alone and announced by a screen reader.

### Key Entities

- **Category**: a value of the account's "Contact categories" list. It has a label, a position and an active or stopped state, and belongs to the account, so every vault shares it.
- **Category field**: any field of a contact's structure version that draws on that list with several values allowed; the default template supplies one.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person adds a category that did not exist to a contact in one save, without opening another screen.
- **SC-002**: Narrowing a vault of 1,000 contacts to three categories shows its people as fast as the unfiltered list.
- **SC-003**: A contact's categories survive an export then a re-import through vCard with none lost and none duplicated.
- **SC-004**: A Monica 4 import of tagged contacts writes zero "Tags" notes for contacts whose template has a Category field.

## Assumptions

- The field keeps its name, "Category", in the product's copy; the word "tag" appears only where Monica 4 or vCard data is described.
- Categories stay account wide, as every value list is; a filter offers only those used in the vault being shown.
- Synchronised devices receive the new `CATEGORIES` on their next synchronisation; nothing is pushed.
