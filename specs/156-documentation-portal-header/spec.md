# Feature Specification: Documentation portal header

**Feature Branch**: `2026-11-24-documentation-portal-header` | **Created**: 2026-11-24 | **Status**: Draft

**Input**: "On https://my.monicahq.com/docs the header is poor. The documentation is meant to look like
the public marketing site, so add exactly the same header as the marketing site, taken from that
repository's code."

The reference is the marketing repository's `source/_partials/site-header.blade.php` and
`source/_partials/docs-menu.blade.php`, with their copy under `nav` in `lang/*.php`, as of 2026-11-24.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The portal wears the marketing header (Priority: P1)

A visitor crossing from the marketing site into the portal sees the header they just left.

**Independent Test**: Open the marketing home page and a portal page at the same width, in both
themes, and compare the headers.

1. **Given** any portal page, **When** it loads, **Then** the header shows, in order: the mark and
   wordmark, Features, Pricing, Blog, Docs, the sales link, the GitHub link with its star count, the
   theme switch, Sign in and Get started.
2. **Given** the same width and theme, **When** the two headers are compared, **Then** sizes, spacing,
   colours, fonts, icons and wrapping match.

### User Story 2 - The two menus open and lead where the marketing ones do (Priority: P1)

**Independent Test**: Open Features and Docs in the portal and follow every link.

1. **Given** either menu, **When** opened, **Then** it shows the marketing panel: three columns, the
   strip of links, and the card beside them.
2. **Given** either menu, **When** a reader clicks, taps, rests the mouse or uses the keyboard,
   **Then** it opens; Escape and a click elsewhere close it.
3. **Given** a link to a marketing page, **When** followed, **Then** it opens that page on the public
   marketing site; a link to the portal stays on the instance being read.
4. **Given** any portal page, **When** drawn, **Then** Docs is marked as the current section.

### User Story 3 - Signing in and getting started stay on this instance (Priority: P2)

1. **Given** a visitor, **When** they follow Sign in or Get started, **Then** they reach this
   instance's sign in or registration screen.
2. **Given** registration is closed, **When** a visitor loads the portal, **Then** Get started is absent.
3. **Given** a signed in reader, **When** they load the portal, **Then** one primary button to their
   dashboard replaces Sign in and Get started.

### User Story 4 - The header speaks the reader's language (Priority: P2)

1. **Given** a reader in a language the marketing site has, **When** the portal loads, **Then** the
   header copy is the marketing copy in that language and marketing links open that language's pages.
2. **Given** a language the marketing site lacks, **When** the portal loads, **Then** the copy is
   translated and marketing links open the English pages.

### Edge Cases

- A self hosted instance shows the same header, linking to the public marketing site.
- Printing a page leaves the header out.
- The star count is the marketing site's fixed fallback figure; nothing is fetched to draw it.
- A menu panel taller than the window scrolls inside itself.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every portal page MUST show the marketing header in place of the current one.
- **FR-002**: The header MUST match the marketing header's content, order, layout, sizes, colours,
  icons, breakpoints and behaviour in both themes.
- **FR-003**: Both menus MUST open on click, tap, keyboard and mouse rest, close on Escape and on a
  click elsewhere, and open without scripts.
- **FR-004**: Marketing links MUST be absolute, in the reader's language when the marketing site has
  it and in English otherwise.
- **FR-005**: Documentation links, Sign in and Get started MUST stay on the instance being read.
- **FR-006**: Get started MUST be absent while registration is closed.
- **FR-007**: A signed in reader MUST see one button to the dashboard instead of Sign in and Get started.
- **FR-008**: Docs MUST be the only item marked as current.
- **FR-009**: The header copy MUST be translated in every shipped locale, with the marketing wording
  wherever the phrase is new to the application.
- **FR-010**: The header MUST meet WCAG 2.2 AA, including visible focus on every control.
- **FR-011**: The header MUST stay hidden in print and MUST NOT fetch anything to be drawn.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: At 375, 768, 1280 and 1920 pixels, in both themes, the two headers show no visible
  difference other than the current item and the focus ring.
- **SC-002**: Every header link reaches a working page from the portal, in all eight marketing languages.
- **SC-003**: Every control and menu link can be reached and activated with the keyboard alone.

## Assumptions

- "Exactly the same" covers what a reader sees and does; the markup uses the application's own tokens
  and components where they draw the same thing.
- Keeping the two headers in step afterwards is manual.
- A phrase the application already translates keeps its translation, since one phrase has one
  translation in a locale (research R4).
