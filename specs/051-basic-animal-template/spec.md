# Feature Specification: Basic animal template

**Feature Branch**: `2026-09-25-basic-animal-template` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "Add a built-in Animal type and a Basic animal template, following the default contact
template architecture. Every new account receives it; existing accounts get it through an explicit
backfill command. Not the animal creation UI." (full brief in the conversation of 2026-09-25)

## Context

A new account starts with one supplied template, **Basic contact**, published and set as the
contact default of every vault. Nothing is supplied for entities, so an account that wants to keep
its pets starts from a blank type and a blank template. This feature supplies both for animals.
Screens and API endpoints for creating an animal are out of scope; the data they will need is not.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new account has animals ready (Priority: P1)

Dwight signs up. Under Contacts and more he finds an **Animal** type ("Animals", "Pets and other
animals you want to remember."), and under Templates a **Basic animal** template that applies to
Animals only. His first vault uses Basic animal as its Animal default.

**Independent Test**: create an account, run its seeding, inspect the type, the template, its
published version and the vault default.

**Acceptance Scenarios**:

1. **Given** a new account, **Then** it holds one active Animal type and one active supplied Basic
   animal template restricted to it, whose first structure and section versions are published.
2. **Given** that template, **Then** it has one page, General, with the sections Animal details
   (Species, Breed, Sex, Birthday, Adoption date, Microchip number), People (Caregivers: Person,
   Role), Care (Food and routine, Health and care notes) and Notes (Notes), in that order, with the
   field types of the brief.
3. **Given** Sex, **Then** it offers Female, Male and Unknown, none preselected.
4. **Given** Microchip number, **Then** it is text, so "0042" keeps its zeros, and it is not unique.
5. **Given** the template, **Then** it holds no name or avatar field: the animal's own name and
   picture carry them.
6. **Given** the account's first vault, **Then** its Animal default is the published Basic animal
   version, and its contact default is still Basic contact.
7. **Given** a Create contact screen, **Then** Basic animal is not offered.

### User Story 2 - Vaults created later get the Animal default (Priority: P1)

**Acceptance Scenarios**:

1. **Given** an account with the Animal type and template, **When** Dwight creates a vault,
   **Then** its Animal default is the published Basic animal version.
2. **Given** a vault whose Animal default Dwight changed to his own template, **When** seeding or
   the backfill runs again, **Then** his choice stays.

### User Story 3 - Caregivers link an animal to people (Priority: P2)

Each caregiver row names a contact and, optionally, a role ("Owner", "Pet sitter").

**Acceptance Scenarios**:

1. **Given** a caregiver row, **Then** Person is required and Role is optional.
2. **Given** an animal, **When** a second row names a contact already listed, **Then** it is refused.
3. **Given** a contact of another vault or account, **Then** it cannot be named.
4. **Given** a caregiver is added, **Then** nothing is written on the contact's own record.

### User Story 4 - An existing account gets it on request (Priority: P2)

An administrator runs one command, for one account or for all, and it reports what it did.

**Acceptance Scenarios**:

1. **Given** an account without the supplied Animal type and template, **When** the command runs,
   **Then** both are created and every vault without an Animal default gets one.
2. **Given** the command already ran, **When** it runs again, **Then** nothing is duplicated.
3. **Given** a type or template of the user's own, whatever it is called, **Then** it is never
   changed or treated as the supplied one; where its identifier blocks creation, the account is
   skipped and reported.

### Edge Cases

- Seeding interrupted midway leaves nothing half made, and a retry completes it.
- An account whose supplied template the user edited keeps every edit and every older version.
- Accounts never see each other's type, template, value list or defaults.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Seeding a new account MUST create the Animal type before the Basic animal template,
  and both MUST be identified by stable keys, never by their translated names.
- **FR-002**: The template MUST follow Basic contact's conventions: supplied flag, translation keys
  for every name, version 1 published for the structure and each section.
- **FR-003**: All top-level fields MUST be optional; no empty value and no caregiver row is written
  by seeding.
- **FR-004**: The Sex choices MUST be a supplied list with stable keys, seeded before the template.
- **FR-005**: Every vault of the account, and every vault created after, MUST get the published
  Basic animal version as its Animal default, unless one is already set.
- **FR-006**: Seeding and the backfill MUST each run in one transaction and be safe to repeat.
- **FR-007**: The backfill MUST be an explicit command, idempotent, and report each account as
  seeded, already done or skipped with its reason.
- **FR-008**: A caregiver list MUST NOT name the same contact twice, through a per-field setting
  ("distinct within its list") set on Caregivers > Person; fields without it behave as today.
- **FR-009**: Basic contact, its seeding and the contact picker MUST NOT change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of accounts created after release have the Animal type, the published Basic
  animal template and an Animal default in every vault.
- **SC-002**: Running seeding or the backfill twice on the same account produces the same rows as
  running it once.
- **SC-003**: No user-created type, template or vault default changes when the backfill runs.

## Assumptions

- The supplied type is recognised through the supplied template that is restricted to it, so no
  column is added to types; a user's type named "Animals" is never the supplied one.
- Where the backfill finds the type's slug `animals` or the template's slug `basic-animal` already
  used by the user, it skips the account rather than inventing another slug.
- Sex is a value list like Genders, so it appears in Value lists and can be extended there.
- The relation input on screens does not exist yet (out of scope); rules are enforced where values
  are written.
