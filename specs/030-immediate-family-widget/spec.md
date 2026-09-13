# Feature Specification: Immediate family block on a contact record

**Branch**: `2026-09-13-immediate-family-widget` | **Created**: 2026-09-13 | **Status**: Draft

**Input**: "implement the immediate family box in the contact show view page. This widget only shows Current partner + children if any." Design: `Widget famille proche.dc.html`, Claude Design project `Fiche contact avec relationships`.

## Overview

A block on the contact overview answering one question at a glance: who are this person's children, and who is their partner right now. It reads relationships somebody wrote down explicitly, never deduces a link, and never claims an empty list means the person has no family. The rules are not invented here; they are written in [`spec/models/026` § Immediate family](../../spec/models/026-relationship-not-yet.md#immediate-family-on-a-contact-record), which this implements in full. The record and the form that writes it shipped in [029](../029-add-relationship-form/spec.md); nothing has read them back yet.

Out of scope: the full relationship list, the family tree, romantic history, editing or ending a relationship from the block, the API endpoint, and any surface other than the contact overview.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See a person's children (Priority: P1)

Chandler's record is open. His two children are named under **Children**, each a way through to that child's own record.

**Why this priority**: the half most records will have, and the half that does not depend on a status. The smallest slice that turns a written-down relationship into something the user can see.

**Independent Test**: record parentage from the viewed contact to two people, open the record, confirm both appear under Children with a count of two, each linking to that person.

**Acceptance Scenarios**:

1. **Given** the viewed contact is the parent side of two parentage links, **Then** both children are named once each and the list says there are two.
2. **Given** a child is reached through a link recorded as ended, **Then** they are still listed. Ending contact with a child does not undo parentage.
3. **Given** the viewed contact is the *child* side, **Then** the parent is not listed. The block looks one way only.
4. **Given** a child was written down without a name, **Then** the block uses the product's existing fallback wording and still links to their record.
5. **Given** a child is archived, or is partial and kept out of the main people list, **Then** they are listed all the same.
6. **Given** a child's birthday is recorded with a known year, **Then** their age stands beside their name; absent, or with an unknown year, nothing stands there.

### User Story 2 - See who the person is with now (Priority: P1)

Chandler is married. The block names his wife under **Current partner**, and not the partner he separated from.

**Why this priority**: the half that carries the judgement. Getting "current" wrong shows the user something untrue about somebody's life.

**Independent Test**: record one current and one ended romantic link on the same contact, open the record, confirm only the current one is listed.

**Acceptance Scenarios**:

1. **Given** a current romantic link with no dates at all, **Then** the other person is listed.
2. **Given** an ended romantic link whose end date is unknown, **Then** that person is not listed. Current is the recorded state, never a missing end date.
3. **Given** an ended link and a current link to the *same* person, **Then** they appear once and count once.
4. **Given** two different people linked as current, **Then** both are listed and the list says two. The block never picks one.
5. **Given** the viewed contact is on either side of the link, **Then** the other person is listed. The stored order changes nothing.

### User Story 3 - Understand an empty block (Priority: P2)

Somebody's family has not been written down. The block explains what would land there, and offers the way to write the first one.

**Why this priority**: most records start empty, so this is the state seen most often, and where the block could most easily say something false.

**Independent Test**: open a record with no qualifying relationships; confirm the block appears, explains itself and offers the way to add a relationship.

**Acceptance Scenarios**:

1. **Given** neither list holds anybody, **Then** the block says what would appear there and states this describes what is written down, not the person's actual family.
2. **Given** the block is empty, **Then** a way from it opens the existing relationship form for this contact.
3. **Given** children but no current partner, **Then** Children lists them and Current partner says plainly that none is recorded, rather than disappearing.

### User Story 4 - Trust the block on a crowded record (Priority: P3)

A record with parents, siblings, cousins, colleagues, friends, ex-partners and a couple of children shows the children and the current partner, and nothing else.

**Why this priority**: it only bites on a record kept for years, but it is what makes the block trustworthy. A block that quietly lets a sibling in is worse than no block.

**Independent Test**: record one relationship of every supplied type plus a user-invented type named Spouse; confirm only qualifying people appear and the counts match.

**Acceptance Scenarios**:

1. **Given** relationships of every other kind, **Then** none of them puts anybody in the block.
2. **Given** a user's own type named Spouse, **Then** nobody linked through it is listed.
3. **Given** a partner has children the viewed contact has no parentage link to, **Then** those children are not listed.
4. **Given** thousands of relationships, **Then** the block is drawn without reading them all and the page stays as fast as it is today.

