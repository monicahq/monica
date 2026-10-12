# Feature Specification: A Page of Our Own When an Address Leads Nowhere

**Feature Branch**: `077-not-found-page` (git branch `2026-10-12-not-found-page`)
**Created**: 2026-10-12
**Status**: Draft

## Clarifications

### Session 2026-10-12

- Q: The mock's "Status" and "Help" footer links have no address in the product. Settings or drop? → A: Drop them (FR-004).
**Input**: "add a custom 404 page in the app" (illustration: `monica-404-illustration.webp`, two pandas on a stone 404 in a meadow; full page mock: `Whimsical Panda 404 Meadow.png`)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Understand that the page does not exist, and get back (Priority: P1)

Somebody follows a broken link, mistypes an address, or opens a record they cannot reach. Instead of
the framework's bare "404 Not Found", they see a page in Monica's look that says what happened and
offers one way back.

**Why this priority**: This is the feature.

**Independent Test**: Request an address that matches nothing and check the page described in FR-002.

**Acceptance Scenarios**:

1. **Given** anybody, signed in or not, **When** they request an address that matches nothing, **Then** they see the Monica logo, the illustration, "We’re sorry, but the page you’re looking for doesn’t exist.", "It may have moved, or the link might be broken.", a "Return home" button and the footer.
2. **Given** that page, **When** they press "Return home", **Then** they land on the home address, which takes a signed in person to their vaults and a visitor to sign in, as it does today.
3. **Given** that page, **When** they select the logo, **Then** it does what "Return home" does.
4. **Given** a signed in person, **When** they open a record of another account or a record through the wrong vault, **Then** they see the same page, with nothing saying the record exists.

### User Story 2 - Read the page in my language and with my tools (Priority: P2)

The page speaks the reader's language and works with a keyboard, a screen reader and at phone width.

**Why this priority**: The constitution makes both a delivery condition (V, IX).

**Independent Test**: Open the page in another locale, then with a keyboard and a screen reader, then at phone width.

**Acceptance Scenarios**:

1. **Given** a signed in person whose language is French, **When** they reach the page, **Then** every sentence, the button, the footer and the browser tab title are in French.
2. **Given** a visitor, **When** they reach the page, **Then** it uses the language the rest of the signed out screens would use for them.
3. **Given** a keyboard user, **When** they press Tab, **Then** focus moves visibly through the logo, the button and the footer links, in reading order.
4. **Given** a screen reader, **When** the page opens, **Then** the sentence is announced as the page heading and the illustration is skipped.

### Edge Cases

- A request to the API, the MCP server or a passkey endpoint keeps its JSON not found answer; it never receives this page.
- A CardDAV client keeps its not found status.
- An administrator address refused to a non administrator shows this page, which says nothing about the admin space existing.
- Dark theme: the page follows the reader's theme and shows the night version of the illustration.
- Phone width: the illustration scales down to the width of the screen and the page never scrolls sideways; the footer stacks.
- Reduced motion: the page has no animation to remove.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every not found answer to a request for a web page MUST render this page, with the not found status kept.
- **FR-002**: The page MUST show, in order: the Monica logo; the illustration; the sentence as the single page heading; the line under it; the "Return home" button; the footer.
- **FR-003**: "Return home" and the logo MUST lead to the home address.
- **FR-004**: The footer MUST show "Monica © {current year}" and "Open source personal CRM" on one side, and on the other "Docs", leading to the documentation portal, and "Legal", leading to the terms address the registration form already uses. The mock's "Status" and "Help" links are not shown.
- **FR-005**: Requests to the API, the MCP server and the passkey endpoints MUST keep their JSON not found answer; CardDAV requests MUST keep their not found status.
- **FR-006**: The page MUST be the same for a visitor and a signed in person, and MUST NOT reveal whether the requested record exists.
- **FR-007**: Every sentence, the button, the footer and the tab title MUST be translated in every configured locale.
- **FR-008**: The illustration MUST be decorative to assistive technology; the heading carries the meaning.
- **FR-009**: The page MUST meet WCAG 2.2 AA: keyboard operable, visible focus, contrast compliant in both themes, readable zoomed to 200%, usable from phone to desktop without sideways scrolling.
- **FR-010**: The page MUST render even when the reader's session or account cannot be loaded.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: No web page request ever shows the framework's default not found screen.
- **SC-002**: From the page, a person reaches the home address in one action.
- **SC-003**: No untranslated sentence of ours reaches the reader in any configured locale.
- **SC-004**: Every link on the page leads to a page that exists.
- **SC-005**: No API, MCP or passkey client sees a change in what it receives, and no CardDAV client sees a change of status.

## Assumptions

- Scope is the not found answer only. Forbidden, expired session, server error and maintenance keep their current screens; they can reuse this page later.
- The page stands alone, as in the mock: no sidebar or top bar of the signed in shell, even for a signed in person.
- "Status" and "Help" are dropped from the footer: the product has no address for either.
- The illustration ships with the product, in both hosted and self hosted, as two images (day for the light theme, night for the dark theme) with no localized variant; the "404" drawn in it is a number, not a sentence.
- The panda logo is the existing one.
- Self hosted and hosted show the same page.
