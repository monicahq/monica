# Feature Specification: Every country, named in the reader's language

**Created**: 2026-11-21

**Status**: Draft

**Input**: User description: "Provide a list of all existing countries, localized. The countries value_list_items aims at proposing a list of favorite countries, listed on the first lines of a dropdown. The entire list of countries is proposed below."

## Context

The country of an address is picked from the account's Countries value list. That list ships with
ten countries, and a country outside them can only be offered after somebody adds it by hand in the
settings, typing its name and its two letter code. Somebody recording a contact in Japan, Morocco or
Chile has to leave the contact, add the country, and come back.

This feature keeps the Countries value list as the account's favourites, shown first, and offers
every country in the world below them, each named in the language of whoever reads the screen.

## Clarifications

### Session 2026-11-21

- Q: Should a favourite country also appear again in the full alphabetical list? → A: Yes, in both groups, and either option saves the same country.
- Q: In the Countries settings, should a favourite be added by picking from the full list? → A: Yes, and a free text entry stays for a place that is not in the standard.
- Q: Should a country arriving as text through an import or CardDAV be recognised as a country of the full list? → A: No, imported text stays as text; only the form, the API and the assistant save a country.
- Q: A plain dropdown or a box that filters as you type? → A: A combo box: typing filters the list, favourites first in the results.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Pick any country without leaving the address (Priority: P1)

Michael Scott records the address of a supplier in Japan. He opens the country dropdown of the
address and sees his favourites first (United States, Canada, and the others his account keeps),
then every country in alphabetical order. He picks Japan and saves. He never visits the settings.

**Why this priority**: This is the request itself. Without it, an address outside the favourites
cannot be entered in one go.

**Independent Test**: On an account whose Countries list holds only the ten supplied countries, edit
an address, pick a country absent from that list, save, and read the country back on the contact.

**Acceptance Scenarios**:

1. **Given** an account whose favourites are the ten supplied countries, **When** somebody opens
   the country dropdown of an address, **Then** the favourites come first, in the order the account
   gave them, followed by every country, in alphabetical order.
2. **Given** that dropdown, **When** somebody picks a country that is not a favourite and saves,
   **Then** the address keeps that country and the contact shows it.
3. **Given** a favourite the account switched off, **When** the dropdown opens, **Then** that country
   is not among the favourites but can still be picked from the full list.
4. **Given** the two groups, **When** somebody using a screen reader moves through the dropdown,
   **Then** each group is announced by its name ("Favourites", "All countries").
5. **Given** the country field, **When** somebody types "per", **Then** the list narrows to the
   countries whose name contains it ("Peru" in English, "Pérou" in French), favourites first.
6. **Given** a search that matches nothing, **When** the list is empty, **Then** it says that no
   country matches, and nothing is picked.

---

### User Story 2 - Country names in the reader's language (Priority: P1)

Pam reads Monica in French. The dropdown lists "Allemagne", "Espagne", "Japon", sorted the way French
sorts them. Jim, in the same account but reading in English (or the same address read later by an
English reader through the API), sees "Germany", "Spain", "Japan" for the same countries.

**Why this priority**: The request asks for a localized list. A full list in English only would be
unusable for most of the thirty-odd locales the product ships.

**Independent Test**: Switch the interface language, open the same address, and check that the full
list and an address saved with a country from it are both named in the new language.

**Acceptance Scenarios**:

1. **Given** a reader whose language is French, **When** the dropdown opens, **Then** every country of
   the full list is named in French and ordered by French alphabetical rules.
2. **Given** an address saved with a country from the full list, **When** a reader in another language
   opens the contact, **Then** the country is named in that reader's language.
3. **Given** a favourite whose label the account wrote itself, **When** any reader opens the dropdown
   or the contact, **Then** that label is shown exactly as written, never translated.
4. **Given** a supplied favourite the account never renamed, **When** a reader opens the dropdown,
   **Then** it is named in that reader's language, as today.

---

### User Story 3 - Choose favourites from the same list (Priority: P2)

Dwight wants Lithuania among his favourites. In the Countries value list of his settings, he picks
Lithuania from the full, localized list instead of typing a name and the code "LT".

**Why this priority**: Favourites are what make the dropdown fast. Once every country is available
by name, asking somebody to know the two letter code of the one they want is no longer necessary.

**Independent Test**: Add a favourite from the settings by picking it from the list, then open an
address and find it among the favourites.

**Acceptance Scenarios**:

1. **Given** the Countries value list in the settings, **When** somebody adds a value, **Then** they
   choose a country from the full localized list rather than typing a code.
2. **Given** a country already among the favourites, **When** somebody tries to add it again,
   **Then** they are told it is already there and nothing is added.
3. **Given** a newly added favourite, **When** an address dropdown opens, **Then** that country appears
   among the favourites, in the position the account gave it.
4. **Given** a place that is not in the standard (a region somebody wants to treat as a country),
   **When** somebody types its name instead of picking from the list, **Then** it is added as a
   favourite with that label, shown as written to every reader, and with no country layout.

---

### User Story 4 - The same capability through the API and an assistant (Priority: P2)

An integration creating contacts from a spreadsheet sets the country of an address to "JP". An
assistant connected over MCP lists the countries to find the one a person named.

**Why this priority**: The constitution requires that everything doable in the interface is doable
through the API, and the MCP server mirrors the API.

**Independent Test**: Through the API, list the countries in a given language, then save an address
naming a country from the full list, and read it back.

**Acceptance Scenarios**:

