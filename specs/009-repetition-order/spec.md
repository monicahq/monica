# Feature Specification: Which end a new repetition goes to

**Feature Branch**: `2026-09-01-repetition-order`

**Created**: 2026-09-01

**Status**: Draft

**Input**: User description: "is it possible to define the order of items? currently, if we add another note, it goes below the first one. in some fields that's ok, but for notes, it doesn't seem great."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new note appears where somebody is already looking (Priority: P1)

Somebody opens a contact to write down what they just learned. They add a note, save, and the note they wrote goes to the bottom of a list that only ever grows, under everything they wrote in the years before. The thing they came to do lands furthest from where they were looking, and every time they come back the list is longer and the newest thing is deeper down.

A note is read newest first. That is how somebody catches up on a person: what happened lately, then further back if they need it. So a new note belongs at the top, and the list belongs in that order whenever it is read.

**Why this priority**: It is the whole of the complaint and it stands alone. Nothing else here is needed for somebody to stop scrolling to the bottom of their own notes.

**Independent Test**: Add a second note to a contact that already has one, save, and confirm the new note is above the old one when the section is read and when it is opened to be changed.

**Acceptance Scenarios**:

1. **Given** a contact with three notes, **When** somebody adds a fourth and saves, **Then** the fourth is first in the list and the other three keep the order they had among themselves.
2. **Given** a contact with several notes, **When** the section is read, **Then** they are in the same order as when the section is open as a form, so the thing somebody edits is the thing they were looking at.
3. **Given** a contact with one note, **When** somebody reads it, **Then** nothing about the ordering is visible and nothing breaks.
4. **Given** a contact with no notes, **When** somebody reads the section, **Then** it says there is nothing yet, as it does today.
5. **Given** somebody adds a note and removes an older one in the same save, **When** they save, **Then** the new note is first, the removed one is gone, and no other note moved.

---

### User Story 2 - Each repeater keeps the order that suits what it holds (Priority: P2)

Newest first is right for notes and wrong for other things. Documents and photographs read as a collection somebody has built up, and reversing them would shuffle a set nobody asked to have shuffled. A passport scanned first is not the least important one.

So the direction is something a repeater says about itself, not a rule the product applies to every repeater at once.

**Why this priority**: It is what stops the first story from being a change nobody asked for everywhere else. It is second because it can be tested only once a direction can be declared at all.

**Independent Test**: On the template an account starts with, add a note and a document to a contact and confirm the note goes to the top of the notes and the document goes to the bottom of the documents.

**Acceptance Scenarios**:

1. **Given** the template an account starts with, **When** somebody adds a document or a photograph, **Then** it goes to the end, exactly as it does today.
2. **Given** a repeater that says nothing about its direction, **When** somebody adds a repetition, **Then** it goes to the end, so nothing already built changes behaviour by being left alone.
3. **Given** two repeaters on one page with different directions, **When** somebody adds a repetition to each in the same save, **Then** each obeys its own direction.

---

### User Story 3 - Somebody who builds a template chooses the direction (Priority: P3)

The direction is right for notes because of what a note is, and somebody inventing their own repeater knows the same thing about whatever they are collecting. A meeting log wants newest first. A set of certificates does not.

**Why this priority**: It is the difference between the product having decided for its own template and the personalisation engine being able to express the idea at all. It is last because the first two stories deliver the value with the product's own answer, and this one asks for a control on a screen.

**Independent Test**: Create a repeater in the builder, choose newest first, put a record on the published version, and confirm a new repetition goes to the top.

**Acceptance Scenarios**:

1. **Given** somebody adding a repeater in the builder, **When** they look at what it asks, **Then** the direction is one of the things they can set, worded in terms of what they will see rather than in terms of a number.
2. **Given** a repeater whose direction somebody did not touch, **When** it is published, **Then** it collects newest last.
3. **Given** a published version, **When** somebody changes the direction, **Then** a new version carries the change and records already on the old version are unaffected.

---

### Edge Cases

