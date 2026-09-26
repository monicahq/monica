# Feature Specification: Relationships and life events in the example account

**Feature Branch**: `2026-09-26-seeded-relationships-life-events`
**Created**: 2026-09-26
**Status**: Clarified
**Input**: "in the default seeded account (for testing purposes), implement the relationships for all people (like chandler etc...) so it matches the tv show. also, provide life events for each contact, at least 20 of them, of different natures, so i can play with the app."

## Clarifications

### Session 2026-09-26

- Q: Add the canonical people the show links to who are not seeded? → A: Yes, seven of them: the twins
  Jack and Erica Bing, Frank Buffay Jr., Ursula Buffay, Barry Farber, and Chandler's parents.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The cast is connected as in the show (Priority: P1)

A developer seeds a fresh database, signs in as Monica and opens anybody. The relationships section
says who that person is to the others, and what ended in the show reads as ended.

**Why this priority**: named first by the user, and the family tree reads from it.

**Independent Test**: open Chandler: spouse Monica (current), former partner Janice (ended), parent
of Jack and Erica, child of his parents, friend of Ross, Rachel, Joey and Phoebe.

**Acceptance Scenarios**:

1. **Given** a fresh seed, **When** Monica opens Ross, **Then** he is her sibling, the child of Jack
   and Judy Geller, the parent of Ben and Emma, and the former spouse of Carol, Emily and Rachel.
2. **Given** a fresh seed, **When** Monica opens Ben, **Then** his parents are Ross, Carol and Susan,
   Jack and Judy Geller are his grandparents, Monica is his aunt, and the twins are his cousins.
3. **Given** a fresh seed, **When** Monica opens any of the 25 people, **Then** at least one
   relationship is listed.

---

### User Story 2 - Every person has a full timeline (Priority: P1)

A developer opens anybody and finds at least twenty life events drawn from the show: the weddings,
the births, Thanksgivings, London, the move to Westchester. They span every category the account
ships with, so each kind of event can be seen and edited.

**Why this priority**: the user's stated goal is to play with the app, and the timeline is where.

**Independent Test**: open any person, the archived Mr. Heckles included, and count at least twenty
events.

**Acceptance Scenarios**:

1. **Given** a fresh seed, **When** Monica opens any person, **Then** their timeline holds at least
   twenty events.
2. **Given** a fresh seed, **When** Monica opens the wedding in London, **Then** every attendee is a
   participant and the event sits once on each of their timelines.
3. **Given** a fresh seed, **When** Monica opens any event, **Then** it has a date, a title and a
   narrative, and most have a location.

---

### User Story 3 - Reseeding stays predictable (Priority: P2)

Seeding twice into empty databases gives the same data, so a bug found yesterday reproduces today.

**Independent Test**: seed twice into an empty database: the second run writes nothing.

---

### Edge Cases

- **No shipped word for the link.** Mr. Heckles was only the downstairs neighbour: the seeder adds
  one custom type, Neighbour, under Other.
- **Two links between one pair.** Ross and Rachel: spouses from the Las Vegas wedding (ended) and
  partners at the finale (current).
- **Death.** Mr. Heckles stays archived; his events end with his death in 1995.
- **Born during or at the end of the show.** Ben (1995), Emma (2002) and the twins (May 2004) have no
  event before their birth. The twins' events continue past the finale (research.md, R2).
- **Parentage loops.** The seeded graph holds none, so the shipped refusal never fires.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The account MUST link its people as the show does, using the shipped relationship types.
- **FR-002**: Every seeded person MUST have at least one relationship.
- **FR-003**: A link that ended in the show MUST be recorded as ended, with its dates where the show
  gives them; one standing at the finale MUST be current.
- **FR-004**: The six friends MUST each be friends with the other five, except Monica with Ross and
  Monica with Chandler, whom family and marriage already describe.
- **FR-005**: The seeder MUST add seven people: Jack Bing, Erica Bing, Frank Buffay Jr., Ursula
  Buffay, Barry Farber, Nora Bing and Chandler's other parent.
- **FR-006**: The seeder MUST add exactly one custom relationship type, Neighbour, under Other.
- **FR-007**: Every seeded person MUST take part in at least twenty life events.
- **FR-008**: The events MUST use every shipped life event category and at least twenty distinct types.
- **FR-009**: An event several seeded people attended MUST be recorded once, with all of them as
  participants.
- **FR-010**: Every event MUST have a date, a title and a narrative of one or two sentences.
- **FR-011**: No event MAY be dated before a participant's birth or after a participant's death.
- **FR-012**: Seeding MUST give the same data on every run and write nothing when the account exists.
- **FR-013**: Relationships and events MUST go through the same rules the app applies to a person.
- **FR-014**: The eighteen existing people, their fields and reminders MUST stay as they are.

## Success Criteria *(mandatory)*

- **SC-001**: After a fresh seed, 25 of 25 people show at least one relationship and twenty events.
- **SC-002**: Every shipped life event category and at least twenty distinct types are in use.
- **SC-003**: Somebody who knows the show finds no seeded relationship that contradicts it.
- **SC-004**: A fresh seed finishes in under ten seconds on a developer machine.

## Assumptions

- "At least 20" means per person. A shared event counts toward every participant.
- Events use the show's dates, so every timeline reads as about twenty years old.
- Type specific life event fields stay empty (research.md, R4).
- The account stays the Friends cast; the rule about The Office covers test data only.
- Chandler's relation to Ben and Emma (uncle by marriage) is recorded.