1. **Given** an API client, **When** it asks for the list of countries, **Then** it receives every
   country with its two letter code and its name in the language the request asks for, and the
   account's favourites marked as such.
2. **Given** an API client, **When** it saves an address naming a country by its two letter code,
   **Then** the address keeps that country, whether or not it is a favourite.
3. **Given** an API client, **When** it names a code that is not a country, **Then** the request is
   refused with a message saying so, and nothing is saved.
4. **Given** an existing client that still names a favourite as today, **When** it saves an address,
   **Then** the result is unchanged.

---

### Edge Cases

- A favourite and the same country in the full list: picking either one saves the same country, and
  the address is shown the same way.
- A favourite typed as free text, now or before this feature: it stays a favourite and keeps its
  label, and it has no counterpart in the full list.
- An address saved before this feature: it keeps exactly the country text it holds today and is
  shown as before. Opening it for editing keeps that country unless somebody picks another.
- A stored country that matches neither a favourite nor a country of the full list: editing the
  address keeps it, as it does today.
- An address arriving as text through a vCard import, CardDAV or the Monica 4 import: its country is
  kept as the text received and shown as written, never translated, even when that text names a
  country of the standard. The address layout keeps working exactly as it does today for such text.
- A locale for which the product has no country names: the names fall back to English.
- Countries whose names change or whose codes are retired (a new country, a renamed one): the list
  follows the reference the product ships with; an address already holding a retired country keeps
  it.
- A favourite deleted from the settings: addresses already using it keep their country.
- A right to left language: the dropdown and the group names read correctly.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The country dropdown of an address MUST show the account's active favourites first, in
  the account's order, then every country in the world.
- **FR-002**: The full list MUST cover every country and territory of the reference the product ships
  with for address layouts: the international country code standard (ISO 3166-1) and the few
  territories the Unicode locale data adds to it, such as Kosovo. Every entry has a two letter code.
- **FR-003**: Every country of the full list MUST be named in the reader's language and sorted by that
  language's alphabetical rules, falling back to English names where a language has none.
- **FR-004**: The country field MUST be a combo box: typing narrows the list to the countries whose
  name, in the reader's language, contains what was typed, ignoring case and accents, with matching
  favourites listed first. The two groups MUST be labelled so that a screen reader announces which
  group an option belongs to, the number of results MUST be announced as it changes, and the field
  MUST be usable by keyboard alone (open, move through results, pick, close with Escape).
- **FR-005**: Saving an address with a country from the full list MUST record which country it is, so
  that every later reader sees it named in their own language and the address keeps the layout of
  that country.
- **FR-006**: A favourite MUST also appear at its alphabetical place in the full list. Picking it
  there MUST save the same result as picking the favourite.
- **FR-007**: A favourite label written by the account MUST be shown as written and never translated.
- **FR-008**: Addresses saved before this feature MUST keep and show the country they hold, unchanged,
  until somebody edits that field.
- **FR-009**: Somebody MUST be able to add a favourite by choosing a country from the full localized
  list in the Countries value list settings, through the same combo box as the address, without
  typing a country code. A free text entry MUST
  remain for a place that is not in the standard; it carries no country code.
- **FR-010**: Adding a country already among the active favourites MUST be refused with a message.
- **FR-011**: The API MUST list every country with its code, its name in the requested language and
  whether it is a favourite of the account, and MUST accept a country code when an address is saved.
- **FR-012**: The MCP server MUST offer the same two capabilities through the API, adding no rule of
  its own.
- **FR-013**: Every new piece of interface text (the group names, the messages) MUST be translated
  into every configured locale.
- **FR-014**: When what was typed in the country combo box names no country, the combo box MUST offer
  to record it anyway, as the last option, in the address and in the Countries settings. An address
  records it as written, without a country code; the Countries list does not change. In the settings it
  becomes a favourite carrying that name, which replaces the separate free text entry of FR-009.

### Key Entities

- **Country**: one entry of the international country code standard, identified by its two letter
  code and named differently in each language. Supplied by the product, the same for every account,
  never edited by an account.
- **Favourite country**: an item of the account's Countries value list. It has a label, a position,
  an on or off state, and usually points at one Country. The account shapes this list.
- **Address country**: the country an address holds. Either a Country of the full list, a favourite,
  or (for addresses saved earlier or imported) a text that matches neither.

## Success Criteria *(mandatory)*

- **SC-001**: Somebody can record an address in any of the 256 countries and territories of the
  reference without leaving the contact form.
- **SC-002**: For every shipped locale, 100% of the countries in the full list are shown with a name in
  that locale or its English fallback, and none appears with a bare code.
- **SC-003**: Picking a favourite takes no more steps than it does today: favourites stay at the top,
  before any other country.
- **SC-004**: 100% of addresses saved before the change show the same country text after it.
- **SC-005**: Adding a favourite requires no knowledge of country codes.
- **SC-006**: Every country that can be picked in the interface can be set through the API and an
  assistant.

## Assumptions

- The Countries value list keeps its role and its name in the settings; the ten supplied countries
  stay its default content. This feature adds the full list beside it, it does not replace it.
- The reference list of countries and their names in each language comes from a source the product
  already ships with, so no list is typed or translated by hand.
- Countries are offered only in the address country dropdown and in the Countries settings. Other
  places a country could appear (a phone number's country prefix, a contact's nationality) are out of
  scope.
- A country picked from the full list is not added to the favourites automatically.
- Imports and CardDAV are out of scope: they keep storing the country text they receive.
- The reader's language is the one already used for the rest of the interface, and for the API the
  language the request asks for, as elsewhere.
