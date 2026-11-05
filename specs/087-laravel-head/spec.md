# Feature Specification: Document head metadata through Laravel Head

**Feature Branch**: `claude/laravel-head-integration-0c9ecc` | **Created**: 2026-11-02 | **Status**: Draft

**Input**: "Add Laravel Head https://laravel.com/framework/docs/head"

## Clarifications

### Session 2026-11-04

- Q: Should the public pages be indexable on a self hosted instance? → A: Yes, on every instance, but
  one environment setting turns indexing off, after which every page is noindex.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Private pages stay out of search engines (Priority: P1)

Today every page is open to crawlers: `robots.txt` allows everything and no page says otherwise. A
contact shared through a private link, a sign in screen and every signed in screen can end up in a
search index. After this change, a crawler is told not to index any page except the public ones.

**Why this priority**: A private link in a search index exposes a person's details to anybody.

**Independent Test**: Open a private link, its password form, a sign in screen, a vault screen and an
error page, and read the robots instruction each one carries.

**Acceptance Scenarios**:

1. **Given** a contact shared through a private link, **When** a crawler opens the link or its
   password form, **Then** the page tells it not to index the page and not to follow its links.
2. **Given** a signed in screen, the admin space or a sign in screen, **When** it is opened, **Then**
   it tells crawlers not to index it.
3. **Given** a page that answers not found or another error, **When** it is opened, **Then** it tells
   crawlers not to index it.
4. **Given** a documentation page or the marketplace, **When** a crawler opens it, **Then** it may
   index the page and follow its links.

### User Story 2 - Public pages describe themselves (Priority: P2)

Somebody searching for how to use the API, CardDAV, the MCP server or the guide finds a page with a
title, a one sentence description and one canonical address. A link to it pasted into a chat
application shows that title and description.

**Why this priority**: These are the only public pages, so the only ones where search matters.

**Independent Test**: Open each public page and read its title, description, canonical address and
link preview tags.

**Acceptance Scenarios**:

1. **Given** a documentation index, an API section, the CardDAV or MCP guide, a guide page or the
   marketplace, **When** it is opened, **Then** it carries its title, a description, a canonical
   address and link preview tags repeating the title and description.
2. **Given** the plain text twin of a documentation page (the `.md` address), **When** it is opened,
   **Then** nothing about it changes.

### User Story 3 - One place declares the head of every page (Priority: P3)

A developer adding a screen sets its title in one place and gets every default without copying markup.

**Why this priority**: It protects the first two stories rather than adding to them.

**Independent Test**: Render one screen per layout before and after the change and compare the
title, icons and manifest each carries.

**Acceptance Scenarios**:

1. **Given** any existing screen, **When** it is rendered after the change, **Then** its title reads
   exactly as before, and it keeps its icons, manifest, csrf token, Turbo setting and theme script.

### Edge Cases

- A screen that sets no title falls back to the application name, as it does today.
- A public page reached with a query string declares the same canonical address as without it.
- A self hosted instance declares its own host as canonical, never the hosted one. With indexing off,
  public pages keep their title, description and canonical address.
- A private link keeps the contact's name as its title, so a chat preview of the link shows it. The
  person sharing the link decides where it is pasted.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every page MUST tell crawlers not to index it, except the documentation, the guide and
  the marketplace. One environment setting MUST turn indexing off, and then every page is noindex.
- **FR-002**: Every page reached through a private link, its password form included, MUST tell
  crawlers not to follow its links either. Every error page MUST tell crawlers not to index it.
- **FR-003**: Every public page MUST carry a title, a one sentence description, a canonical address
  on the instance's own host, and link preview tags (title, description, type, site name).
- **FR-004**: Withdrawn on 2026-11-04. The guide is written in one language, so no page has another.
- **FR-005**: Every new description written in the code MUST be translated into every locale.
- **FR-006**: Every existing screen MUST keep its title wording, icons, manifest, csrf token, Turbo
  setting and theme script exactly as today.
- **FR-007**: The defaults shared by every page MUST be declared once, and a page MUST declare its
  own title, description and robots instruction in one place.
- **FR-008**: The plain text documentation addresses, `llms.txt` and the OpenAPI document MUST NOT
  change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Of every route that answers a page, the only ones a crawler may index are the
  documentation, the guide and the marketplace, and none with indexing off, checked route by route.
- **SC-002**: 100% of public pages carry a title, a description and a canonical address.
- **SC-003**: No existing screen changes its title, icons or manifest (one screen per layout).
- **SC-004**: A new screen gets every default by setting its title in one place.

## Assumptions

- The package is `laravel/head`, a required dependency. The request is the approval the project asks
  for before adding one. Laravel 13.35 is installed.
- `robots.txt` stays as it is: a path blocked there cannot carry its own instruction, and its address
  can still be indexed from links pointing at it.
- No preview image is added. The application has none made for link previews.
- Titles keep their wording with no suffix such as " · Monica". Adding one is a separate decision.
- A guide page takes its description from its own front matter, written in the page's language.
- Structured data, preloading and feeds are out of scope: nothing here calls for them.
- Links naming a guide page in other languages wait for the guide's second language.
