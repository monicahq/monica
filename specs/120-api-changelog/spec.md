# Feature Specification: API changelog

**Feature Branch**: `2026-11-12-docs-portal-design` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "add a changelog for the api, based on this design [...] do not include the "upgrade" section".
Source: `API changelog.dc.html`, design project `85e4275a-6c98-4ddb-84ec-c28a81ecfb2f`.

**Decided with the requester on 2026-11-12**: the API has one unpinned version, v1, so changes are
grouped by ship date and the design's version parts are left out. It ships with no entry; entries for
existing methods come in a later pull request, on the current branch.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what changed, newest first (Priority: P1)

A developer opens "Changelog", the last item of "Getting started" in the reference sidebar. Releases
are listed newest first, each a date ("4 November 2026") with an optional note; the newest carries
"Latest". Inside a release, changes are grouped by the part of the API they touch, using the
sidebar's group names. Each row shows its kind (Added, Changed, Deprecated, Removed), its title,
"Breaking" when it is, and how many endpoints it touches.

**Why this priority**: the list is the changelog. **Independent Test**: entries on two dates.

**Acceptance Scenarios**:

1. **Given** releases on 4 November and 16 September, **Then** 4 November comes first, with "Latest".
2. **Given** a breaking entry, **Then** its row shows "Breaking"; a non breaking one shows none.
3. **Given** entries touching three endpoints and one, **Then** their rows read "3 endpoints" and
   "1 endpoint"; one touching no specific endpoint reads "All endpoints".
4. **Given** no entry exists, **Then** the title and introduction are followed by one sentence saying
   no change has been recorded yet, with no filter and no release column.

### User Story 2 - Read one change in full (Priority: P1)

Following a row opens the entry: release date, kind, part of the API, "Breaking" when it applies,
title, summary; for a breaking change a note "Why this is a breaking change" saying what fails; "Why
it changed"; a "Changes" table of field, how it changed, and endpoints; "In the same release". Beside
it, an example of the request or answer. There is no "Upgrade" section.

**Why this priority**: only the entry says whether to act. **Independent Test**: open two entries.

**Acceptance Scenarios**:

1. **Given** any entry, **Then** every block above shows, the breaking note only when breaking.
2. **Given** an endpoint the reference documents, **Then** it links to its reference page; one the
   reference does not document (removed) is plain text.
3. **Given** three entries on one date, **Then** "In the same release" lists the two others; an entry
   alone on its date has no such block.
4. **Given** an example with a before and an after shape, **Then** "After" is shown and a
   "Before"/"After" control switches them; with one shape the control is absent; with none the
   example column is absent.
5. **Given** an address naming an unknown entry, **Then** the answer is not found.

### User Story 3 - Narrow the list (Priority: P2)

Above the releases: a search over title, summary and part of the API; a kind choice (All by
default); "Breaking changes only"; a live count. Beside them, a "Releases" column names every date
with its total count and jumps to it.

**Why this priority**: worth it once the list is long. **Independent Test**: apply each filter.

**Acceptance Scenarios**:

1. **Given** "Removed" is picked or "Breaking changes only" is checked, **Then** only matching
   entries remain, emptied releases are hidden and the count follows, without a reload.
2. **Given** a search for "nickname", **Then** matching is case insensitive over the three fields.
3. **Given** filters leaving nothing, **Then** "No change matches" shows with "Clear filters".

### Edge Cases

- An entry whose part of the API is hidden from the reference (flag off for every account, support
  off) is hidden everywhere, and a release left with no visible entry is hidden.
- At phone width the release column and the example go under the content; long paths wrap.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The reference MUST offer a changelog list and one page per entry, reached from the last
  item of "Getting started", and listed in the sitemap and `llms.txt`.
- **FR-002**: Releases MUST be listed newest first, the newest marked "Latest"; inside one, entries
  are grouped by the sidebar's group names.
- **FR-003**: An entry MUST carry an identifier, a kind, a part of the API, a title, a summary, a
  reason, and changed fields each with how it changed and its endpoints. It MAY be breaking (then an
  impact sentence is required) and MAY carry an example (an after shape, optionally a before shape).
- **FR-004**: The entry page MUST show the blocks of User Story 2 in that order, and MUST NOT show an
  "Upgrade" section, a version badge, a `Monica-Version` header or copy about moving a key.
- **FR-005**: The list MUST filter in the page, as User Story 3 describes.
- **FR-006**: The introduction MUST say: every change to the API, grouped by the date it shipped,
  breaking changes marked. It MUST NOT promise that a key keeps an older behaviour.
- **FR-007**: The list and each entry MUST offer "Copy for LLM" and "View as Markdown" like every
  reference page. The list's Markdown carries every release with its entries; an entry's carries
  every block of its page and no upgrade section.
- **FR-008**: Hidden or unknown entries MUST answer not found, on the page and in Markdown.
- **FR-009**: Entries MUST be written in the repository next to the reference, so an entry ships with
  the change it records, and the test suite MUST refuse a missing field, an unknown kind or part of
  the API, a breaking entry with no impact, and a shared identifier.
- **FR-010**: The `api-docs-writer` skill MUST say that a pull request changing the API adds an entry.
- **FR-011**: The pages MUST reuse the reference's header, search, sidebar (marking "Changelog"
  current) and breadcrumb, be public, be in English like the reference, and meet WCAG 2.2 AA, the
  kind control being buttons announcing whether they are pressed.

### Key Entities

Release (a date, an optional note, its entries); Entry (FR-003); Changed field (FR-003).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer finds every breaking change since a given date in under 30 seconds, and
  every documented endpoint an entry names is one click from it.
- **SC-002**: Recording a change is one entry in the same pull request; a malformed one fails the suite.
- **SC-003**: Both pages pass the accessibility review with no new finding; the shipped changelog
  shows its empty state and no invented entry.

## Assumptions

- The design's "Versions" column becomes "Releases"; its sample data describes changes that never
  happened and is not shipped. No database: entries are content in the repository.
