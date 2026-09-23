# Feature Specification: Edit relationship form

**Feature Branch**: `2026-09-23-edit-relationship-form` | **Created**: 2026-09-23 | **Status**: Draft

**Input**: "Change the Edit relationship screen based on design 2a of `Edit relationship.dc.html`."

## Context

Today the screen asks the type, "Which way round", "Is it still going" and two dates, and cannot
rename the other person. Design 2a is built on 1b, which makes type and direction one "Relationship"
select of sentences. 1c turns the status into one checkbox. 2a adds a block for the other person,
who may be a partial contact: somebody written down while a relationship was entered, with no record
in the list of people. 2a reuses the add form’s wording for naming one.

## Clarifications

### Session 2026-09-23

- Q: With "Is it still going" gone, what happens to a relationship ended with no end date? → A: Use
  the 1c checkbox for every relationship. Ticked with no date means ended on an unknown date.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Type and direction are one choice (Priority: P1)

From Chandler’s page, "Relationship" reads "Chandler Bing is Thierry Henry’s parent." The reader
picks "Thierry Henry is Chandler Bing’s parent." and saves; both pages read the other way round.

**Why this priority / Independent Test**: the base of the design. Reverse it, read both tabs.

**Acceptance Scenarios**:

1. **Given** an asymmetric type, **Then** it offers two sentences, one per direction; a symmetric type
   offers one. Sentences are grouped under the account’s categories, in the account’s order.
2. **Given** the screen opens, **Then** the sentence matching the stored type and direction is chosen.
3. **Given** another sentence is saved, **Then** the relationship carries its type and direction.
4. **Given** the link carries a type the account switched off, **Then** its sentences stay offered
   and saving it unchanged succeeds.

### User Story 2 - The other person, in one of three states (Priority: P1)

A block captioned with the other person’s role in the chosen sentence ("Child") shows who they are.

| The other person | The block shows |
| --- | --- |
| Has a contact record | Avatar and name. Nothing to edit: they are renamed on their own page |
| Partial, named | First name (required) and Last name (optional), filled in, "Thierry has no contact record, so their name is changed here.", and "Create a contact record for this person" |
| Partial, nameless | "I do not know their name" (chosen) and "I know their name now", which reveals the two fields; and "Create a contact record for this person" |

**Why this priority / Independent Test**: turn 2 exists for this. Open and rename each state.

**Acceptance Scenarios**:

1. **Given** the screen opened from Chandler’s page, **Then** the other person is the side that is
   not Chandler, whichever side is stored first.
2. **Given** a new name is saved, **Then** the person carries it everywhere and the change is
   recorded as a name change, as renaming on a contact page records it.
3. **Given** First name is empty while the fields are shown, **Then** saving is refused on First name.
4. **Given** "I do not know their name" is kept, **Then** the person stays nameless.
5. **Given** any refusal (end before start, a loop in parentage), **Then** nothing is saved, the name
   included, and the form comes back as the reader left it.
6. **Given** the other person is nameless, **Then** the sentences and the line under the title call
   them "an unnamed :role"; that line names the page’s contact first.

### User Story 3 - One checkbox says whether it ended (Priority: P1)

The reader ticks "This relationship has ended"; "Ended on" appears, optional. They save without a
date: it is ended on an unknown date. Later they untick it and save: it is still going again.

**Why this priority / Independent Test**: the tab and the current partner read this state. Tick and
untick, with and without a date.

**Acceptance Scenarios**:

1. **Given** the screen opens, **Then** the box is ticked exactly when the relationship is ended, and
   "Ended on" shows only then, filled when there is a date.
2. **Given** the box is unticked on save, **Then** it is still going with no end date, even if one was
   typed before unticking.
3. **Given** the end falls before the start, **Then** saving is refused on "Ended on".

### Edge Cases

- Opened from a partial contact’s own page: the other side is Chandler, who has a record; no field.
- Both sides partial: only the other person’s name is offered.
- A partial contact on several relationships: renaming them here renames them on every link.
- A relationship that does not involve the page’s contact answers not found.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: One "Relationship" select of sentences MUST replace the type and direction questions.
- **FR-002**: Saving MUST keep today’s refusals: a newly chosen switched off type, a loop in parentage,
  an end before the start.
- **FR-003**: The other person’s block MUST follow the table in User Story 2.
- **FR-004**: A name change and the relationship change MUST be saved together or not at all.
- **FR-005**: The checkbox MUST replace the status question; "Ended on" is shown and saved only while
  it is ticked.
- **FR-006**: The form MUST end with "Saved on both records at once.", Cancel and "Save changes".
- **FR-007**: Every new string MUST be translated in every configured locale; a sentence MUST be one
  translated phrase with placeholders.
- **FR-008**: The add relationship form MUST behave as it does today.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Reversing a relationship takes one choice in one field.
- **SC-002**: A partial contact’s name is corrected without leaving the screen, in one save.
- **SC-003**: Opening the screen and saving it unchanged changes nothing.

## Assumptions

- A symmetric type reads "Chandler Bing is Thierry Henry’s sibling.", not the design’s "are siblings":
  a role is one stored word, and the plural of an account’s own role cannot be built.
- The design’s "the name is kept on this relationship" is rewritten: a partial contact may be on
  several links.
- A named partial contact cannot be made nameless again here, and which two people a relationship
  joins stays uncorrectable.
- No API endpoint exists for a contact’s relationships; adding one is out of scope.
