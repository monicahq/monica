# Feature Specification: Locale Aware Sorting

**Feature Branch**: `2026-08-27-locale-aware-sorting`

**Created**: 2026-08-27

**Status**: Draft

**Input**: User description: "sort collections for locales"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A list reads in my alphabet (Priority: P1)

A reader whose interface is French expects Élodie filed with the E names, not after Z. In
German, Müller under M. In Spanish, Ñuñez after Nunez. In Turkish, Işık where a Turkish
reader looks for it. Today those names are pushed past their letter, so a reader scrolls
past the end of the alphabet to find a name they can spell.

**Acceptance Scenarios**:

1. **Given** Dwight Schrute, Élodie Bernard and Erin Hannon, and a French interface,
   **When** the list is ordered by name, **Then** Bernard, Hannon, Schrute, with Bernard
   under B.
2. **Given** Angela Martin, Ångström Nilsson and Michael Scott, and a German interface,
   **When** the list is ordered by name, **Then** Martin, Nilsson, Scott.
3. **When** the order is reversed, **Then** the list reads as the exact reverse.
4. **Given** more people than fit on one page, **When** the reader pages to the end,
   **Then** every person appears exactly once.

### User Story 2 - The headings agree with what is under them (Priority: P2)

Letter headings are what a reader jumps by. A heading derived some other way than the order
it heads sits over people who do not belong under it.

**Acceptance Scenarios**:

1. **Given** a French interface and Élodie and Erin, **Then** both sit under one E heading.
2. **Given** a Turkish interface and Işık and Irmak, **Then** each sits under the heading a
   Turkish reader expects, and Işık is not under the dotted I.
3. **Given** a name starting with a digit, punctuation or a symbol, **Then** it sits under a
   single catch all heading.

### User Story 3 - I find a name typed without its accents (Priority: P2)

A reader looking for Élodie types elodie, because the accent is awkward to type or they do
not remember which way it leans.

**Acceptance Scenarios**:

1. **Given** a French interface, **When** the reader searches elodie, **Then** Élodie is
   among the results.
2. **Given** a Spanish interface and both Jose Ruiz and José Ruiz, **When** the reader
   searches jose, **Then** both appear.
3. **When** the same term is run on each kind of installation the product supports,
   **Then** the same entries come back.
4. **When** a term matches nothing, **Then** the list says so rather than showing a blank.

### User Story 4 - The other alphabetical lists follow the same rule (Priority: P3)

The marketplace catalogue ordered by name, and the filter naming who wrote an entry in the
administration record. Read the same way, so ordered the same way.

**Acceptance Scenarios**:

1. **Given** a French interface, **When** the catalogue is ordered by name, **Then**
   accented names sit with their unaccented letter.
2. **Given** a list the reader arranged by dragging, **Then** it keeps that order and
   nothing is alphabetised.

### Edge Cases

- Two names ranking equal under the language's rules, and two identical names. Both appear,
  once each, in an order repeatable across every reading and every page.
- A name in a script the reader's language says nothing about. It appears in a predictable
  place rather than failing to render.
- A name that is only punctuation, a digit or an emoji. It appears and it gets a heading.
- A language supplying no alphabet rules of its own. Ordered neutrally, identically every
  time.
- An installation that cannot supply alphabet rules at all. Refused at install or upgrade,
  told what is missing, rather than serving lists nobody can rely on.
- A language change whose key rebuild does not finish. Language and keys stay as they were.
- Turkish, where uppercasing a dotless letter does not give the capital it gives in the
  other eight languages.
- A name longer than the stored basis for ordering. Two names differing only past that
  length rank equal, and the rule above applies.
- A search term whose accents differ from the entry's in a language treating the two as
  different letters rather than as one letter and its accented form. The expected answer
  differs by language, so a test has to name the language it asks about.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every alphabetical list of text MUST be ordered by the alphabet of the
  reader's interface language.
- **FR-002**: An accent, diacritic or ligature MUST file where the reader's language files
  it, not after the unaccented alphabet.
- **FR-003**: French MUST compare accents from the end of the word, which no other
  configured language does.
- **FR-004**: Ordering MUST ignore capitalisation, so two spellings of one name sit together.
- **FR-005**: Two entries the language ranks equal MUST keep the same relative order on
  every reading.
- **FR-006**: A paged alphabetical list MUST show every entry exactly once across its pages.
- **FR-007**: Reversing a list MUST give the exact reverse of the ascending order.
- **FR-008**: A letter heading MUST be the letter the reader's language files every entry in
  that group under.
