# Feature Specification: Phone numbers and addresses shown the way their country writes them

**Feature Branch**: `claude/location-specific-phone-address-364373`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Use location specifics ways of displaying phone number and address"

## Context

A phone number is shown exactly as it was typed or imported, so one contact shows `+33612345678` and
the next `06 12 34 56 78`. An address is shown as its filled parts joined by commas in one fixed
order (post office box, extended line, street, city, region, postcode, country), which no country
uses. This feature changes how both are shown, and nothing about what is stored.

## Clarifications

### Session 2026-11-19

- Q: Which countries get an address layout? → A: Every country, and the supplied list stays at ten.
- Q: How is a country an account added matched to its layout? → A: By its international country code.
- Q: Several lines or one line on a record page? → A: Several lines, as on an envelope.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An address reads as it would on an envelope (Priority: P1)

Somebody opens a contact whose address is in France and reads "12 rue de la Paix", then
"75002 Paris", then "France". The same screen shows a contact in the United States as
"1725 Slough Avenue", then "Scranton, PA 18505", then "United States".

**Why this priority**: every address in the product is wrong for its country today.

**Independent Test**: store the same seven parts under two countries and read two different layouts.

**Acceptance Scenarios**:

1. **Given** an address in France, **When** it is shown, **Then** the postcode comes before the city.
2. **Given** an address in the United States, **When** it is shown, **Then** city, region and postcode share a line.
3. **Given** an address with no country, or a country carrying no country code, **When** it is
   shown, **Then** it keeps today's order.
4. **Given** an address with only a city and a country, **When** it is shown, **Then** those two
   appear with no empty line and no stray separator.

### User Story 2 - A phone number reads as its country groups it (Priority: P2)

Somebody opens a contact whose number was imported as `+33612345678` and reads `+33 6 12 34 56 78`.
An American number stored as `+15705550123` reads `+1 570-555-0123`. Following the link still dials
the same number.

**Why this priority**: independent of story 1, and it only helps numbers that name their country.

**Independent Test**: store one unspaced number per country and read each grouped its own way.

**Acceptance Scenarios**:

1. **Given** a number starting with a country prefix, **When** it is shown, **Then** it is grouped as
   that country groups it, with the prefix kept.
2. **Given** text that is not a valid number ("ask Pam", "ext. 142 after 6pm"), **When** it is shown,
   **Then** it appears exactly as typed.
3. **Given** a number with no country prefix, **When** it is shown, **Then** it appears exactly as typed.

### User Story 3 - The same reading everywhere (Priority: P3)

The contact list column, a shared page, the text reading the API and the assistant give, and the
emergency phone of an insurance record show a number or an address exactly as the record page does.

**Why this priority**: consistency, and principle X of the constitution for the API.

**Independent Test**: read one address and one number on each surface and compare.

**Acceptance Scenarios**:

1. **Given** a contact with an address and a number, **When** each surface shows them, **Then** all
   of them show the same text.

### Edge Cases

- A supplied country the account renamed keeps its country code, and so its layout.
- A country the account added (Japan) gets its layout once it carries its country code.
- A number shown inside a right to left screen keeps its digits in left to right order.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: An address MUST be shown in the order and grouping of its own country, for every
  country in the world, found through the international country code its country carries.
- **FR-002**: An address with no country, or one carrying no code, MUST keep today's order.
- **FR-012**: The supplied list of countries MUST stay at ten, and an account MUST be able to give a
  country it adds its country code.
- **FR-013**: A record page and a shared page MUST show an address on several lines, as its country
  lays them out, and a list cell and the text reading MUST show the same order on one line.
- **FR-003**: Every filled part of an address MUST be shown, even one its layout has no place for.
- **FR-004**: A phone number carrying a country prefix MUST be shown grouped as its country groups it.
- **FR-005**: A phone value that is not a valid number MUST be shown exactly as stored.
- **FR-006**: A phone number without a country prefix MUST be shown exactly as stored, with no country guessed.
- **FR-007**: The country of the address or the number MUST decide, never the reader's language or place.
- **FR-008**: Showing a value MUST NOT change what is stored, edited, searched, exported or imported.
- **FR-009**: Every place that shows a phone field, an address field or an insurance record's phone
  MUST show the same reading, including shared pages and the text the API and the assistant return.
- **FR-010**: The link that dials a number MUST dial the number that was stored.
- **FR-011**: A screen reader MUST announce an address in the order it is shown.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A complete address of any country carrying its code matches that country's postal layout.
- **SC-002**: Every number with a valid country prefix is shown grouped, and every other value as typed.
- **SC-003**: No stored phone or address value differs before and after the feature ships.
- **SC-004**: One value reads the same on every surface that shows it.

## Assumptions

- The country code is the two letter code of ISO 3166, which the ten supplied countries already carry.
- The edit form keeps its present order of inputs. Reordering it by country is out of scope.
- A location's own address is one free line of text and stays as typed.