### Edge Cases

- Somebody who is both a child and a current partner is counted in both lists. The counts never merge.
- The same pair linked several times, through several rows or several romantic types, yields one entry.
- A type renamed, moved to another category or withdrawn from the picker leaves everybody already in the block in it.
- Deleting a qualifying contact or relationship removes the entry; restoring either puts it back.
- The contact flagged as the user themselves behaves as any other record, and a private share link gains no access from the block.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Selection MUST follow [`spec/models/026` § Immediate family](../../spec/models/026-relationship-not-yet.md#immediate-family-on-a-contact-record) exactly: children by protected parentage behaviour with the viewed contact as parent, current partners by protected romantic behaviour and current state from either side; account and vault scoped; deleted excluded, partial and archived kept; each person once, distinct people counted; no traversal to a second link; membership never decided by a type's name, category or availability.
- **FR-002**: The selection MUST live where a future API endpoint reads the same rules, not inside a view model.
- **FR-003**: The block MUST be drawn without loading every relationship the contact has, at a query count that does not grow with the number of people listed.
- **FR-004**: The block MUST appear on the contact overview, titled *Immediate family*.
- **FR-005**: Whenever the block holds anybody, it MUST present two named lists in a fixed order, **Children** then **Current partner**, each carrying its own distinct count, and neither MUST disappear because the other holds everybody.
- **FR-006**: An empty list MUST describe what is written down rather than what is true of the person: *No children recorded*, *No current partner recorded*.
- **FR-007**: Every person listed MUST be named. A nameless person MUST show through the product's existing fallback, never an invented name.
- **FR-007a**: A person MUST lead to their own record, unless they are only a name: somebody written down while a relationship was entered and never filled in is named, said to be name only, and offered no way through, because the record behind them holds nothing to read. This departs from `spec/models/026`, which says to link every name.
- **FR-008**: Beside each name the block MUST show that person's age when a birthday with a known year is recorded, and nothing otherwise. The role is not repeated on the row; the list heading carries it.
- **FR-009**: Each list MUST name everybody who qualifies, in a stable order by name.
- **FR-010**: When both lists are empty the block MUST say what would land there, and it MUST offer the way to record a relationship whether empty or not, through the form that already exists for this contact.
- **FR-011**: The block MUST offer no other destination. Family tree, full relationship list and any *Manage* or *View all* control stay out until those screens exist.
- **FR-012**: The block MUST be operable by keyboard and screen reader, keep every person reachable without hovering, and carry no meaning in colour alone.
- **FR-013**: Every string MUST be translatable, and every phrase carrying a number MUST let each language choose its own wording.

### Key Entities

No new entity. The block reads [Contact](../../spec/models/019-contact-done.md), [Relationship](../../spec/models/026-relationship-not-yet.md) and [RelationshipType](../../spec/models/025-relationship-type-done.md) as they stand, plus the optional `birthday` field value for the age.

## Success Criteria *(mandatory)*

- **SC-001**: A recorded child or partner is visible on the overview without opening or clicking anything.
- **SC-002**: All thirteen acceptance scenarios in `spec/models/026` for this block hold.
- **SC-003**: A record with 10,000 relationships opens as fast as one with none, at a query count that does not grow with the people listed.
- **SC-004**: Nobody appears twice in one list, at any number of qualifying rows, and no age is shown for an unknown birthday year.
- **SC-005**: An empty block never states or implies the person has no children and no partner.
- **SC-006**: Every string the block can show exists in every configured locale after the feature ships.
- **SC-007**: No parent, sibling, partner's child, or person linked through a user-invented type ever appears.

## Assumptions

Three conflicts between the drawing and `spec/models/026` were put to the user and answered:

- **Two named lists, age beside the name**, against the drawing's single flat list. The heading carries the role, which frees the right-hand slot for the age.
- **No *Needs clarification* group.** No such state exists in the data, and `spec/models/026` rules out a certainty state by name.
- **Only the way to record a relationship.** *Family tree*, *Manage* and *View all* point at screens that do not exist, and the design system forbids a link that leads nowhere.

The rest, decided from the code:

- **Each list is shown whole**, not previewed. With no full list screen to reach, a truncated preview would strand people; the preview and its *View all* arrive with that screen.
- **The placeholder *Relationships* panel stays.** It describes the unbuilt full list, a different thing, so the new block sits beside it.
- **Age is best effort.** A birthday is a template field, not a column, so a contact whose template omits it has none, and one recorded without a year yields no age.
- **The block is read only** and does not refresh itself while the page is open.
