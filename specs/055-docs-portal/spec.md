# Feature Specification: Documentation portal

**Feature Branch**: `2026-09-25-docs-portal` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "add the `/docs` route in the app", implementing `Docs portal.dc.html` from the claude.ai
design project `eaa519cf-2b76-489e-9daf-a6a95590877a`. A portal to every documentation area; only
the API reference exists today, the other areas come later.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Find the right documentation area (Priority: P1)

A visitor, signed in or not, opens `/docs` and sees the documentation areas Monica offers: Using
Monica (guide), API, MCP, and CardDAV and CalDAV (references), each with an icon, its kind, its title
and a one sentence description. They follow the one that answers their question.

**Why this priority**: it is the page. Without it the API reference has no way in except its URL.

**Independent Test**: open `/docs` signed out, see the four areas, follow API and land on the API
reference.

**Acceptance Scenarios**:

1. **Given** a visitor who is not signed in, **When** they open `/docs`, **Then** the page answers
   with the title "Documentation" and the four areas.
2. **Given** the portal, **When** the visitor follows the API area, **Then** they reach `/docs/api`.
3. **Given** an area whose pages do not exist yet, **Then** its card is shown
   as in the design but is not a link and carries no arrow.

### User Story 2 - Start using Monica or get an API key (Priority: P2)

From the top of the page, a visitor can start with Monica or go and create an API key.

**Why this priority**: the two most common next steps after reading the portal.

**Independent Test**: follow each button and check where it lands.

**Acceptance Scenarios**:

1. **Given** the portal, **When** the visitor follows "Get started with Monica", **Then** they reach
   the registration screen.
2. **Given** the portal, **When** the visitor follows "Get an API key", **Then** they reach the screen
   where API keys are created, signing in first when they are not signed in.

### User Story 3 - Read the documentation with an assistant (Priority: P3)

Below the areas, a short note tells somebody reading with an assistant that every page can be copied
as Markdown, where the whole reference is published as one file, and where the OpenAPI document is.

**Independent Test**: follow both links in the note; each returns its document.

**Acceptance Scenarios**:

1. **Given** the note, **When** the visitor follows the single-file link, **Then** they get
   `/llms.txt`, a plain text index of the documentation in the llms.txt format.
2. **Given** the note, **When** the visitor follows "OpenAPI spec", **Then** they get the OpenAPI document.

### Edge Cases

- A signed in visitor sees the same page; the header still offers the way to sign in only to a visitor.
- The illustration beside the title is decorative: a screen reader skips it, and it shows no real key.
- On a phone the illustration and the cards stack below the title without horizontal scrolling.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `/docs` MUST be public and answer without signing in.
- **FR-002**: The page MUST follow the design: a header with the logo, the theme switch and Sign in; a
  title, a line under it, two buttons and a decorative illustration; four area cards; the note for
  assistants; a footer saying Monica is open source and can be self hosted, linking to its source code.
- **FR-003**: Every area card that has pages MUST link to its first page; today that is the API reference only.
- **FR-004**: The header link on the logo MUST lead to the home screen.
- **FR-005**: Elements of the design that lead nowhere yet MUST be left out: the Features and Pricing
  links, the documentation search, and the Changelog and Status links in the footer.
- **FR-006**: Every string MUST be translated into every configured locale.
- **FR-007**: The page MUST meet WCAG 2.2 AA: keyboard operable, visible focus, one `h1`, the cards
  announced as a list, and the illustration hidden from assistive technology.
- **FR-008**: The theme switch MUST behave as it does on the API reference.
- **FR-009**: `/llms.txt` MUST be public and list, in the llms.txt format, the product name, a one line
  summary, and a link to the Markdown copy of every documentation page that exists, today the API
  reference as one file and each of its pages.
- **FR-010**: A documentation area added later MUST appear in `/llms.txt` once its pages exist.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A visitor reaches the API reference from `/docs` in one step.
- **SC-002**: The page renders for a signed out visitor with no link pointing at `#` or at a missing page.
- **SC-003**: Every string on the page exists in every configured locale.
- **SC-004**: The page is usable at 320 pixels wide and at 200% zoom without horizontal scrolling.

## Assumptions

- The page reuses the tokens, icons and components already in the application; the design system
  files in the design project are not copied in, since the application's theme already carries them.
- The header matches the API reference header, so the documentation reads as one place.
- `/llms.txt` follows the format described at llmstxt.org: an `h1`, a blockquote summary, then
  sections of Markdown links. It stays in English, like the API reference it lists.
- The areas are a fixed list in the code; adding the missing pages later means adding their links.
- "Get started with Monica" leads to registration, which is where an instance lets a new person in.