- **FR-009**: An entry not starting with a letter MUST group under one catch all heading.
- **FR-010**: A list the reader arranged themselves MUST keep that order and MUST NOT be
  alphabetised.
- **FR-011**: Changing the interface language MUST reorder every list, with no further
  action from the reader. The keys behind it MUST all be rebuilt together, and if the
  rebuild cannot complete, the language change MUST NOT take effect and the keys MUST be
  left as they were, so no list is ever ordered on a mixture of two languages.
- **FR-012**: A language with no alphabet rules of its own MUST still be ordered, neutrally
  and identically on every reading, without failing to render. This covers a configured
  language carrying no rules particular to it, not an installation unable to supply rules at
  all, which FR-013 refuses.
- **FR-013**: The ability to supply per language alphabet rules MUST be a declared hard
  dependency. An installation lacking it MUST be refused at install or upgrade, with a
  message naming what is missing, and MUST NOT start and serve lists in a wrong order.
- **FR-014**: Adding a tenth language MUST apply its alphabet everywhere with no list
  changed.
- **FR-015**: The list of people in a vault MUST be ordered in the reader's language. How
  that list is built and paged MAY change to allow it.
- **FR-016**: A paged alphabetical list MUST keep an ordering key beside each entry, built
  from its text under the reader's language, and MUST order and page on that key. Turning a
  page MUST stay as cheap as today and MUST NOT get more expensive as a vault grows. A list
  held whole MAY be ordered directly, with no stored key.
- **FR-017**: Searching MUST compare text the way the reader's language compares it. A term
  written without accents MUST find an entry written with them wherever that language treats
  the accent as a minor difference, and case MUST keep folding as it does today.
- **FR-018**: Where the language treats two characters as different letters rather than as
  one letter and its accented form, searching MUST keep them apart. The language decides
  what folds, not a list of characters written down here.
- **FR-019**: Searching MUST keep matching anywhere in the text, not only at its start.
- **FR-020**: The same term over the same data MUST return the same entries on every
  installation the product supports, however it stores its data.

### Key Entities

- **Reader's language**: the interface language of the person looking. Held per user. It
  decides an order, not the language a name was written in.
- **Alphabetical list**: any list ordered by text a person wrote or chose. Distinct from a
  list the reader arranged, which carries its own order.
- **Ordering key**: what is compared to rank two entries. A property of the pair of entry
  and language, not of the entry alone.
- **Letter heading**: the letter over a group, from the same rules as the order it heads.

## Success Criteria *(mandatory)*

- **SC-001**: In each of the nine configured languages, a list holding that language's
  characteristic letters reads in its dictionary order.
- **SC-002**: A count of alphabetical lists not following the reader's language is zero.
- **SC-003**: Paging a list of at least three pages shows every entry exactly once, in all
  nine languages.
- **SC-004**: A count of headings disagreeing with their contents is zero.
- **SC-005**: Changing the language of an account holding a thousand people takes one step,
  after which every list reads entirely in the new language. A count of readings showing a
  mixture is zero, including after a change that failed.
- **SC-006**: A list of a thousand entries appears within a tenth of the time it takes today.
- **SC-007**: A language with no rules of its own still renders, ordered identically every
  reading. An installation that cannot supply rules is refused, and told what is missing.
- **SC-008**: In each of the nine languages, a term written without that language's accents
  finds the entries written with them, wherever that language treats the accent as minor.
- **SC-009**: A count of terms returning different entries on two installations is zero.
- **SC-010**: A reader can find a name they can spell without scrolling past its letter, in
  all nine languages.

## Assumptions

- The reader's interface language decides the order. The language a name is written in does
  not, because one list holds names from several languages and only one order is shown.
- An account holds one user, so one language per account and one stored key per entry. A
  share link is not an exception: it shows one person, not a list. A catalogue shared across
  accounts is the exception, and it is ordered when read instead.
- Alphabet rules come from the environment, not from anything written in this repository. A
  language gains its rules by being configured, not by being coded for.
- Lists the reader arranged are out of scope: templates, pages, sections, fields, value
  lists and their items, vaults, entity types.
- Ordering numbers inside text so that item 2 precedes item 10 is out of scope.
- Ordering by date, count or anything not text is unaffected.
- Content a person wrote is never rewritten to make it sort.
- The environment component supplying the rules is used nowhere else today, so this feature
  introduces the dependency. Per FR-013 it becomes hard, which means an installation lacking
  it is refused on upgrade until an administrator adds it. That is a deliberate choice of a
  loud failure over a quiet wrong order.
