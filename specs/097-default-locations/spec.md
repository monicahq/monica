# Feature Specification: Default Locations

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-26 · **Status**: Draft
**Input**: "je veux que les comptes par defaut, aient des locations par defaut ajouté dans le compte.
fais une migration pour les comptes existants aussi. voici la liste à fournir: 🏠 Maison, 🛋️ Salon,
🍳 Cuisine, 🛏️ Chambre, 🚿 Salle de bain, 📦 Rangement (the last five indented under the first). ca
doit etre un job qui est declenchée quand on cree le compte."

Specification 086 states that no location is created automatically for an account. This reverses
that: an account starts with one house and five rooms inside it, so somebody recording where a thing
is kept finds a place to pick, and renames, moves or deletes what does not suit them.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new account starts with a house and its rooms (Priority: P1)

Somebody signs up. Shortly after, the locations screen lists "My house" at the top level and the five
rooms of the catalogue inside it, each with its emoji, in the language they chose.

**Why this priority**: It is the feature. **Independent Test**: Create an account, let the queue run,
open the locations screen.

**Acceptance Scenarios**:

1. **Given** a new account, **When** its setup work has run, **Then** it holds exactly the six
   locations of the catalogue, with their parent and emoji, and no address.
2. **Given** an account created in French, **When** the screen opens, **Then** it reads "Maison",
   "Salon", "Cuisine", "Chambre", "Salle de bain" and "Rangement".
3. **Given** the setup work has run, **When** the account journal is opened, **Then** it holds one
   entry saying the locations were set up.

### User Story 2 - The seeded locations are ordinary locations (Priority: P1)

A seeded location is the account's own from the moment it exists.

**Why this priority**: A default that cannot be changed is a constraint, not a help.
**Independent Test**: Rename "Storage space" to "Garage", move "Bedroom" to the top level, delete
"Bathroom".

**Acceptance Scenarios**:

1. **Given** a seeded location, **When** it is renamed, moved, given an address or deleted, **Then**
   it behaves exactly as in specification 086, and nothing marks it as seeded.
2. **Given** "My house" holding its rooms, **When** somebody deletes it, **Then** it is refused as any
   location holding another is.
3. **Given** a seeded location deleted, **When** the setup work runs again, **Then** it does not come
   back.

### User Story 3 - Accounts that existed before (Priority: P2)

Every account that exists when this ships receives the same six locations through the release's
migrations, with nothing for an operator to run.

**Why this priority**: Without it, everybody already using the product starts from an empty list.
**Independent Test**: With two existing accounts, one holding a location of its own, run the
migration.

**Acceptance Scenarios**:

1. **Given** an existing account with no location, **When** the migration runs, **Then** it holds the
   six locations.
2. **Given** an existing account that holds a location, **When** the migration runs, **Then** nothing
   is added to it.

### Edge Cases

- A failure while writing: nothing is kept, so an account never holds a house without its rooms.
- Somebody deletes every location: the empty state of specification 086 shows again.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Creating an account MUST queue work that gives it the six locations of the catalogue.
- **FR-002**: The work MUST run on the queue, after the account exists, and MUST NOT delay sign-up.
- **FR-003**: The work MUST write all six locations or none for an account.
- **FR-004**: The work MUST add nothing to an account that already holds at least one location.
- **FR-005**: Names MUST be written in the language of the user the account was created for, at the
  moment the work runs. Emojis are the same in every language.
- **FR-006**: The account's journal MUST record one entry when the locations are set up.
- **FR-007**: A seeded location MUST carry no mark distinguishing it from one the account created.
- **FR-008**: Every name in the catalogue MUST be translatable in every configured locale.
- **FR-009**: A migration MUST give the catalogue to every existing account holding no location,
  through the same work a new account receives.
- **FR-010**: The product guide page on locations MUST say an account starts with these locations and
  that they can be changed or deleted.

### The catalogue

| Location | Emoji | Inside | French |
| --- | --- | --- | --- |
| My house | 🏠 | (top level) | Maison |
| Living room | 🛋️ | My house | Salon |
| Kitchen | 🍳 | My house | Cuisine |
| Bedroom | 🛏️ | My house | Chambre |
| Bathroom | 🚿 | My house | Salle de bain |
| Storage space | 📦 | My house | Rangement |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every account created after this ships holds the six locations within the time its
  other setup work takes, with no change to how long sign-up takes.
- **SC-002**: Running the work or the migration any number of times for one account leaves it with
  six locations.
- **SC-003**: After the release, every account that held no location holds the six, and every account
  that held one holds exactly what it held before.

## Assumptions

- The indentation in the input means the five rooms sit inside "Maison".
- English is the source language, so the English names are the keys and French reaches the reader
  through the translation file. "Maison" is "My house" and "Rangement" is "Storage space", because
  "Home" and "Storage" already name an address label and file storage in every locale, and "House"
  already names a category of possession type, translated as a detached house (一戸建て, 独栋住宅) in
  several locales, which is wrong for somebody living in a flat.
- Names are stored as plain text in the user's language when the work runs, as any location name is.
  A user who later switches language keeps the seeded names in the first one.
- The work runs in the existing chain of account setup work, after the other seeding steps.
- Locations are not yet released, so in practice no existing account holds one.
