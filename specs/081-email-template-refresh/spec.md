# Feature Specification: Refreshed Layout for Every Email Monica Sends

**Feature Branch**: `081-email-template-refresh` (git branch `2026-10-17-email-template-refresh`)
**Created**: 2026-10-17
**Status**: Draft
**Input**: "refresh the design of our transactional emails (ie change the template) based on this design" (claude.ai design project `4e0e76f6`, `Email Templates.dc.html`). "default is light, but we need to support dark mode too, even though it's complex for emails."

The 21 emails Monica sends share one layout, still the framework's stock theme. The design draws the
confirmation message in light (1a) and dark (1b); what it settles applies to every email.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every email reads as coming from Monica, in light (Priority: P1)

A reader opens any email from Monica in light mode and sees design 1a: header bar with mark and name,
the email's heading, its sentences, one near black button when there is something to do, the sign
off, the fallback strip, then the footer under the card.

**Why this priority**: It is the change, and light is what every client falls back to.
**Independent Test**: Send the confirmation message, open it in light mode, compare it with 1a.

**Acceptance Scenarios**:

1. **Given** an email with an action, **When** opened, **Then** the reader sees, in order: header
   bar, heading, sentences, button, panel if any, closing sentences, sign off, fallback strip, footer.
2. **Given** an email with no action, **When** opened, **Then** there is no button and no strip.
3. **Given** a notification with no heading of its own, **When** opened, **Then** the heading is the
   existing default ("Hello!", or "Something went wrong" for an error), shown once.
4. **Given** a long signed address, **When** the strip shows it, **Then** it is complete, a working
   link, and wraps inside the strip.
5. **Given** a reader in another language, **When** opened, **Then** the layout's words are in that
   language, as today.

### User Story 2 - Every email follows dark mode where the client allows it (Priority: P2)

A reader in dark mode opens an email. Where the client reports dark mode, they see design 1b. Where
the client recolours on its own, everything stays legible.

**Why this priority**: Clients handle dark mode in three incompatible ways, so it is layered on a
light email that must work everywhere first.

**Independent Test**: Open the confirmation message in Apple Mail in dark and compare it with 1b;
open it in Gmail and Outlook apps in dark and check every text and the mark against its ground.

**Acceptance Scenarios**:

1. **Given** a client that reports dark mode, **When** opened, **Then** page, card, strip,
   hairlines, every text, the button and the link take the colours of 1b.
2. **Given** that client, **When** opened, **Then** both marks are drawn light on dark.
3. **Given** a client that reports nothing, **When** opened, **Then** it is 1a.
4. **Given** a client that recolours on its own, **When** opened, **Then** every text and the button
   label stay legible and the mark stays visible.

### User Story 3 - The parts the design does not draw match it (Priority: P3)

Fourteen emails carry a highlighted panel ("If this was not you, change your password now."), the
digest carries bold titles, and the shared body supports a success and an error button. They take
the design system's look in both modes rather than the old theme's.

**Why this priority**: Most security emails carry a panel, but its look is derived, not drawn.
**Independent Test**: Open the "new sign in" alert and the daily digest in both modes.

**Acceptance Scenarios**:

1. **Given** an email with a panel, **When** opened, **Then** it is a sunken block with a hairline in
   the current mode's colours, where it stands today.
2. **Given** a button marked as an error or a success, **When** opened, **Then** it takes the
   design system's danger or success colour for the mode; every other button is the neutral one.
3. **Given** the digest, **When** opened, **Then** titles are bold in the heading colour.

### Edge Cases

- **Images blocked**: the header still reads the product name in text, the header mark's
  alternative text names the product, the footer mark is decoration.
- **Self hosted**: images come from the sending instance, so no other host is contacted.
- **Phone width**: the card fills the width with a smaller margin, nothing scrolls sideways.
- **Outlook on Windows**: square corners, no shadow, everything else in place.
- **Plain text part**: unchanged, out of scope.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every email MUST use the new layout without any notification being edited.
- **FR-002**: The layout MUST place, in order: header bar (mark, product name), heading, sentences, button, panel, closing sentences, sign off, fallback strip, and under the card the footer (small mark, copyright).
- **FR-003**: The heading MUST be the notification's own, falling back to the existing defaults, with no second greeting.
- **FR-004**: The fallback strip MUST appear only with an action and show the full address as a link in a fixed width face, wrapping.
- **FR-005**: The column MUST be at most 600 pixels wide and fit a phone without sideways scrolling.
- **FR-006**: Sizes, weights, spacing, colours, radii and hairlines MUST be those of 1a and 1b, in the system font stack, with no web font.
- **FR-007**: The layout MUST add no new reader facing text; it reuses the existing translated strings.
- **FR-008**: Light MUST be the default when the client reports nothing.
- **FR-009**: When the client reports dark mode, the email MUST take the colours of 1b, with light marks.
- **FR-010**: The email MUST declare that it supports light and dark.
- **FR-011**: In clients that recolour on their own, every text, the button label and the mark MUST stay legible.
- **FR-012**: The panel MUST be a sunken block with a hairline in the design system's colours for the mode.
- **FR-013**: Error and success buttons MUST take the design system's danger and success colours for the mode; others the neutral button.
- **FR-014**: Bold text MUST take the heading colour and links in sentences the link colour, for the mode.
- **FR-015**: Every text MUST meet WCAG 2.2 AA contrast on its ground in both modes, the button label included.
- **FR-016**: The email MUST state its language, give the header mark alternative text, mark the footer mark as decoration, and hide layout structures from screen readers.
- **FR-017**: Images MUST be served by the sending instance; no tracking image, no remote font, no other host.
- **FR-018**: The plain text part MUST keep its current content and order.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All 21 emails render in the new layout, none in the stock theme.
- **SC-002**: The confirmation message in Apple Mail matches 1a in light and 1b in dark, with no difference a reviewer can point to.
- **SC-003**: In Apple Mail (macOS, iOS), Gmail (web, Android, iOS) and Outlook (web, Windows), in each mode offered, all text and the mark are legible and the button usable.
- **SC-004**: Every text measures at least 4.5:1 on its ground (3:1 for the heading) in both palettes.
- **SC-005**: With images blocked, the sender is identifiable and every action can be followed.
- **SC-006**: At 375 pixels wide, no email scrolls sideways.
- **SC-007**: Opening an email contacts the sending instance only.

## Assumptions

- `styles.css` and `support.js` are the design canvas's runtime and full stylesheet; the email's look is settled by the email file, the colour tokens and the mark.
- The design's copy is the confirmation message's existing copy; no string changes.
- Pixel matching 1b is expected only where the client reports dark mode. The design's inverted mark is replaced by a separate light mark, since most clients ignore CSS filters.
- Panel and coloured buttons derive from the tokens `--sunken`, `--hairline`, `--danger`, `--success`. No notification uses a coloured button today.
