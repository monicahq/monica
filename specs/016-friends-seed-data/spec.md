# Feature Specification: Friends seed data

**Feature Branch**: `2026-09-04-friends-seed-data`

**Created**: 2026-09-04

**Status**: Draft

**Input**: User description: "I want a better seeding data. We have Michael Scott. I want only references to the tv show Friends instead. The main account should not be Michael Scott, but Monica Geller. Then create reminders for some of these contacts, based on the main information from Friends to be realistic."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A populated account to sign in to (Priority: P1)

A contributor clones the repository, runs the seeder, and signs in as Monica Geller. The vault she
lands in already holds the people around her, so every screen that lists contacts has something to
list.

**Why this priority**: an empty account is the state a contributor cannot review a screen in. The
account and its contacts are what everything else here hangs off.

**Independent Test**: run the seeder on an empty database, sign in with the seeded address, and read
the vault dashboard. It names Monica and shows the other contacts.

**Acceptance Scenarios**:

1. **Given** an empty database, **When** the seeder runs, **Then** one account exists whose only user
   is Monica Geller, and her vault holds her as the Me contact plus the rest of the cast.
2. **Given** the seeded account, **When** a contributor opens a contact, **Then** the page shows the
   values the show gives that person rather than an empty template.
3. **Given** the seeded account, **When** the contact list is read, **Then** a few people in it are
   favourite and one is archived rather than listed.

---

### User Story 2 - Reminders that already fall due (Priority: P2)

The same contributor opens the reminders screen and the vault dashboard without setting anything up,
and finds reminders on the dates the show gives these people, with deadlines already computed.

**Why this priority**: the reminder screens (specs 012, 013 and 015) are the hardest part of the
product to review by hand, since reaching a deadline means creating a contact, a date and a rule first.

**Independent Test**: run the seeder, open the vault's reminders screen, and read the list. It names
several people and the dates they are about.

**Acceptance Scenarios**:

1. **Given** the seeded account, **When** the reminders screen is read, **Then** it lists a reminder for
   each birthday the seeder set a rule on.
2. **Given** the seeded account, **When** the reminders are read together, **Then** some of them carry
   deadlines inside the generation window rather than none of them doing so.
3. **Given** the seeded account, **When** the reminders are compared, **Then** they do not all repeat at
   the same cadence and do not all warn at the same moment.

---

### Edge Cases

- Seeding with no queue worker running. Nothing the seeder needs may wait in a queue, since a
  contributor who started no worker would get an account with no template and therefore no contacts.
- A person the show gives no birthday for. They get the values it does give and no reminder.
- A person there is no reason to keep in an active list, Mr. Heckles being dead. He is archived.
- Running the seeder twice. The second run finds the account and stops.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The seeded account MUST have Monica Geller as its one user, reachable with a password a
  contributor can guess, and no reference to The Office anywhere in what it writes.
- **FR-002**: The seeded vault MUST hold Monica as its Me contact.
- **FR-003**: The seeded vault MUST hold the rest of the extended cast, being the other five leads and
  around a dozen recurring characters, each written down as an ordinary contact.
- **FR-004**: Each contact MUST carry the values the show supplies for that person, across the fields
  the default template already offers: birthday, gender, category, email address, phone number,
  postal address and a note.
- **FR-005**: Every email address and phone number the seeder writes MUST be unmistakably fictional,
  so that seed data can never reach a real person.
- **FR-006**: A few contacts MUST be marked favourite, and at least one MUST be archived, so both
  states are visible without a contributor producing them.
- **FR-007**: The seeder MUST create reminders on the birthdays of a subset of the contacts, and those
  reminders MUST differ from one another in cadence and in the moments they warn at.
- **FR-008**: The reminders MUST between them carry deadlines, so the reminder screens have
  something to show on the first page load.
- **FR-009**: The seeder MUST complete on its own, with no queue worker and no second command to type.
- **FR-010**: The seeder MUST be covered by a test asserting what it writes.

### Key Entities *(include if data involved)*

- **Account and user**: one of each, Monica Geller.
- **Contact**: one per cast member, on the default contact template, one of them the Me contact.
- **Field value**: the birthday, gender, category, email, phone, address and note written on each
  contact.
- **Reminder**: a rule hanging off a birthday value, with the deadlines it produces.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A contributor goes from an empty database to a signed in, populated account with one
  command and no further setup.
- **SC-002**: Every contact list, contact page, reminder list and dashboard block in the product has
  data to render straight after seeding, with no screen left empty.
- **SC-003**: The vault holds at least fifteen contacts and at least five reminders.
- **SC-004**: No name, address, email or phone number in the seed data refers to The Office, or to a
  real person or place that could be contacted.

## Assumptions

- Scope is `database/seeders` and the test covering it. The testing rule in `.claude/rules/testing.md`
  and the existing test suite stay on The Office, decided with the user.
- Reminders hang off dates, and the only date the default template offers is the birthday. Wedding
  anniversaries would mean a field on a published version, so a new draft, and are out of scope.
- Where the show never states a date, the widely cited fan canon date is used. Where neither exists,
  no date is written.
- Mr. Heckles is dead in the show, so he is the contact that demonstrates the archived state.
- Deadlines are generated 90 days ahead, so how many of the reminders carry one depends on the month
  the seeder is run in. Long lead moments are what keep the screens populated year round.
- Running the seeder again finds the account already there and leaves it alone.