- A repetition removed and another added in the same save. Both happen, and nothing else moves.
- A repeater holding one repetition, or none. The direction is invisible and nothing errors.
- The order somebody sees and the order stored underneath run opposite ways. Every repetition still has to come back from the form as the repetition it was: a save that loses track of which is which does not reorder anything, it deletes it, because a repetition that does not arrive is one the save understands as taken away.
- Two repetitions that somehow hold the same place in the order. Reading is still stable and repeatable rather than left to chance.
- Gaps in the order, left behind by something removed earlier. They change nothing a reader sees.
- A record created before this change, on a version published before it. It reads exactly as it did yesterday.
- A repeater whose direction somebody flips while a record is being edited in another tab. The save that lands does not move anything it was not asked to move.
- A note carrying a date that disagrees with the order it was added in. The order does not consult it. This is deliberate and is in the Assumptions.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A repeater MUST be able to say which end a new repetition is added at, as a property of that repeater.
- **FR-002**: A repeater that says nothing MUST add to the end, which is what every repeater does today, so nothing already built changes by being left alone.
- **FR-003**: The notes repeater in the template an account starts with MUST say newest first.
- **FR-004**: The documents and photographs repeaters in that template MUST keep adding to the end.
- **FR-005**: The direction MUST govern the order in both states of a block, reading and form, so that what somebody edits is what they were looking at.
- **FR-006**: Adding a repetition MUST NOT change where any existing repetition sits relative to the others.
- **FR-007**: Removing a repetition MUST NOT change where any remaining repetition sits relative to the others.
- **FR-008**: The direction MUST NOT be worked out from any value inside a repetition. Order is the order repetitions were added, and consulting a value would make it depend on something that can be empty, and would leave two answers to the same question.
- **FR-009**: A save MUST NOT lose, duplicate or reorder a repetition because of the direction it was shown in. Every repetition somebody was shown MUST come back as itself.
- **FR-010**: The direction of a repeater on a published version MUST NOT change for records already on that version. A change makes a new version, like every other change to a structure.
- **FR-011**: Reading a repeater MUST be stable: the same record read twice MUST answer in the same order.
- **FR-012**: Nothing about this MUST require a person to enter, confirm or maintain anything. The direction is a property of the structure, not a question asked of whoever fills the record in.
- **FR-013**: The builder MUST let somebody choose the direction when they add or change a repeater, worded as what they will see rather than as a number. (User Story 3)
- **FR-014**: The way somebody may later reorder repetitions by hand MUST NOT be closed off by this. The direction says where a new one lands, and says nothing about where an existing one may be moved to.

### Key Entities

- **Repetition**: One filling in of a repeater, holding whatever that repeater asks on each pass, and already carrying its own place in the order and its own identity. This feature adds nothing to it.
- **A repeater's direction**: Which end a new repetition is added at. A property of the repeater, settled when the structure is written and frozen with the version it belongs to.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody adding a note to a contact with any number of notes sees the note they just wrote without scrolling.
- **SC-002**: Adding a document or a photograph behaves exactly as it did before this change, judged on the order of the whole collection.
- **SC-003**: For any repeater and any number of repetitions, the order read and the order shown in the form are the same order.
- **SC-004**: No repetition is ever lost, duplicated or moved by a save that was not asked to move it, which is proven by a test rather than by inspection.
- **SC-005**: A record created before this change reads in the same order after it as before it.
- **SC-006**: Somebody building a repeater can express newest first without help and without a number. (User Story 3)

## Assumptions

- **Order is the order things were added, and nothing else.** Sorting notes by a date written inside them was considered and rejected: the date can be empty, it can be wrong, and it would leave the product with two answers to the question of what comes first. The place a repetition holds stays the single answer.
- **Newest first is a direction, not a sort.** Nothing is recalculated when a value changes: the order is not consulted again because somebody edited a value. This was written to say that adding a repetition never renumbers the others, on the grounds that renumbering writes to rows nobody touched. Reading the code answered that rather than upheld it. The product already renumbers every sibling when a repetition is moved, so it is not a new kind of write, it happens inside a save somebody asked for on that block, and only a number that actually changes is written. What stands is the part that matters: the relative order of what is already there is never disturbed.
- **Records already written are untouched.** A published structure is frozen, so this reaches an account when it is given a new version, and nobody's existing list reorders under them.
- **Reordering by hand is out of scope, and it is nearer than this said.** A repetition already carries its own place in the order, the intention that somebody may move one is written down where that place is stored, and the operation that moves one exists and is tested. What it has not got is a screen. So this feature does not leave a door open, it makes that operation coherent: the place a repetition holds keeps meaning the order it appears in, so first means top for every repeater. A screen for it still has to reckon with the promise that nothing is written until a block is saved, which is a question of its own.
- **User Story 3 may be dropped without harming the first two.** If the builder control is deferred, the product still declares the direction for its own template, and a repeater somebody invents collects newest last, which is what they get today.
- **The date on a note is a separate change.** Showing more about a note, its date included, is a matter of what the repeater asks on each pass, and it is neither needed by this nor blocked by it.
- **It depends on the notes repeater existing.** FR-003 and FR-004 name the repeaters in the template an account starts with. Notes became a repeater in its own change, which is not yet on the default branch, so this feature cannot be finished until that one lands. The first two stories are otherwise independent of it: a direction can be declared and honoured with the documents and photographs repeaters alone.
